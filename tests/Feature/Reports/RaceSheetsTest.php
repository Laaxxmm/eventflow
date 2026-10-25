<?php

use App\Models\Registration;
use App\Payments\PaymentStatus;
use App\RaceSheets\HeatRaceSheetBuilder;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    $this->event = ReportFixtures::event();
    $this->owner = ReportFixtures::owner($this->event);
    $this->builder = app(HeatRaceSheetBuilder::class);
});

test('heats never mix distance, age band or gender, and follow the config order', function () {
    foreach (range(1, 9) as $i) {
        ReportFixtures::entrant($this->event, '500M', 9, 'Boy');
    }
    ReportFixtures::entrant($this->event, '500M', 9, 'Girl');
    ReportFixtures::entrant($this->event, '500M', 13, 'Boy');
    ReportFixtures::entrant($this->event, '1000M', 10, 'Boy');

    $sheet = $this->builder->build($this->event);

    expect(array_map(fn ($h) => [implode(' / ', $h->group), $h->number, count($h->registrationIds)], $sheet->heats))->toBe([
        ['500 m / 8-10 / Boy', 1, 3],   // 9 entrants, heat_size 4 → 3 even heats
        ['500 m / 8-10 / Boy', 2, 3],
        ['500 m / 8-10 / Boy', 3, 3],
        ['500 m / 8-10 / Girl', 1, 1],
        ['500 m / 12-14 / Boy', 1, 1],
        ['1000 m / 10-12 / Boy', 1, 1],
    ]);
});

test('only paid entrants are on the sheet, missing answers group as Unspecified', function () {
    $paid = ReportFixtures::entrant($this->event, '500M', 9, 'Boy');
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['status' => PaymentStatus::Pending]);
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['status' => PaymentStatus::Refunded]);
    $noAnswers = Registration::factory()->for($this->event)->paid()->create(['distance_code' => '500M', 'answers' => []]);

    $sheet = $this->builder->build($this->event);

    expect(collect($sheet->heats)->flatMap->registrationIds->sort()->values()->all())->toBe([$paid->id, $noAnswers->id])
        ->and(last($sheet->heats)->group)->toBe(['distance' => '500 m', 'age_band' => 'Unspecified', 'gender' => 'Unspecified']);
});

test('heats split evenly', function (int $count, int $size, array $sizes) {
    expect(array_map('count', $this->builder->split($count > 0 ? range(1, $count) : [], $size)))->toBe($sizes);
})->with([
    [9, 8, [5, 4]],
    [16, 8, [8, 8]],
    [17, 8, [6, 6, 5]],
    [6, 6, [6]],
    [10, 10, [10]],
    [0, 8, []],
]);

test('random seeding is stable across reprints', function () {
    $this->event->update(['config' => ReportFixtures::config(['race_sheet' => ['heat_size' => 20, 'seeding' => 'random']])]);
    foreach (range(1, 12) as $i) {
        ReportFixtures::entrant($this->event, '500M', 9, 'Boy');
    }

    $first = $this->builder->build($this->event->fresh())->heats[0]->registrationIds;
    $second = $this->builder->build($this->event->fresh())->heats[0]->registrationIds;

    expect($first)->toBe($second)->toHaveCount(12)
        ->and($first)->not->toBe(collect($first)->sort()->values()->all()); // actually shuffled
});

test('events without a race_sheet get start lists with the same grouping', function () {
    $config = ReportFixtures::config();
    unset($config['race_sheet']);
    $this->event->update(['config' => $config]);
    foreach (range(1, 10) as $i) {
        ReportFixtures::entrant($this->event, '500M', 9, 'Boy');
    }

    expect(fn () => $this->builder->build($this->event))->toThrow(DomainException::class);

    $sheet = $this->builder->startList($this->event);
    expect($sheet->heats)->toHaveCount(1)
        ->and($sheet->heats[0]->registrationIds)->toHaveCount(10);

    $this->actingAs($this->owner)->get(route('reports.race-sheet', $this->event))
        ->assertOk()
        ->assertSee('Start lists');
});

test('the race sheet page lists heats with their entrants', function () {
    $r = ReportFixtures::entrant($this->event, '1000M', 11, 'Girl', ['participant_name' => 'Meera Iyer']);

    $this->actingAs($this->owner)->get(route('reports.race-sheet', $this->event))
        ->assertOk()
        ->assertSee('1000 m · 10-12 · Girl')
        ->assertSee('Heat 1')
        ->assertSee('Meera Iyer')
        ->assertSee($r->pass_no);
});

test('CSV has group columns, heat and position, and neutralises formulas', function () {
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['participant_name' => 'Arjun']);
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['participant_name' => '=cmd()', 'checkedIn' => true]);

    $response = $this->actingAs($this->owner)->get(route('reports.race-sheet.csv', $this->event));

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $response->streamedContent()))));

    expect($rows[0])->toBe(['Distance', 'Age band', 'Gender', 'Heat', 'Position', 'Pass no', 'Name', 'Age', 'Checked in'])
        ->and($rows)->toHaveCount(3)
        ->and(array_slice($rows[1], 0, 5))->toBe(['500 m', '8-10', 'Boy', '1', '1'])
        ->and($rows[1][6])->toBe('Arjun')
        ->and($rows[2][6])->toBe("'=cmd()")
        ->and($rows[2][8])->toBe('yes');
});

test('the print view downloads an A4 PDF', function () {
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy');

    $response = $this->actingAs($this->owner)->get(route('reports.race-sheet.pdf', $this->event));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});
