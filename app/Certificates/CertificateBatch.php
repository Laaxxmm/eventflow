<?php

namespace App\Certificates;

use Illuminate\Support\Facades\Cache;

/**
 * Status of an event's bulk certificate ZIP, kept in the cache (no table needed):
 * state queued|running|ready|failed, plus count, path, message and timestamps.
 */
final class CertificateBatch
{
    public const TTL_SECONDS = 7 * 24 * 3600;

    /** @return array{state: string, count?: int, path?: string, message?: string, updated_at?: string}|null */
    public static function get(int $eventId): ?array
    {
        return Cache::get(self::key($eventId));
    }

    /** @param  array<string, mixed>  $data */
    public static function put(int $eventId, string $state, array $data = []): void
    {
        Cache::put(self::key($eventId), ['state' => $state, ...$data, 'updated_at' => now()->toIso8601String()], self::TTL_SECONDS);
    }

    public static function inProgress(int $eventId): bool
    {
        return in_array(self::get($eventId)['state'] ?? null, ['queued', 'running'], true);
    }

    private static function key(int $eventId): string
    {
        return "eventflow.certificates.{$eventId}";
    }
}
