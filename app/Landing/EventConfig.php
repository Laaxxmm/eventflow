<?php

namespace App\Landing;

use App\Enums\Sport;
use App\RaceSheets\RaceSheetConfig;
use Carbon\CarbonImmutable;

/**
 * Typed view of an event landing config. Shape is defined by events/schema.json.
 */
final readonly class EventConfig
{
    /**
     * @param  list<Distance>  $distances
     * @param  list<AgeBand>  $ageBands
     * @param  list<FormField>  $fields
     * @param  list<Faq>  $faq
     * @param  list<array{name: string, logo?: ?string, url?: ?string, tier?: ?string}>  $sponsors
     * @param  list<array{time: string, label: string}>  $schedule
     */
    public function __construct(
        public string $org,
        public string $slug,
        public string $name,
        public CarbonImmutable $date,
        public string $venue,
        public Branding $branding,
        public Hero $hero,
        public array $distances,
        public array $ageBands = [],
        public array $fields = [],
        public array $faq = [],
        public ?Certificate $certificate = null,
        public Sport $sport = Sport::Generic,
        public ?RaceSheetConfig $raceSheet = null,
        public string $template = self::DEFAULT_TEMPLATE,
        public array $sponsors = [],
        public array $schedule = [],
        public ?string $charity = null,
    ) {}

    /** Landing template used when the event and its sport pack set none (or an unknown key). */
    public const DEFAULT_TEMPLATE = 'gun-lap';

    /**
     * @param  array<string, mixed>  $data  Decoded YAML/JSON matching events/schema.json,
     *                                      after SportPack::applyTo() has filled in defaults
     */
    public static function fromArray(array $data): self
    {
        return new self(
            org: $data['org'],
            slug: $data['slug'],
            name: $data['name'],
            date: CarbonImmutable::parse($data['date']),
            venue: $data['venue'],
            branding: new Branding(...$data['branding']),
            hero: new Hero(...$data['hero']),
            distances: array_map(fn (array $d) => new Distance(...$d), $data['distances']),
            ageBands: array_map(fn (array $b) => new AgeBand(...$b), $data['age_bands'] ?? []),
            fields: array_map(fn (array $f) => new FormField(...$f), $data['fields'] ?? []),
            faq: array_map(fn (array $f) => new Faq(...$f), $data['faq'] ?? []),
            certificate: isset($data['certificate']) ? new Certificate(...$data['certificate']) : null,
            sport: Sport::from($data['sport'] ?? Sport::Generic->value),
            raceSheet: isset($data['race_sheet']) ? RaceSheetConfig::fromArray($data['race_sheet']) : null,
            template: self::resolveTemplate($data['template'] ?? null),
            sponsors: array_values(array_filter($data['sponsors'] ?? [], fn ($s) => is_array($s) && filled($s['name'] ?? null))),
            schedule: array_values(array_filter($data['schedule'] ?? [], fn ($s) => is_array($s) && filled($s['time'] ?? null))),
            charity: filled($data['charity'] ?? null) ? (string) $data['charity'] : null,
        );
    }

    /** A landing template key: aliases mapped to their successor, unknown keys to the default. */
    public static function resolveTemplate(mixed $key): string
    {
        $key = (string) $key;
        $key = (string) config("eventflow.landing_template_aliases.{$key}", $key);

        return array_key_exists($key, (array) config('eventflow.landing_templates')) ? $key : self::DEFAULT_TEMPLATE;
    }
}
