<?php

namespace App\Http\Controllers;

use App\Landing\Capacity;
use App\Landing\DatabaseLandingSource;
use App\Landing\EventStatus;
use App\Landing\RegistrationForm;
use App\Landing\Theme;
use App\Saas\PlanLimits;
use App\View\LandingPage;
use Illuminate\View\View;

class PublicEventController extends Controller
{
    public function __construct(private DatabaseLandingSource $landing) {}

    /** /{org}: the org's published events, soonest first. */
    public function org(string $org): View
    {
        $org = $this->landing->org($org);

        $events = $org->events()
            ->where('status', EventStatus::Published)
            ->orderBy('date')
            ->get();

        return view('landing.org', [
            'org' => $org,
            'events' => $events,
            'theme' => Theme::for(null, $org->colours ?? $events->first()?->colours),
        ]);
    }

    /** /{org}/{slug}: the branded landing with its registration form. */
    public function show(string $org, string $slug): View
    {
        $event = $this->landing->event($org, $slug);
        $config = $this->landing->load($org, $slug);

        // Layout from the event's landing template (config `template`, else the sport pack's).
        return view('landings.templates.'.$config->template, [
            'page' => new LandingPage(
                config: $config,
                orgName: $event->org->name,
                orgSlug: $event->org->slug,
                form: new RegistrationForm($config),
                theme: Theme::for($config->branding),
                remaining: Capacity::remaining($event, $config),
                open: RegistrationController::isOpen($config) && ! ($paused = PlanLimits::atParticipantCap($event)),
                paused: $paused ?? false,
            ),
        ]);
    }
}
