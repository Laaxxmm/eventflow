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
     * @param  array<string, string>  $contact  Footer contact / links (CONTACT_KEYS)
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
        public ?CarbonImmutable $registrationCloses = null,
        public bool $countdown = false,
        public string $countdownTarget = self::COUNTDOWN_TARGETS[0],
        public array $contact = [],
    ) {}

    /** Countdown block targets (Session P1). */
    public const COUNTDOWN_TARGETS = ['registration_close', 'event_start'];

    /** Footer contact keys (Session P1). */
    public const CONTACT_KEYS = ['email', 'phone', 'website', 'instagram', 'facebook', 'x', 'youtube', 'terms_url', 'privacy_url'];

    /** Online registration closes at the earlier of registration_closes and the start. */
    public function closesAt(): CarbonImmutable
    {
        return $this->registrationCloses !== null && $this->registrationCloses->lessThan($this->date)
            ? $this->registrationCloses
            : $this->date;
    }

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
            registrationCloses: filled($data['registration_closes'] ?? null) ? CarbonImmutable::parse($data['registration_closes']) : null,
            countdown: (bool) ($data['countdown']['enabled'] ?? false),
            countdownTarget: in_array($data['countdown']['target'] ?? null, self::COUNTDOWN_TARGETS, true) ? $data['countdown']['target'] : self::COUNTDOWN_TARGETS[0],
            contact: array_filter(array_intersect_key((array) ($data['contact'] ?? []), array_flip(self::CONTACT_KEYS)), 'filled'),
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
