<?php

namespace App\View\Composers;

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Organiser sidebar: groups of {label, href, icon, active}. Race sheets, certificates and
 * check-in appear only when the org's Season Pass + Org Settings have the module on.
 */
class NavigationComposer
{
    /** Outline icon paths (24x24), from docs/design/daylight-track/Sidebar.dc.html. */
    public const ICONS = [
        'home' => 'M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
        'events' => 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
        'people' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c0-4 3-6 7-6s7 2 7 6M17 11a3 3 0 1 0 0-6M22 21c0-3-2-5-5-5.5',
        'scan' => 'M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M4 12h16',
        'sheet' => 'M6 3h9l4 4v14H6zM9 12h7M9 16h7M9 8h3',
        'award' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM8.5 14L7 22l5-3 5 3-1.5-8',
        'bell' => 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4zM10 21h4',
        'settings' => 'M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M14 4v4M8 10v4M16 16v4',
        'card' => 'M3 6h18v12H3zM3 10h18M7 15h4',
        'wallet' => 'M3 7h15a3 3 0 0 1 3 3v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 7l12-4 2 4M16 14h2',
        'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
        'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18',
    ];

    public function compose(View $view): void
    {
        $view->with('nav', $this->groups(request()->user()));
    }

    /** @return list<array{label: string|null, items: list<array{label: string, href: string, icon: string, active: bool}>}> */
    public function groups(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $groups = [];
        $org = $user->org;

        if ($org && $user->hasRole(Role::Owner, Role::Staff)) {
            $on = fn (Module $m) => ! app()->bound(Entitlements::class) || app(Entitlements::class)->enabled($org, $m);
            $focus = $this->focusEvent();

            $items = [
                $this->item(__('Home'), 'dashboard', 'home', 'dashboard'),
                $this->item(__('Events'), 'organiser.events.index', 'events', 'organiser.events.*'),
                $this->item(__('Participants'), 'dashboard.participants', 'people', 'dashboard.participants*'),
            ];
            if ($on(Module::CheckIn) && Route::has('checkin.index')) {
                $items[] = $this->item(__('Check-in'), 'checkin.index', 'scan', 'checkin.*');
            }
            if ($on(Module::RaceSheets) && Route::has('reports.race-sheet')) {
                $items[] = $this->item(__('Race sheets'), $focus ? route('reports.race-sheet', $focus) : 'organiser.events.index', 'sheet', 'reports.race-sheet*');
            }
            if ($on(Module::Certificates) && Route::has('reports.certificates')) {
                $items[] = $this->item(__('Certificates'), $focus ? route('reports.certificates', $focus) : 'organiser.events.index', 'award', 'reports.certificates*');
            }
            $groups[] = ['label' => null, 'items' => $items];

            $settings = [];
            if (Gate::forUser($user)->allows('manage-org')) {
                if (Route::has('settings.edit')) {
                    $settings[] = $this->item(__('Modules'), 'settings.edit', 'settings', 'settings.edit');
                }
                if (Route::has('settings.notifications.show')) {
                    $settings[] = $this->item(__('Email and WhatsApp'), 'settings.notifications.show', 'bell', 'settings.notifications.*');
                }
                if (Route::has('settings.payments.show')) {
                    $settings[] = $this->item(__('Payments'), 'settings.payments.show', 'wallet', 'settings.payments.*');
                }
                if (Route::has('settings.domain.show')) {
                    $settings[] = $this->item(__('Domain'), 'settings.domain.show', 'globe', 'settings.domain.*');
                }
            }
            if (Route::has('account.plan')) {
                $settings[] = $this->item(__('Plan and billing'), 'account.plan', 'card', 'account.plan');
            }
            $groups[] = ['label' => __('Settings'), 'items' => $settings];
        }

        if ($user->isPlatformAdmin() && Route::has('platform.orgs.index')) {
            $groups[] = ['label' => __('Indefine'), 'items' => [$this->item(__('Organisations'), 'platform.orgs.index', 'shield', 'platform.*')]];
        }

        return array_values(array_filter($groups, fn ($g) => $g['items'] !== []));
    }

    /** The next upcoming event (else the latest) for per-event pages in the nav. */
    private function focusEvent(): ?Event
    {
        return Event::query()->where('date', '>=', now()->subDay())->orderBy('date')->first()
            ?? Event::query()->latest('date')->first();
    }

    /** @return array{label: string, href: string, icon: string, active: bool} */
    private function item(string $label, string $routeOrUrl, string $icon, string $activePattern): array
    {
        return [
            'label' => $label,
            'href' => str_starts_with($routeOrUrl, 'http') ? $routeOrUrl : route($routeOrUrl),
            'icon' => self::ICONS[$icon],
            'active' => request()->routeIs($activePattern),
        ];
    }
}
