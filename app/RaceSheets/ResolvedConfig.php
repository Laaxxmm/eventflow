<?php

namespace App\RaceSheets;

use App\Contracts\SportPackRepository;
use App\Enums\Sport;
use App\Models\Event;

/**
 * An event's config with its sport pack's defaults applied (SportPack::applyTo).
 *
 * events.config holds the authored YAML; keys the event omits (age_bands,
 * fields, race_sheet, certificate) come from events/sports/<sport>.yaml.
 * Race sheets and certificates read this, never events.config directly.
 */
final class ResolvedConfig
{
    /** @return array<string, mixed> */
    public static function for(Event $event): array
    {
        $authored = is_array($event->config) ? $event->config : [];
        $sport = $event->sport instanceof Sport
            ? $event->sport
            : Sport::tryFrom((string) ($authored['sport'] ?? Sport::Generic->value));

        if ($sport === null || ! app()->bound(SportPackRepository::class)) {
            return $authored;
        }

        return app(SportPackRepository::class)->get($sport)->applyTo($authored);
    }
}
