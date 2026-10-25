<?php

use App\Models\User;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    $this->event = ReportFixtures::event();
});

dataset('report routes', [
    'race sheet' => ['reports.race-sheet'],
    'race sheet csv' => ['reports.race-sheet.csv'],
    'race sheet pdf' => ['reports.race-sheet.pdf'],
    'certificates' => ['reports.certificates'],
]);

test('guests are sent to login', function (string $route) {
    $this->get(route($route, $this->event))->assertRedirect(route('login'));
})->with('report routes');

test('volunteers only see check-in', function (string $route) {
    $volunteer = User::factory()->for($this->event->org)->volunteer()->create();

    $this->actingAs($volunteer)->get(route($route, $this->event))->assertRedirect('/checkin');
})->with('report routes');

test('volunteers cannot queue certificates', function () {
    $volunteer = User::factory()->for($this->event->org)->volunteer()->create();

    $this->actingAs($volunteer)->post(route('reports.certificates.generate', $this->event))->assertForbidden();
});

test('staff can use reports', function (string $route) {
    $staff = User::factory()->for($this->event->org)->staff()->create();

    $this->actingAs($staff)->get(route($route, $this->event))->assertOk();
})->with('report routes');

test('another org cannot reach this event', function (string $route) {
    $otherOwner = User::factory()->create(); // owner of a different org

    $this->actingAs($otherOwner)->get(route($route, $this->event))->assertNotFound();
})->with('report routes');
