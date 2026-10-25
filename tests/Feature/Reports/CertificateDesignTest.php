<?php

use App\Certificates\DomPdfCertificateRenderer;
use App\Certificates\IssueCertificate;
use App\Certificates\ReissueEventCertificates;
use App\Landing\EventForm;
use App\Models\IssuedCertificate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->event = ReportFixtures::event();
    $this->owner = ReportFixtures::owner($this->event);
    $this->templates = app(DomPdfCertificateRenderer::class);
});

dataset('premium templates', [
    'heritage-gold landscape' => ['heritage-gold', 'landscape'],
    'heritage-gold portrait' => ['heritage-gold', 'portrait'],
    'modern-minimal landscape' => ['modern-minimal', 'landscape'],
    'modern-minimal portrait' => ['modern-minimal', 'portrait'],
    'midnight-elite' => ['midnight-elite', 'landscape'],
    'stadium-strip' => ['stadium-strip', 'landscape'],
    'academic-seal landscape' => ['academic-seal', 'landscape'],
    'academic-seal portrait' => ['academic-seal', 'portrait'],
]);

dataset('gallery', ['heritage-gold', 'modern-minimal', 'midnight-elite', 'stadium-strip', 'academic-seal']);

function certEntrant($event, array $answers = [], string $name = 'Meera Iyer')
{
    $r = ReportFixtures::entrant($event, '1000M', 11, 'Girl', ['participant_name' => $name, 'checkedIn' => true]);
    $r->update(['answers' => array_merge((array) $r->answers, $answers)]);

    return $r->fresh();
}

function pdfPageCount(string $pdf): int
{
    return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
}

test('the gallery ships exactly five premium templates', function () {
    expect(array_keys(config('eventflow.certificates.gallery')))
        ->toBe(['heritage-gold', 'modern-minimal', 'midnight-elite', 'stadium-strip', 'academic-seal']);
});

test('each template issues a one-page, print-ready PDF with every placeholder', function (string $template, string $orientation) {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => $template, 'orientation' => $orientation, 'credential' => 'merit']])]);
    $r = certEntrant($this->event, ['bib' => '417', 'finish_time' => '00:04:12', 'rank' => '3']);

    $file = $this->templates->issue($r);
    $pdf = Storage::disk('local')->get($file->pdfPath);
    $html = $this->templates->html($r);
    $certNo = IssuedCertificate::for($r)->cert_no;

    expect(substr($pdf, 0, 4))->toBe('%PDF')
        ->and(strlen($pdf))->toBeGreaterThan(20_000)       // fonts, textures and QR embedded, not a blank sheet
        ->and(pdfPageCount($pdf))->toBe(1)
        ->and($pdf)->toContain('/MediaBox')
        ->and($pdf)->toContain('Inter')                     // bundled fonts, not the default serif
        ->and($html)->toContain("certificate--{$template}")
        ->and($html)->toContain('Meera Iyer')
        ->and($html)->toContain('Fixture Skating Open')
        ->and($html)->toContain('Certificate of Merit')
        ->and($html)->toContain('07 February 2027')
        ->and($html)->toContain('00:04:12')
        ->and($html)->toContain('417')
        ->and($html)->toContain($certNo)
        ->and($html)->toContain('data:image/png;base64,');   // verify QR
})->with('premium templates');

test('portrait PDFs are A4 portrait and landscape PDFs A4 landscape', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'academic-seal', 'orientation' => 'portrait']])]);
    $pdf = Storage::disk('local')->get($this->templates->issue(certEntrant($this->event))->pdfPath);
    expect($pdf)->toMatch('#/MediaBox \[0(\.0+)? 0(\.0+)? 595\.2\d* 841\.8\d*\]#');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'stadium-strip']])]);
    $pdf = Storage::disk('local')->get($this->templates->issue(certEntrant($this->event, [], 'Arjun Rao'))->pdfPath);
    expect($pdf)->toMatch('#/MediaBox \[0(\.0+)? 0(\.0+)? 841\.8\d* 595\.2\d*\]#');
});

test('the organiser logo is printed, and a missing logo falls back to the organiser name or initials', function () {
    Storage::disk('public')->put('branding/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'modern-minimal'], 'branding' => ['primary' => '#1d3b8b', 'secondary' => '#e8553f', 'logo' => 'branding/logo.png']])]);
    expect($this->templates->html(certEntrant($this->event)))->toContain('data:image/png;base64,iVBOR');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'modern-minimal']])]);
    expect($this->templates->html(certEntrant($this->event, [], 'Arjun Rao')))->toContain(e($this->event->org->name));
});

test('neutral palettes work without brand colours, and brand colours are used when set', function () {
    $r = certEntrant($this->event);

    foreach (['gold' => '#9a7425', 'slate' => '#475569', 'ink' => '#1e3a5f'] as $palette => $accent) {
        $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'academic-seal', 'palette' => $palette]])]);
        expect($this->templates->data($r->fresh(), 'EF-CERT-TEST0000')->palette->accent)->toBe($accent);
    }

    $this->event->update(['colours' => ['primary' => '#1d3b8b', 'secondary' => '#e8553f'], 'config' => ReportFixtures::config(['certificate' => ['template' => 'stadium-strip', 'palette' => 'brand']])]);
    expect($this->templates->data($r->fresh(), 'EF-CERT-TEST0000')->palette->accentFill)->toBe('#1d3b8b');
});

