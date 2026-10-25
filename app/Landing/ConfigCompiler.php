<?php

namespace App\Landing;

use App\Contracts\SportPackRepository;
use App\Enums\Sport;
use App\Support\EventSchema;
use Carbon\CarbonImmutable;

/**
 * Turns an authored event config (YAML file or events.config) into a validated EventConfig:
 * fills sport pack defaults, pins dates without an offset to IST, then checks events/schema.json.
 */
final class ConfigCompiler
{
    public const TIMEZONE = 'Asia/Kolkata';

    public function __construct(private SportPackRepository $packs) {}

    /**
     * @param  array<string, mixed>  $authored
     * @return array<string, mixed> Config with pack defaults filled in
     *
     * @throws InvalidEventConfig
     */
    public function resolve(array $authored, string $source = 'event config'): array
    {
        $sport = Sport::tryFrom((string) ($authored['sport'] ?? Sport::Generic->value));
        $data = $sport ? $this->packs->get($sport)->applyTo($authored) : $authored;

        if ($errors = EventSchema::errors($data)) {
            throw new InvalidEventConfig($source, $errors);
        }

        $data['date'] = self::withOffset($data['date']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $authored
     *
     * @throws InvalidEventConfig
     */
    public function compile(array $authored, string $source = 'event config'): EventConfig
    {
        return EventConfig::fromArray($this->resolve($authored, $source));
    }

    /** "2027-01-17 05:30" (no offset) means IST, per events/schema.json. */
    public static function withOffset(string $date): string
    {
        if (preg_match('/([+-]\d{2}:\d{2}|Z)$/', $date)) {
            return $date;
        }

        return CarbonImmutable::parse($date, self::TIMEZONE)->toIso8601String();
    }
}
