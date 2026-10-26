<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\View\SampleEvent;
use Illuminate\View\View;

/**
 * Public gallery of the five Race Ink landing templates (/templates) and a full preview of
 * each on sample data (/templates/{key}). "Use this template" starts signup with it preselected.
 */
class TemplateGalleryController extends Controller
{
    public function index(): View
    {
        return view('marketing.templates', [
            'templates' => config('eventflow.landing_templates'),
        ]);
    }

    public function show(string $template): View
    {
        abort_unless(array_key_exists($template, config('eventflow.landing_templates')), 404);

        return view('landings.templates.'.$template, [
            'page' => SampleEvent::landing($template),
            'useTemplateUrl' => self::useUrl($template),
        ]);
    }

    /** Signed-in organisers go straight to the wizard; everyone else signs up first. */
    public static function useUrl(string $template): string
    {
        return auth()->check()
            ? route('organiser.events.create', ['template' => $template])
            : route('register', ['template' => $template]);
    }
}