test('midnight elite has a print-safe light variant', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'midnight-elite', 'variant' => 'print']])]);
    expect($this->templates->html(certEntrant($this->event)))->toContain('certificate--print')->toContain('style="background: #f9f7f1;"');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'midnight-elite']])]);
    expect($this->templates->html(certEntrant($this->event, [], 'Arjun Rao')))->toContain('certificate--screen');
});

test('signatures print the names and roles given, or two blank default slots', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'heritage-gold', 'signatories' => [['name' => 'Arjun Mehta', 'role' => 'Race Director'], ['role' => 'Head Coach']]]])]);
    expect($this->templates->html(certEntrant($this->event)))->toContain('Arjun Mehta')->toContain('Race Director')->toContain('Head Coach');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'heritage-gold']])]);
    expect($this->templates->html(certEntrant($this->event, [], 'Arjun Rao')))->toContain('Race Director')->toContain('Chief Guest');
});

test('the stamp can be switched off', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'modern-minimal']])]);
    $r = certEntrant($this->event);
    expect($this->templates->html($r))->toContain('data-part="stamp"');

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'modern-minimal', 'stamp' => false]])]);
    expect($this->templates->html($r->fresh()))->not->toContain('data-part="stamp"');
});

test('a stored certificate is reused until something printed on it changes', function () {
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'heritage-gold']])]);
    $r = certEntrant($this->event);

    $first = $this->templates->issue($r);
    $issuedAt = IssuedCertificate::for($r)->issued_at;
    $this->travel(5)->minutes();

    expect($this->templates->issue($r->fresh())->pdfPath)->toBe($first->pdfPath)
        ->and(IssuedCertificate::for($r)->issued_at->equalTo($issuedAt))->toBeTrue();

    $r->update(['participant_name' => 'Meera S. Iyer']); // observer re-issues (sync queue)
    $certificate = IssuedCertificate::for($r);

    expect($certificate->issued_at->greaterThan($issuedAt))->toBeTrue()
        ->and($this->templates->isCurrent($r->fresh(), $certificate))->toBeTrue()
        ->and($certificate->pdf_path)->toBe($first->pdfPath);                   // same EF-CERT number, same file
});

test('renames and design changes queue re-issues for issued certificates only', function () {
    $r = certEntrant($this->event);
    $other = certEntrant($this->event, [], 'Arjun Rao');
    $this->templates->issue($r);

    Queue::fake();
    $r->update(['participant_name' => 'Meera S. Iyer']);
    $other->update(['participant_name' => 'Arjun K. Rao']); // never issued: nothing to refresh
    Queue::assertPushed(IssueCertificate::class, fn ($job) => $job->registrationId === $r->id);
    Queue::assertNotPushed(IssueCertificate::class, fn ($job) => $job->registrationId === $other->id);

    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => 'midnight-elite']])]);
    Queue::assertPushed(ReissueEventCertificates::class, fn ($job) => $job->eventId === $this->event->id);
});

test('organisers can re-issue one participant', function () {
    Queue::fake();
    $r = certEntrant($this->event);

    $this->actingAs($this->owner)->post(route('reports.certificates.reissue', [$this->event, $r]))
        ->assertRedirect()
        ->assertSessionHas('status');

    Queue::assertPushed(IssueCertificate::class, fn ($job) => $job->registrationId === $r->id && $job->force);
});

test('the certificates page lists participants with their certificate numbers', function () {
    $r = certEntrant($this->event);
    certEntrant($this->event, [], 'Arjun Rao');
    $this->templates->issue($r);

    $this->actingAs($this->owner)->get(route('reports.certificates', $this->event))
        ->assertOk()
        ->assertSee('Heritage Gold')
        ->assertSee('Midnight Elite')
        ->assertSee('Download sample')
        ->assertSee('Contact Indefine')
        ->assertSee('Meera Iyer')
        ->assertSee(IssuedCertificate::for($r)->cert_no)
        ->assertSee('Not issued yet');

    $this->actingAs($this->owner)->get(route('reports.certificates', [$this->event, 'q' => 'Arjun']))
        ->assertSee('Arjun Rao')
        ->assertDontSee('Meera Iyer');
});

test('anyone can verify an issued certificate by its number', function () {
    $r = certEntrant($this->event);
    $this->templates->issue($r);
    $certNo = IssuedCertificate::for($r)->cert_no;

    $this->get(route('certificates.verify', $certNo))
        ->assertOk()
        ->assertSee('Valid certificate')
        ->assertSee('Meera Iyer')
        ->assertSee('Fixture Skating Open')
        ->assertDontSee($r->email);

    $this->get('/certificates/verify/EF-CERT-ZZZZZZZZ')->assertNotFound();
});

test('a certificate number is not verifiable before it is issued', function () {
    $certNo = IssuedCertificate::for(certEntrant($this->event))->cert_no; // number reserved, no PDF yet

    $this->get(route('certificates.verify', $certNo))->assertNotFound();
});

