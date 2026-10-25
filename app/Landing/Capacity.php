<?php

namespace App\Landing;

use App\Models\Event;
use App\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Places taken per distance: paid registrations, plus pending ones still inside the
 * payment hold window (an abandoned checkout frees its place after the hold).
 */
final class Capacity
{
    public const PENDING_HOLD_MINUTES = 30;

    public static function taken(Event $event, ?string $distanceCode = null): int
    {
        return $event->registrations()
            ->when($distanceCode !== null, fn (Builder $q) => $q->where('distance_code', $distanceCode))
            ->where(fn (Builder $q) => $q
                ->where('status', PaymentStatus::Paid)
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', PaymentStatus::Pending)
                    ->where('created_at', '>=', now()->subMinutes(self::PENDING_HOLD_MINUTES))))
            ->count();
    }

    public static function isFull(Event $event, Distance $distance): bool
    {
        if ($distance->capacity !== null && self::taken($event, $distance->code) >= $distance->capacity) {
            return true;
        }

        return $event->capacity !== null && self::taken($event) >= $event->capacity;
    }

    /** @return array<string, int|null> Remaining places by distance code (null = unlimited) */
    public static function remaining(Event $event, EventConfig $config): array
    {
        $remaining = [];

        foreach ($config->distances as $distance) {
            $remaining[$distance->code] = $distance->capacity === null
                ? null
                : max(0, $distance->capacity - self::taken($event, $distance->code));
        }

        return $remaining;
    }
}
