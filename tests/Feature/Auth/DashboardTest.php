<?php

use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\PaymentStatus;

beforeEach(function () {
    $this->org = Org::factory()->create();
    $this->owner = User::factory()->for($this->org)->create();
    $this->event = Event::factory()->for($this->org)->create(['name' => 'City 10K']);

    Registration::factory()->count(2)->for($this->event)->paid()->create(['amount_paise' => 59900]);
    Registration::factory()->for($this->event)->checkedIn()->create(['amount_paise' => 59900]);
    Registration::factory()->for($this->event)->create(['amount_paise' => 59900]); // pending
});

test('dashboard shows registrations, paid total and checked-in counts', function () {
    $this->actingAs($this->owner)->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totals', [
            'registrations' => 4,
            'paid' => 3,
            'paid_total_paise' => 179700,
            'checked_in' => 1,
        ])
        ->assertSee('City 10K')
        ->assertSee('₹1,797.00');
});

test('staff can see the dashboard and export', function () {
    $staff = User::factory()->for($this->org)->staff()->create();

    $this->actingAs($staff)->get('/dashboard')->assertOk();
    $this->actingAs($staff)->get(route('dashboard.participants.export'))->assertOk();
});

test('participants can be filtered by status', function () {
    $response = $this->actingAs($this->owner)
        ->get(route('dashboard.participants', ['event' => $this->event->id, 'status' => PaymentStatus::Pending->value]));

    expect($response->viewData('registrations')->total())->toBe(1);
});

test('CSV export has a header, one row per participant and custom answers', function () {
    Registration::factory()->for($this->event)->paid()->create([
        'participant_name' => '=HYPERLINK("http://evil")',
        'answers' => ['tshirt_size' => 'M', 'gender' => 'Female'],
    ]);

    $response = $this->actingAs($this->owner)->get(route('dashboard.participants.export', ['event' => $this->event->id]));

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $rows = array_map('str_getcsv', array_filter(explode("\n", $response->streamedContent())));

    expect($rows[0])->toContain('Pass no', 'Name', 'gender', 'tshirt_size')
        ->and($rows)->toHaveCount(6)
        ->and($rows[1][1])->toBe("'=HYPERLINK(\"http://evil\")") // newest first, formula neutralised
        ->and($rows[1])->toContain('M', 'Female');
});
