<?php

namespace App\Certificates;

use App\Contracts\CertificateRenderer;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Tenancy\CurrentOrg;
use App\Models\Tenancy\OrgScope;
use App\Payments\PaymentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Renders a certificate for every checked-in, paid runner of an event and
 * stores them as one ZIP. Progress goes to CertificateBatch.
 */
final class BuildCertificateZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly int $eventId) {}

    public function handle(CertificateRenderer $renderer, DomPdfCertificateRenderer $settings): void
    {
        $event = Event::withoutGlobalScope(OrgScope::class)->findOrFail($this->eventId);

        app(CurrentOrg::class)->run($event->org_id, function () use ($event, $renderer, $settings) {
            CertificateBatch::put($event->id, 'running');

            $disk = Storage::disk($settings->disk());
            $tmp = tempnam(sys_get_temp_dir(), 'certs');
            $zip = new ZipArchive;

            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the certificate ZIP.');
            }

            $count = 0;
            foreach (self::eligible($event)->lazyById(200) as $registration) {
                $file = $renderer->render($registration);
                $zip->addFromString(
                    $registration->pass_no.'-'.Str::slug($registration->participant_name).'.pdf',
                    $disk->get($file->pdfPath),
                );
                $count++;
            }

            $zip->close();

            $path = 'certificates/'.$event->id.'/'.$event->slug.'-certificates.zip';
            $stream = fopen($tmp, 'r');
            $disk->put($path, $stream);
            is_resource($stream) && fclose($stream);
            @unlink($tmp);

            CertificateBatch::put($event->id, 'ready', ['count' => $count, 'path' => $path]);
        });
    }

    public function failed(Throwable $e): void
    {
        CertificateBatch::put($this->eventId, 'failed', ['message' => $e->getMessage()]);
    }

    /** Checked-in, paid registrations: the runners who get a certificate. */
    public static function eligible(Event $event): Builder
    {
        return Registration::query()
            ->whereBelongsTo($event)
            ->where('status', PaymentStatus::Paid)
            ->whereNotNull('checked_in_at');
    }
}
