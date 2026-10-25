<?php

namespace App\Landing;

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
    ) {}

    /**
     * @param  array<string, mixed>  $data  Decoded YAML/JSON matching events/schema.json
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
        );
    }
}
