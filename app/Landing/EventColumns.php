<?php

namespace App\Landing;

/**
 * Denormalised events columns derived from a config, so listings, payments and
 * check-in can read an event without parsing events.config.
 */
final class EventColumns
{
    /**
     * @param  array<string, mixed>  $authored  Stored as events.config
     * @return array<string, mixed>
     */
    public static function from(array $authored, EventConfig $config): array
    {
        $capacities = array_map(fn (Distance $d) => $d->capacity, $config->distances);
        $ages = $config->ageBands;

        return [
            'slug' => $config->slug,
            'name' => $config->name,
            'sport' => $config->sport,
            'template_id' => $config->template,
            'certificate_template_id' => $config->certificate?->template,
            'date' => $config->date,
            'venue' => $config->venue,
            'price_paise' => min(array_map(fn (Distance $d) => $d->pricePaise(), $config->distances)),
            'capacity' => in_array(null, $capacities, true) ? null : array_sum($capacities),
            'distances' => array_map(fn (Distance $d) => (array) $d, $config->distances),
            'min_age' => $ages ? min(array_map(fn (AgeBand $b) => $b->min, $ages)) : null,
            'max_age' => $ages ? max(array_map(fn (AgeBand $b) => $b->max, $ages)) : null,
            'colours' => ['primary' => $config->branding->primary, 'secondary' => $config->branding->secondary],
            'form_fields' => ['custom' => array_map(fn (FormField $f) => (array) $f, $config->fields)],
            'config' => $authored,
        ];
    }
}
