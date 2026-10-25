<?php

namespace App\Http\Controllers;

use App\Certificates\DomPdfCertificateRenderer;
use App\Landing\ConfigCompiler;
use App\Landing\EventColumns;
use App\Landing\EventForm;
use App\Landing\InvalidEventConfig;
use App\Models\Event;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pick a certificate design for an event: one of the gallery templates, or an
 * uploaded full-bleed A4 background (exact pixel size, text stamped at the
 * coordinates in config('eventflow.certificates.upload_layout')).
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

        $block = ['template' => $input['template'], 'orientation' => $input['orientation']];
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

        return redirect()->route('reports.certificates', $event)->with('status', __('Certificate design saved.'));
    }

    /** A sample certificate in the given design, rendered as an inline PDF. */
    public function preview(Request $request, Event $event, string $template): Response
    {
        abort_unless(array_key_exists($template, (array) config('eventflow.certificates.gallery', [])), 404);

        $event->loadMissing('org');
        $sample = clone $event;
        $config = $sample->config ?? [];
        $config['certificate'] = ['template' => $template, 'orientation' => $request->query('orientation') === 'portrait' ? 'portrait' : 'landscape'];
        $sample->config = $config;
        $settings = $this->templates->settings($sample);

        $distance = ($config['distances'][0] ?? $event->distances[0] ?? [])['code'] ?? '5K';
        $registration = (new Registration)->forceFill([
            'pass_no' => 'EF-SAMPLE01',
            'participant_name' => 'Ananya Krishnan',
            'distance_code' => $distance,
            'answers' => ['bib' => '1024', 'finish_time' => '00:42:18'],
        ])->setRelation('event', $sample);

        $pdf = Pdf::loadHTML($this->templates->html($registration))->setPaper('a4', $settings->orientation)->output();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$template.'-preview.pdf"',
        ]);
    }
}
