<?php

namespace App\Http\Controllers;

use App\Contracts\SportPackRepository;
use App\Landing\ConfigCompiler;
use App\Landing\EventColumns;
use App\Landing\EventConfig;
use App\Landing\EventForm;
use App\Landing\EventStatus;
use App\Landing\EventWizard;
use App\Landing\InvalidEventConfig;
use App\Landing\LogoInspector;
use App\Models\Event;
use App\Models\Org;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Organiser event setup (CRUD). Writes events.config in the same shape as event YAML.
 */
class EventController extends Controller
{
    public function __construct(private ConfigCompiler $compiler) {}

    public function index(Request $request): View
    {
        $org = $this->org($request);

        return view('events.index', [
            'org' => $org,
            'events' => $org->events()->withCount('registrations')->orderBy('date')->get(),
        ]);
    }

    /** The 5-step create wizard (App\Landing\EventWizard). */
    public function create(Request $request, SportPackRepository $packs): View
    {
        $org = $this->org($request);

        return view('events.wizard', [
            'org' => $org,
            'packs' => EventWizard::packs($packs),
            'templates' => config('eventflow.landing_templates'),
            'step' => (int) session('wizard_step', 1),
            'contact' => config('eventflow.certificates.custom_contact') ?: config('plans.brand.contact_email'),
        ]);
    }

    /** Creates and (by default) publishes the event from the wizard, with logo/photo uploads. */
    public function store(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        $validator = Validator::make($request->all(), EventWizard::rules(), EventWizard::messages());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['logo', 'hero']))
                ->with('wizard_step', EventWizard::stepFor($validator->errors()->keys()));
        }
        $input = $validator->validated();

        $logo = $request->file('logo')?->storePublicly("branding/{$org->id}", 'public');
        $hero = $request->file('hero')?->storePublicly("branding/{$org->id}", 'public');
        $logoCheck = $logo ? LogoInspector::inspect(Storage::disk('public')->path($logo)) : null;

        $slug = Event::uniqueSlug($org->id, $input['name']);
        $authored = EventWizard::toConfig($input, $org->slug, $slug, $logo, $hero);

        try {
            $config = $this->compiler->compile($authored);
        } catch (InvalidEventConfig $e) {
            Storage::disk('public')->delete(array_filter([$logo, $hero]));
            throw ValidationException::withMessages(EventForm::errorBag($e->errors));
        }

        $status = $input['publish'] ? EventStatus::Published : EventStatus::Draft;
        $event = $org->events()->create(EventColumns::from($authored, $config) + ['status' => $status]);

        return redirect()->route('organiser.events.published', $event)->with([
            'logo_dark' => (bool) ($logoCheck['dark'] ?? false),
            'logo_opaque' => $logoCheck !== null && ! $logoCheck['transparent'],
        ]);
    }

    /** "Your page is live" after the wizard: link, share and next steps. */
    public function published(Request $request, Event $event): View
    {
        $org = $this->org($request, $event);

        return view('events.published', [
            'org' => $org,
            'event' => $event,
            'url' => route('landing.show', [$org->slug, $event->slug]),
            'template' => config("eventflow.landing_templates.{$event->template_id}"),
        ]);
    }

    public function edit(Request $request, Event $event): View
    {
        $org = $this->org($request, $event);

        return view('events.edit', [
            'org' => $org,
            'event' => $event,
            'input' => EventForm::toInput($this->resolved($event), $event->status),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $org = $this->org($request, $event);
        $input = $request->validate(EventForm::rules());

        $authored = EventForm::toConfig($input, $org->slug, $event->slug, $event->config ?? []);
        $config = $this->compile($authored);

        $used = $event->registrations()->whereNotNull('distance_code')->distinct()->pluck('distance_code');
        $missing = $used->diff(array_map(fn ($d) => $d->code, $config->distances));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'distances' => __('Distances :codes already have registrations and cannot be removed.', ['codes' => $missing->implode(', ')]),
            ]);
        }

        $event->update(EventColumns::from($authored, $config) + ['status' => $input['status']]);

        return redirect()->route('organiser.events.edit', $event)->with('status', 'event-updated');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->org($request, $event);

        if ($event->registrations()->exists()) {
            return back()->withErrors(['event' => __('This event has registrations, so it cannot be deleted. Set its status to closed instead.')]);
        }

        $event->delete();

        return redirect()->route('organiser.events.index')->with('status', 'event-deleted');
    }

    /** The signed-in organiser's org (route model binding is already org-scoped). */
    private function org(Request $request, ?Event $event = null): Org
    {
        $user = $request->user();
        abort_if($user->org_id === null, 403);

        if ($event !== null) {
            abort_unless($event->org_id === $user->org_id, 404);
        }

        return $user->org;
    }

    /** @return array<string, mixed> */
    private function resolved(Event $event): array
    {
        try {
            return $this->compiler->resolve($event->config ?? [], "event {$event->id}");
        } catch (InvalidEventConfig) {
            return $event->config ?? [];
        }
    }

    private function compile(array $authored): EventConfig
    {
        try {
            return $this->compiler->compile($authored);
        } catch (InvalidEventConfig $e) {
            throw ValidationException::withMessages(EventForm::errorBag($e->errors));
        }
    }
}
