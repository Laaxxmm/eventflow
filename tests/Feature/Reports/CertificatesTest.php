<?php

use App\Certificates\BuildCertificateZip;
use App\Certificates\CertificateBatch;
use App\Certificates\DomPdfCertificateRenderer;
use App\Contracts\CertificateRenderer;
use App\Payments\PaymentStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    Storage::fake('local');
    $this->event = ReportFixtures::event();
    $this->owner = ReportFixtures::owner($this->event);
    $this->templates = app(DomPdfCertificateRenderer::class);
});

test('the certificate shows name, distance, event and date from the YAML template', function () {
    $r = ReportFixtures::entrant($this->event, '1000M', 11, 'Girl', ['participant_name' => 'Meera Iyer', 'checkedIn' => true]);

    $html = $this->templates->html($r);

    expect($html)->toContain('certificate--podium')   // certificate.template: podium
        ->toContain('Meera Iyer')
        ->toContain('1000 m')                          // distance label, not the code
        ->toContain('Fixture Skating Open')
        ->toContain('07 February 2027')
        ->toContain($r->pass_no);
});

test('rendering stores an A4 PDF per runner', function () {
    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    $file = app(CertificateRenderer::class)->render($r);

    expect($file->pdfPath)->toBe("certificates/{$this->event->id}/{$r->pass_no}.pdf")
        ->and(substr(Storage::disk('local')->get($file->pdfPath), 0, 4))->toBe('%PDF');
});

test('unknown templates fall back to classic, and unsupported orientations to the template default', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'neon']])]);
    expect($this->templates->settings($this->event)->template)->toBe('classic');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'podium', 'orientation' => 'portrait']])]);
    expect($this->templates->settings($this->event)->orientation)->toBe('landscape'); // podium is landscape-only
});

test('an uploaded A4 background is embedded behind the text', function () {
    Storage::disk('local')->put('certificates/backgrounds/open.png', file_get_contents(__DIR__.'/fixtures/a4-background.png'));
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'upload', 'background' => 'certificates/backgrounds/open.png']])]);
    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    expect($this->templates->html($r))->toContain('certificate--upload')->toContain('data:image/png;base64,');
    expect(substr(Storage::disk('local')->get(app(CertificateRenderer::class)->render($r)->pdfPath), 0, 4))->toBe('%PDF');
});

test('a missing uploaded background fails loudly', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'upload', 'background' => 'certificates/missing.png']])]);
    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    $this->templates->html($r);
})->throws(RuntimeException::class, 'certificates/missing.png');

test('one click queues the ZIP job once', function () {
    Queue::fake();
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    $this->actingAs($this->owner)->post(route('reports.certificates.generate', $this->event))
        ->assertRedirect(route('reports.certificates', $this->event));
    $this->actingAs($this->owner)->post(route('reports.certificates.generate', $this->event)); // double click

    Queue::assertPushed(BuildCertificateZip::class, 1);
    Queue::assertPushed(BuildCertificateZip::class, fn ($job) => $job->eventId === $this->event->id);
    expect(CertificateBatch::get($this->event->id)['state'])->toBe('queued');
});

test('nothing is queued without checked-in runners', function () {
    Queue::fake();
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy'); // paid, not checked in

    $this->actingAs($this->owner)->post(route('reports.certificates.generate', $this->event))
        ->assertSessionHas('status', fn ($s) => str_contains($s, 'No checked-in runners'));

    Queue::assertNothingPushed();
});

test('the job zips a PDF for every checked-in paid runner only', function () {
    $a = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['participant_name' => 'Arjun Rao', 'checkedIn' => true]);
    $b = ReportFixtures::entrant($this->event, '1000M', 11, 'Girl', ['participant_name' => 'Meera Iyer', 'checkedIn' => true]);
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy');                                                         // not checked in
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['status' => PaymentStatus::Refunded, 'checkedIn' => true]); // refunded

    (new BuildCertificateZip($this->event->id))->handle(app(CertificateRenderer::class), $this->templates);

    $batch = CertificateBatch::get($this->event->id);
    expect($batch['state'])->toBe('ready')->and($batch['count'])->toBe(2);

    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($zipFile, Storage::disk('local')->get($batch['path']));
    $zip = new ZipArchive;
    $zip->open($zipFile);
    $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();

    expect($names)->toBe(collect(["{$a->pass_no}-arjun-rao.pdf", "{$b->pass_no}-meera-iyer.pdf"])->sort()->values()->all())
        ->and(substr($zip->getFromIndex(0), 0, 4))->toBe('%PDF');

    $zip->close();
    unlink($zipFile);

    $this->actingAs($this->owner)->get(route('reports.certificates', $this->event))
        ->assertOk()
        ->assertSee('2 certificates ready')
        ->assertSee('Download ZIP');

    $download = $this->actingAs($this->owner)->get(route('reports.certificates.download', $this->event));
    $download->assertOk()->assertDownload("{$this->event->slug}-certificates.zip");
});

test('a failed job is shown on the page', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'upload', 'background' => 'certificates/missing.png']])]);
    ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    $this->actingAs($this->owner)->post(route('reports.certificates.generate', $this->event)); // sync queue in tests

    expect(CertificateBatch::get($this->event->id)['state'])->toBe('failed');
    $this->actingAs($this->owner)->get(route('reports.certificates', $this->event))
        ->assertSee('certificates/missing.png');
});

test('a single certificate downloads for a checked-in runner only', function () {
    $in = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);
    $out = ReportFixtures::entrant($this->event, '500M', 9, 'Boy');

    $this->actingAs($this->owner)->get(route('reports.certificates.show', [$this->event, $in]))
        ->assertOk()
        ->assertDownload("{$in->pass_no}-certificate.pdf");

    $this->actingAs($this->owner)->get(route('reports.certificates.show', [$this->event, $out]))->assertNotFound();
});

test('the ZIP cannot be downloaded before it is ready', function () {
    $this->actingAs($this->owner)->get(route('reports.certificates.download', $this->event))->assertNotFound();
});

test('light brand colours are not used for text on white', function () {
    $this->event->org->update(['colours' => ['primary' => '#d9ff45', 'secondary' => '#ff6e61']]); // prototype lime/coral
    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    $html = $this->templates->html($r);

    expect($html)->toContain('background: #d9ff45')                 // brand colour kept for the band
        ->toContain('.name { color: #17213a; }')                    // name falls back to ink
        ->toContain('.band .title { color: #17213a;')               // dark text on the light band
        ->and(DomPdfCertificateRenderer::contrast('#17213a', '#ffffff'))->toBeGreaterThan(4.5)
        ->and(DomPdfCertificateRenderer::contrast('#d9ff45', '#ffffff'))->toBeLessThan(4.5);
});
