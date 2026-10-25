<?php

use App\Models\Org;
use App\Models\User;

beforeEach(function () {
    $this->volunteer = User::factory()->for(Org::factory())->volunteer()->create();
});

test('volunteers land on check-in after login', function () {
    $this->post('/login', ['email' => $this->volunteer->email, 'password' => 'password'])
        ->assertRedirect('/checkin');
});

test('volunteers are sent to check-in from every other page', function (string $uri) {
    $this->actingAs($this->volunteer)->get($uri)->assertRedirect('/checkin');
})->with(['/dashboard', '/dashboard/participants', '/dashboard/participants/export', '/profile', '/']);

test('volunteers cannot change anything outside check-in', function () {
    $this->actingAs($this->volunteer)
        ->patch('/profile', ['name' => 'X', 'email' => $this->volunteer->email])
        ->assertForbidden();
});

test('volunteers can still log out', function () {
    $this->actingAs($this->volunteer)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

test('only owners and staff pass the dashboard gates', function () {
    $staff = User::factory()->staff()->create();

    expect($this->volunteer->can('view-dashboard'))->toBeFalse()
        ->and($this->volunteer->can('export-participants'))->toBeFalse()
        ->and($this->volunteer->can('check-in'))->toBeTrue()
        ->and($staff->can('view-dashboard'))->toBeTrue()
        ->and($staff->can('manage-org'))->toBeFalse();
});