test('each template has a thumbnail and a sample PDF with placeholder data', function (string $template) {
    $this->actingAs($this->owner)->get(route('certificates.thumbnail', $template))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $inline = $this->actingAs($this->owner)->get(route('reports.certificates.preview', [$this->event, $template]));
    $inline->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($inline->getContent(), 0, 4))->toBe('%PDF')->and(strlen($inline->getContent()))->toBeGreaterThan(20_000);

    $this->actingAs($this->owner)->get(route('reports.certificates.preview', [$this->event, $template, 'download' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$template.'-sample.pdf"');

    expect(IssuedCertificate::query()->count())->toBe(0); // samples never issue a number
})->with('gallery');

test('unknown templates have no thumbnail or sample', function () {
    $this->actingAs($this->owner)->get(route('certificates.thumbnail', 'fancy'))->assertNotFound();
    $this->actingAs($this->owner)->get(route('reports.certificates.preview', [$this->event, 'fancy']))->assertNotFound();
});

test('organisers save a design with palette, credential, signatures and stamp', function () {
    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'midnight-elite',
        'orientation' => 'landscape',
        'palette' => 'gold',
        'credential' => 'participation',
        'variant' => 'print',
        'stamp' => '0',
        'signatories' => [['name' => 'Arjun Mehta', 'role' => 'Race Director'], ['name' => '', 'role' => 'Chief Guest']],
    ])->assertRedirect(route('reports.certificates', $this->event))->assertSessionHasNoErrors();

    $event = $this->event->fresh();
    expect($event->config['certificate'])->toEqual([
        'template' => 'midnight-elite',
        'orientation' => 'landscape',
        'palette' => 'gold',
        'credential' => 'participation',
        'variant' => 'print',
        'stamp' => false,
        'signatories' => [['name' => 'Arjun Mehta', 'role' => 'Race Director'], ['role' => 'Chief Guest']],
    ])->and($event->certificate_template_id)->toBe('midnight-elite');
});

test('saving the event in the editor keeps the certificate design options', function () {
    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'heritage-gold', 'orientation' => 'portrait', 'palette' => 'slate', 'credential' => 'merit',
        'signatories' => [['name' => 'Arjun Mehta', 'role' => 'Race Director']],
    ]);
    $config = $this->event->fresh()->config;

    $form = EventForm::toConfig(
        EventForm::toInput($config) + ['certificate' => ['template' => 'heritage-gold', 'orientation' => 'portrait']],
        $this->event->org->slug, $this->event->slug, $config,
    );

    expect($form['certificate'])->toMatchArray(['palette' => 'slate', 'credential' => 'merit', 'signatories' => [['name' => 'Arjun Mehta', 'role' => 'Race Director']]]);
});

test('an A4 background at the exact pixel size is stored and used', function () {
    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'upload',
        'orientation' => 'landscape',
        'background' => UploadedFile::fake()->image('artwork.png', 3508, 2480),
    ])->assertSessionHasNoErrors();

    $settings = $this->templates->settings($this->event->fresh());
    expect($settings->isUpload())->toBeTrue()
        ->and(Storage::disk('local')->exists($settings->background))->toBeTrue();

    $html = $this->templates->html(certEntrant($this->event));
    expect($html)->toContain('certificate--upload')->toContain('left: 18.50mm') // name slot centred at x 148.5mm (260mm box)
        ->toContain('EF-CERT-');                                                  // verify slot
});

test('a background at the wrong size is rejected with a clear message', function (string $orientation, int $w, int $h) {
    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'upload',
        'orientation' => $orientation,
        'background' => UploadedFile::fake()->image('artwork.png', $w, $h),
    ])->assertSessionHasErrors('background');

    expect(session('errors')->first('background'))->toContain('must be exactly')->toContain("Yours is {$w} × {$h} px")
        ->and($this->event->fresh()->config['certificate']['template'])->not->toBe('upload');
})->with([
    'too small' => ['landscape', 1754, 1240],
    'portrait size for landscape' => ['landscape', 2480, 3508],
    'landscape size for portrait' => ['portrait', 3508, 2480],
]);

test('upload needs a PNG or JPG file', function () {
    $this->actingAs($this->owner)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'upload', 'orientation' => 'landscape'])
        ->assertSessionHasErrors('background');

    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'upload', 'orientation' => 'landscape',
        'background' => UploadedFile::fake()->create('artwork.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('background');
});

test('unknown templates and options are rejected', function () {
    $this->actingAs($this->owner)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'fancy', 'orientation' => 'landscape', 'palette' => 'neon'])
        ->assertSessionHasErrors(['template', 'palette']);
});

test('another org cannot change the design or re-issue', function () {
    $other = ReportFixtures::owner(ReportFixtures::event(ReportFixtures::orgWithPass()));
    $r = certEntrant($this->event);

    $this->actingAs($other)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'academic-seal', 'orientation' => 'landscape'])
        ->assertNotFound();
    $this->actingAs($other)->post(route('reports.certificates.reissue', [$this->event, $r]))->assertNotFound();
});
