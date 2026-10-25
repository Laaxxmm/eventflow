<?php

namespace App\RaceSheets;

use App\Contracts\RaceSheetBuilder;
use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use DomainException;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Splits an event's paid entrants into heats from its race_sheet config.
 *
 * - Groups by race_sheet.group_by (distance, age_band, gender or any form field
 *   key); heats never mix groups. Groups follow the config's distance and age
 *   band order, then alphabetical.
 * - Splits each group evenly: 9 entrants with heat_size 8 give heats of 5 and 4.
 * - Seeding: registration_order, or random with the event id as seed so a
 *   reprint gives the same heats.
 */
final class HeatRaceSheetBuilder implements RaceSheetBuilder
{
    public const DEFAULT_GROUP_BY = ['distance', 'age_band', 'gender'];

    public function build(Event $event): RaceSheet
    {
        $config = ResolvedConfig::for($event)['race_sheet'] ?? null;

        if (! is_array($config)) {
            throw new DomainException("Event {$event->id} has no race_sheet config.");
        }

        return $this->buildWith($event, RaceSheetConfig::fromArray($config));
    }

    /**
     * Start lists for mass-start events (no race_sheet): same grouping, one
     * "heat" per group.
     */
    public function startList(Event $event): RaceSheet
    {
        return $this->buildWith($event, new RaceSheetConfig(heatSize: PHP_INT_MAX, groupBy: self::DEFAULT_GROUP_BY));
    }

    public function buildOrStartList(Event $event): RaceSheet
    {
        return is_array(ResolvedConfig::for($event)['race_sheet'] ?? null) ? $this->build($event) : $this->startList($event);
    }

    public function buildWith(Event $event, RaceSheetConfig $config): RaceSheet
    {
        $entrants = Registration::query()
            ->whereBelongsTo($event)
            ->where('status', PaymentStatus::Paid)
            ->orderBy('id')
            ->get(['id', 'distance_code', 'answers']);

        $groups = $entrants
            ->groupBy(fn (Registration $r) => json_encode($this->groupValues($event, $config, $r)))
            ->sortBy(fn (Collection $members, string $key) => $this->sortKey($event, json_decode($key, true)))
            ->all();

        $heats = [];
        foreach ($groups as $key => $members) {
            $ids = $members->pluck('id')->values()->all();
            if ($config->seeding === 'random') {
                // Seeded by the event, so a reprint gives the same heats.
                $ids = (new Randomizer(new Mt19937($event->id)))->shuffleArray($ids);
            }

            foreach ($this->split($ids, $config->heatSize) as $i => $heatIds) {
                $heats[] = new Heat(group: $this->labels($event, json_decode($key, true)), number: $i + 1, registrationIds: $heatIds);
            }
        }

        return new RaceSheet(eventId: $event->id, config: $config, heats: $heats);
    }

    /**
     * Even split: ceil(n / size) heats whose sizes differ by at most one.
     *
     * @param  list<int>  $ids
     * @return list<list<int>>
     */
    public function split(array $ids, int $heatSize): array
    {
        $count = count($ids);
        if ($count === 0) {
            return [];
        }

        $heats = (int) ceil($count / max(1, $heatSize));
        $base = intdiv($count, $heats);
        $extra = $count % $heats;

        $out = [];
        $offset = 0;
        for ($h = 0; $h < $heats; $h++) {
            $size = $base + ($h < $extra ? 1 : 0);
            $out[] = array_slice($ids, $offset, $size);
            $offset += $size;
        }

        return $out;
    }

    /**
     * Raw group values (codes) for one entrant, keyed by group_by key.
     *
     * @return array<string, string>
     */
    private function groupValues(Event $event, RaceSheetConfig $config, Registration $r): array
    {
        $answers = $r->answers ?? [];
        $values = [];

        foreach ($config->groupBy as $key) {
            $values[$key] = match ($key) {
                'distance' => (string) ($r->distance_code ?? ''),
                'age_band' => $this->ageBand($event, $answers['age'] ?? null) ?? '',
                default => is_scalar($answers[$key] ?? null) ? trim((string) $answers[$key]) : '',
            };
        }

        return $values;
    }

    /**
     * Display labels for a group: distance label from the config, '' as "Unspecified".
     *
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function labels(Event $event, array $values): array
    {
        $distances = collect($this->distances($event))->keyBy('code');

        return collect($values)->map(fn (string $value, string $key) => match (true) {
            $value === '' => __('Unspecified'),
            $key === 'distance' => (string) ($distances[$value]['label'] ?? $value),
            default => $value,
        })->all();
    }

    /**
     * @param  array<string, string>  $values
     * @return list<int|string>
     */
    private function sortKey(Event $event, array $values): array
    {
        $distanceOrder = array_flip(array_column($this->distances($event), 'code'));
        $bandOrder = array_flip(array_column($this->ageBands($event), 'label'));

        return collect($values)->map(fn (string $value, string $key) => match ($key) {
            'distance' => $distanceOrder[$value] ?? PHP_INT_MAX,
            'age_band' => $bandOrder[$value] ?? PHP_INT_MAX,
            default => $value === '' ? "\u{10FFFF}" : mb_strtolower($value),
        })->values()->all();
    }

    private function ageBand(Event $event, mixed $age): ?string
    {
        if (! is_numeric($age)) {
            return null;
        }

        foreach ($this->ageBands($event) as $band) {
            if ((int) $age >= (int) $band['min'] && (int) $age <= (int) $band['max']) {
                return (string) $band['label'];
            }
        }

        return null;
    }

    /** @return list<array{code: string, label: string}> */
    private function distances(Event $event): array
    {
        return array_values(ResolvedConfig::for($event)['distances'] ?? $event->distances ?? []);
    }

    /** @return list<array{label: string, min: int, max: int}> */
    private function ageBands(Event $event): array
    {
        return array_values(ResolvedConfig::for($event)['age_bands'] ?? []);
    }
}
