<?php

namespace App\Http\Controllers;

use App\Landing\Capacity;
use App\Landing\ConfigCompiler;
use App\Landing\EventColumns;
use App\Landing\EventConfig;
use App\Landing\EventStatus;
use App\Landing\InvalidEventConfig;
use App\Landing\RegistrationForm;
use App\Landing\Theme;
use App\Models\Event;
use App\Models\Org;
use App\Payments\PaymentStatus;
use App\Saas\EventAddons;
use App\Saas\Trial;
use App\View\LandingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Event → Overview (Session UX-H2): the organiser's own public page in a live preview
 * (desktop / mobile), plus Open public URL, Change template, Edit content and
 * Publish / Unpublish, and the event's add-ons.
 */
class EventOverviewController extends Controller
{
    public function __construct(private ConfigCompiler $compiler) {}

    public function show(Request $request, Event $event): View
    {
        $org = $this->org($request, $event);
        $template = $this->config($event)?->template;

        return view('events.overview', [
            'org' => $org,
            'event' => $event,
            'live' => $event->status === EventStatus::Published,
            'publicUrl' => route('landing.show', [$org->slug, $event->slug]),
            'template' => $template,
            'templates' => config('eventflow.landing_templates'),
            'addons' => EventAddons::status($event),
            'stats' => [
                'registrations' => $event->registrations()->count(),
                'paid' => $event->registrations()->where('status', PaymentStatus::Paid)->count(),
            ],
        ]);
    }

    /**
     * The event's landing exactly as runners see it, drafts included, inert (preview mode).
     * ?template= tries another template without saving it.
     */
    public function preview(Request $request, Event $event): View
    {
        $org = $this->org($request, $event);
        $config = $this->config($event) ?? abort(404, __('This event has no page content yet.'));

        $template = $request->query('template');
        if (is_string($template) && array_key_exists($template, config('eventflow.landing_templates'))) {
            $config = $this->compile(['template' => $template] + ($event->config ?? []));
        }

        return view('landings.templates.'.$config->template, [
            'page' => new LandingPage(
                config: $config,
                orgName: $org->name,
                orgSlug: $org->slug,
                form: new RegistrationForm($config),
                theme: Theme::for($config->branding),
                remaining: Capacity::remaining($event, $config),
                open: RegistrationController::isOpen($config),
                preview: true,
                watermark: Trial::watermark($org),
            ),
        ]);
    }

    public function updateTemplate(Request $request, Event $event): RedirectResponse
    {
        $this->org($request, $event);
        $key = $request->validate([
            'template' => ['required', Rule::in(array_keys(config('eventflow.landing_templates')))],
        ])['template'];

        $authored = ['template' => $key] + ($event->config ?? []);
        $event->update(EventColumns::from($authored, $this->compile($authored)));

        return redirect()->route('organiser.events.show', $event)->with('status', 'template-updated');
    }

    public function updateStatus(Request $request, Event $event): RedirectResponse
    {
        $org = $this->org($request, $event);
        $status = $request->validate([
            'status' => ['required', Rule::in([EventStatus::Published, EventStatus::Draft])],
        ])['status'];

        if ($status === EventStatus::Published && $event->status !== EventStatus::Published && ! Trial::canPublish($org, $event->id)) {
            throw ValidationException::withMessages(['status' => __('Your free trial covers :n live event. Pick a plan in Plan & billing to publish more.', ['n' => Trial::eventLimit()])]);
        }

        $event->update(['status' => $status]);

        return redirect()->route('organiser.events.show', $event)
            ->with('status', $status === EventStatus::Published ? 'event-published' : 'event-unpublished');
    }

    private function org(Request $request, Event $event): Org
    {
        $user = $request->user();
        abort_if($user->org_id === null || $event->org_id !== $user->org_id, 404);

        return $user->org;
    }

    private function config(Event $event): ?EventConfig
    {
        try {
            return $this->compiler->compile($event->config ?? [], "event {$event->id}");
        } catch (InvalidEventConfig) {
            return null;
        }
    }

    private function compile(array $authored): EventConfig
    {
        try {
            return $this->compiler->compile($authored);
        } catch (InvalidEventConfig $e) {
            throw ValidationException::withMessages(['template' => collect($e->errors)->flatten()->first() ?? __('This template cannot be applied.')]);
        }
    }
}
