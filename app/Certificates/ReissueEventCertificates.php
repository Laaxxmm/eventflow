<?php

namespace App\Certificates;

use App\Models\IssuedCertificate;
use App\Models\Tenancy\OrgScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * After an event's design, branding or details change, refresh every
 * certificate already issued for it. Each one is only re-rendered when its
 * printed content actually changed (fingerprint check in the renderer).
 */
final class ReissueEventCertificates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public readonly int $eventId) {}

    public function handle(): void
    {
        IssuedCertificate::withoutGlobalScope(OrgScope::class)
            ->where('event_id', $this->eventId)
            ->whereNotNull('issued_at')
            ->pluck('registration_id')
            ->each(fn (int $id) => IssueCertificate::dispatch($id));
    }
}
