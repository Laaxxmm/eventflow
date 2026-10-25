<?php

namespace App\Certificates;

use App\Models\Registration;
use App\Models\Tenancy\CurrentOrg;
use App\Models\Tenancy\OrgScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Issue (or re-issue) one participant's certificate PDF. Without $force the
 * stored file is kept when nothing printed on it has changed.
 */
final class IssueCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(public readonly int $registrationId, public readonly bool $force = false) {}

    public function handle(DomPdfCertificateRenderer $renderer): void
    {
        $registration = Registration::withoutGlobalScope(OrgScope::class)->find($this->registrationId);
        if ($registration === null) {
            return;
        }

        app(CurrentOrg::class)->run($registration->org_id, fn () => $renderer->issue($registration, $this->force));
    }
}
