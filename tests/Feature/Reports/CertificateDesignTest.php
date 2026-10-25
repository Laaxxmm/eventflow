<?php

use App\Certificates\DomPdfCertificateRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->event = ReportFixtures::event();
    $this->owner = ReportFixtures::owner($this->event);
    $this->templates = app(DomPdfCertificateRenderer::class);
});

dataset('gallery', ['classic', 'podium', 'minimal', 'velocity', 'glide']);

test('the gallery has five designed templates', function () {
    expect(config('eventflow.certificates.gallery'))->toHaveCount(5)
        ->toHaveKeys(['classic', 'podium', 'minimal', 'velocity', 'glide']);
});

test('every gallery template prints all placeholders', function (string $template) {
    $orientation = config("eventflow.certificates.gallery.{$template}.orientations")[0];
    $this->event->update(['config' => ReportFixtures::config(['certificate' => ['template' => $template, 'orientation' => $orientation]])]);
    $r = ReportFixtures::entrant($this->event, '1000M', 11, 'Girl', ['participant_name' => 'Meera Iyer', 'checkedIn' => true]);
    $r->update(['answers' => array_merge((array) $r->answers, ['bib' => '417', 'finish_time' => '00:04:12'])]);

    expect($this->templates->html($r->fresh()))
        ->toContain("certificate--{$template}")
        ->toContain('Meera Iyer')
        ->toContain('417')
        ->toContain('00:04:12')
        ->toContain('1000 m')
        ->toContain('Fixture Skating Open')
        ->toContain('07 February 2027');
})->with('gallery');

test('every gallery template has a picker thumbnail', function (string $template) {
    expect(public_path("images/certificates/{$template}-landscape.png"))->toBeFile();
})->with('gallery');

test('the organiser logo is printed when the event has one', function () {
    Storage::disk('public')->put('branding/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    $this->event->update(['config' => ReportFixtures::config(['branding' => ['primary' => '#1d3b8b', 'secondary' => '#e8553f', 'logo' => 'branding/logo.png']])]);
    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);

    expect($this->templates->html($r))->toContain('data:image/png;base64,iVBOR');
});

test('the certificates page shows the gallery and the Indefine contact', function () {
    $this->actingAs($this->owner)->get(route('reports.certificates', $this->event))
        ->assertOk()
        ->assertSee('Velocity')
        ->assertSee('Glide')
        ->assertSee('Upload your own')
        ->assertSee('Contact Indefine');
});

test('organisers pick a gallery template', function () {
    $this->actingAs($this->owner)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'glide', 'orientation' => 'portrait'])
        ->assertRedirect(route('reports.certificates', $this->event));

    $event = $this->event->fresh();
    expect($event->config['certificate'])->toBe(['template' => 'glide', 'orientation' => 'portrait'])
        ->and($event->certificate_template_id)->toBe('glide')
        ->and($this->templates->settings($event)->orientation)->toBe('portrait');
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

    $r = ReportFixtures::entrant($this->event, '500M', 9, 'Boy', ['checkedIn' => true]);
    expect($this->templates->html($r->fresh()))->toContain('certificate--upload')->toContain('left: 48.50mm'); // name slot centred at x 148.5mm
});

test('a background at the wrong size is rejected with a clear message', function (string $orientation, int $w, int $h) {
    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'upload',
        'orientation' => $orientation,
        'background' => UploadedFile::fake()->image('artwork.png', $w, $h),
    ])->assertSessionHasErrors('background');

    expect(session('errors')->first('background'))->toContain('must be exactly')->toContain("Yours is {$w} × {$h} px");

    expect($this->event->fresh()->config['certificate']['template'])->not->toBe('upload');
})->with([
    'too small' => ['landscape', 1754, 1240],
    'portrait size for landscape' => ['landscape', 2480, 3508],
    'landscape size for portrait' => ['portrait', 3508, 2480],
]);

test('upload needs a file and only accepts PNG or JPG', function () {
    $this->actingAs($this->owner)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'upload', 'orientation' => 'landscape'])
        ->assertSessionHasErrors('background');

    $this->actingAs($this->owner)->put(route('reports.certificates.design', $this->event), [
        'template' => 'upload', 'orientation' => 'landscape',
        'background' => UploadedFile::fake()->create('artwork.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('background');
});

test('unknown templates are rejected', function () {
    $this->actingAs($this->owner)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'fancy', 'orientation' => 'landscape'])
        ->assertSessionHasErrors('template');
});

test('a template preview renders a sample PDF', function () {
    $response = $this->actingAs($this->owner)->get(route('reports.certificates.preview', [$this->event, 'velocity']));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');

    $this->actingAs($this->owner)->get(route('reports.certificates.preview', [$this->event, 'nope']))->assertNotFound();
});

test('another org cannot change the design', function () {
    $other = ReportFixtures::owner(ReportFixtures::event(ReportFixtures::orgWithPass()));

    $this->actingAs($other)
        ->put(route('reports.certificates.design', $this->event), ['template' => 'glide', 'orientation' => 'landscape'])
        ->assertNotFound();
});
