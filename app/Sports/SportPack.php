<?php

namespace App\Sports;

use App\Enums\Sport;
use App\RaceSheets\RaceSheetConfig;

/**
 * Sport pack defaults (events/sports/<sport>.yaml).
 *
 * Merge rule: an event config inherits each top-level key it does not set
 * (template, distances, age_bands, fields, race_sheet, certificate). Keys the event sets
 * replace the pack's value entirely; lists are never merged item by item.
 * Pack distances carry no price, so an event that inherits them fails
 * events/schema.json until the organiser prices each distance.
 */
final readonly class SportPack
{
    public const INHERITED_KEYS = ['template', 'distances', 'age_bands', 'fields', 'race_sheet', 'certificate'];

    /**
     * @param  array<string, mixed>  $defaults  Partial event config (keys in INHERITED_KEYS)
     */
    public function __construct(
        public Sport $sport,
        public string $label,
        public array $defaults,
    ) {}

    /** @param  array<string, mixed>  $data  Decoded YAML matching events/sports/schema.json */
    public static function fromArray(array $data): self
    {
        return new self(
            sport: Sport::from($data['sport']),
            label: $data['label'],
            defaults: array_intersect_key($data, array_flip(self::INHERITED_KEYS)),
        );
    }

    public function raceSheet(): ?RaceSheetConfig
    {
        return isset($this->defaults['race_sheet'])
            ? RaceSheetConfig::fromArray($this->defaults['race_sheet'])
            : null;
    }

    /**
     * @param  array<string, mixed>  $event  Event config as authored
     * @return array<string, mixed> Event config with pack defaults filled in
     */
    public function applyTo(array $event): array
    {
        return $event + $this->defaults;
    }
}
