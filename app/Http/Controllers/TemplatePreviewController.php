<?php

namespace App\Http\Controllers;

use App\View\SampleEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing template previews with sample content, for the create-event wizard's picker.
 * GET /organiser/templates/{template}/preview?primary=%23rrggbb&secondary=%23rrggbb&name=...
 */
class TemplatePreviewController extends Controller
{
    public function __invoke(Request $request, string $template): View
    {
        abort_unless(array_key_exists($template, config('eventflow.landing_templates')), 404);

        return view('landings.templates.'.$template, [
            'page' => SampleEvent::landing($template, $request->only(['primary', 'secondary', 'name'])),
        ]);
    }
}
