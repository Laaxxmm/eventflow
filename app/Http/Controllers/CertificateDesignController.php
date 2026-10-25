<?php

namespace App\Http\Controllers;

use App\Certificates\DomPdfCertificateRenderer;
use App\Landing\ConfigCompiler;
use App\Landing\EventColumns;
use App\Landing\EventForm;
use App\Landing\InvalidEventConfig;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Certificate design for an event: a premium gallery template (with palette,
 * credential line, signatories, stamp, print variant) or an uploaded A4
 * background with text stamped at config('eventflow.certificates.upload_layout').
 * Writes the event's `certificate` YAML block; issued PDFs refresh on their own.
 */
class CertificateDesignController extends Controller
{
    public function __construct(private DomPdfCertificateRenderer $templates, private ConfigCompiler $compiler) {}

    public function update(Request $request, Event $event): RedirectResponse
    {
        $gallery = (array) config('eventflow.certificates.gallery', []);
        $current = $this->templates->settings($event);

        $input = Validator::make($request->all(), [
            'template' => ['required', 'string', Rule::in([...array_keys($gallery), 'upload'])],
            'orientation' => ['required', Rule::in(['landscape', 'portrait'])],
            'palette' => ['nullable', Rule::in(array_keys((array) config('eventflow.certificates.palettes')))],
            'credential' => ['nullable', Rule::in(array_keys((array) config('eventflow.certificates.credentials')))],
            'variant' => ['nullable', Rule::in(['screen', 'print'])],
            'stamp' => ['nullable', 'boolean'],
            'signatories' => ['nullable', 'array', 'max:2'],
            'signatories.*.name' => ['nullable', 'string', 'max:80'],
            'signatories.*.role' => ['nullable', 'string', 'max:60'],
            'background' => [
                Rule::requiredIf(fn () => $request->input('template') === 'upload' && ! ($current->isUpload() && $current->orientation === $request->input('orientation'))),
                'nullable', 'file',
                'mimes:'.implode(',', (array) config('eventflow.certificates.upload_mimes', ['png', 'jpg', 'jpeg'])),
                'max:'.(int) config('eventflow.certificates.upload_max_kb', 8192),
            ],
        ], [
            'background.required' => __('Upload your A4 background (PNG or JPG) to use your own design.'),
            'background.mimes' => __('The background must be a PNG or JPG. Export PDF artwork as a 300 dpi PNG.'),
            'background.max' => __('The background must be :max KB or smaller.'),
        ])->after(function ($validator) use ($request) {
            $file = $request->file('background');
            if (! $file instanceof UploadedFile || $validator->errors()->has('background')) {
                return;
            }
            $orientation = $request->input('orientation') === 'portrait' ? 'portrait' : 'landscape';
            [$w, $h] = config("eventflow.certificates.px.{$orientation}");
            $size = @getimagesize($file->getRealPath());
            if (! $size || $size[0] !== $w || $size[1] !== $h) {
                $validator->errors()->add('background', __('The background must be exactly :w × :h px (A4 :orientation at 300 dpi). Yours is :actual.', [
                    'w' => $w, 'h' => $h, 'orientation' => $orientation,
                    'actual' => $size ? "{$size[0]} × {$size[1]} px" : __('not a readable image'),
                ]));
            }
        })->validate();

        $block = array_filter([
            'template' => $input['template'],
            'orientation' => $input['orientation'],
            'palette' => $input['palette'] ?? null,
            'credential' => $input['credential'] ?? null,
            'variant' => ($input['variant'] ?? null) === 'print' ? 'print' : null,
        ]);
        $block['stamp'] = (bool) ($input['stamp'] ?? false);
        $signatories = array_values(array_map(
            fn (array $s) => array_filter(['name' => trim((string) ($s['name'] ?? '')), 'role' => trim((string) ($s['role'] ?? ''))], 'strlen'),
            array_slice((array) ($input['signatories'] ?? []), 0, 2),
        ));
        if (array_filter($signatories)) {
            $block['signatories'] = array_map(fn ($s) => $s ?: ['role' => ''], $signatories);
        }
        if ($input['template'] === 'upload') {
            $block['background'] = isset($input['background'])
                ? $input['background']->store("certificates/backgrounds/{$event->org_id}", $this->templates->disk())
                : $current->background;
        }

        $authored = $event->config ?? [];
        $authored['certificate'] = $block;

        try {
            $config = $this->compiler->compile($authored);
        } catch (InvalidEventConfig $e) {
            throw ValidationException::withMessages(EventForm::errorBag($e->errors));
        }

        $event->update(EventColumns::from($authored, $config));

        return redirect()->route('reports.certificates', $event)->with('status', __('Certificate design saved. Issued certificates are being refreshed.'));
    }

    /**
     * A placeholder certificate in the given design with the event's branding,
     * inline or as a download (?download=1). Query: orientation, palette, credential, variant.
     */
    public function preview(Request $request, Event $event, string $template): Response
    {
        $resolved = DomPdfCertificateRenderer::resolveTemplate($template);
        abort_unless($resolved === $template || config("eventflow.certificates.aliases.{$template}") === $resolved, 404);

        $overrides = array_filter([
            'palette' => in_array($request->query('palette'), array_keys((array) config('eventflow.certificates.palettes')), true) ? $request->query('palette') : null,
            'credential' => in_array($request->query('credential'), array_keys((array) config('eventflow.certificates.credentials')), true) ? $request->query('credential') : null,
            'variant' => $request->query('variant') === 'print' ? 'print' : null,
        ]);
        $orientation = in_array($request->query('orientation'), ['landscape', 'portrait'], true) ? $request->query('orientation') : null;

        $data = $this->templates->sample($event, $resolved, $orientation, $overrides);
        $pdf = $this->templates->pdf($this->templates->view($data), $data->orientation);
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$resolved.'-sample.pdf"',
        ]);
    }

    /** Gallery thumbnail (resources/certificates/thumbnails/<key>.png). */
    public function thumbnail(string $template): BinaryFileResponse
    {
        abort_unless(array_key_exists($template, (array) config('eventflow.certificates.gallery', [])), 404);
        $path = resource_path("certificates/thumbnails/{$template}.png");
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
