<?php

namespace App\Http\Controllers;

use App\Contracts\SportPackRepository;
use App\Enums\Sport;
use App\Landing\ConfigCompiler;
use App\Landing\EventColumns;
use App\Landing\EventConfig;
use App\Landing\EventForm;
use App\Landing\InvalidEventConfig;
use App\Models\Event;
use App\Models\Org;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function create(Request $request, SportPackRepository $packs): View
    {
        $sport = Sport::tryFrom((string) $request->query('sport')) ?? Sport::Generic;
        $defaults = $packs->get($sport)->applyTo(['sport' => $sport->value]);

        return view('events.create', [
            'org' => $this->org($request),
            'input' => EventForm::toInput($defaults),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        $input = $request->validate(EventForm::rules());
        $slug = $this->uniqueSlug($org, $input['name']);

        $authored = EventForm::toConfig($input, $org->slug, $slug);
        $config = $this->compile($authored);

        $event = $org->events()->create(EventColumns::from($authored, $config) + ['status' => $input['status']]);

        return redirect()->route('organiser.events.edit', $event)->with('status', 'event-created');
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

    /** The signed-in organiser's org; staff of another org get a 404 for its events. */
    private function org(Request $request, ?Event $event = null): Org
    {
        $user = $request->user();
        abort_if($user->org_id === null || $user->role === 'volunteer', 403);

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

    private function uniqueSlug(Org $org, string $name): string
    {
        $base = Str::limit(Str::slug($name), 70, '') ?: 'event';
        $base = trim($base, '-');
        $slug = $base;

        for ($n = 2; $org->events()->where('slug', $slug)->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
