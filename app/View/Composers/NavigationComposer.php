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
    /** Heroicon-style outline paths (24x24). */
    public const ICONS = [
        'home' => 'M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z',
        'events' => 'M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
        'people' => 'M16 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm13 9v-1a4 4 0 0 0-3-3.9M16 4.1a3 3 0 0 1 0 5.8',
        'scan' => 'M4 8V5a1 1 0 0 1 1-1h3m8 0h3a1 1 0 0 1 1 1v3m0 8v3a1 1 0 0 1-1 1h-3m-8 0H5a1 1 0 0 1-1-1v-3M7 12h10',
        'sheet' => 'M9 4h6M5 6h14v14H5zM9 10h6M9 14h6M9 18h3',
        'award' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm-3.5 4.5L7 22l5-2 5 2-1.5-2.5M9 14.5 8 22m8-7.5 1 7.5',
        'bell' => 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0',
        'settings' => 'M10.3 4.3a1.7 1.7 0 0 1 3.4 0 1.7 1.7 0 0 0 2.6 1.1 1.7 1.7 0 0 1 2.4 2.4 1.7 1.7 0 0 0 1.1 2.6 1.7 1.7 0 0 1 0 3.4 1.7 1.7 0 0 0-1.1 2.6 1.7 1.7 0 0 1-2.4 2.4 1.7 1.7 0 0 0-2.6 1.1 1.7 1.7 0 0 1-3.4 0 1.7 1.7 0 0 0-2.6-1.1 1.7 1.7 0 0 1-2.4-2.4 1.7 1.7 0 0 0-1.1-2.6 1.7 1.7 0 0 1 0-3.4 1.7 1.7 0 0 0 1.1-2.6 1.7 1.7 0 0 1 2.4-2.4 1.7 1.7 0 0 0 2.6-1.1zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'card' => 'M3 6h18v12H3zM3 10h18M7 15h3',
        'wallet' => 'M4 7h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4zM4 7V6a2 2 0 0 1 2-2h10M16 13h2',
        'shield' => 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6z',
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
                    $settings[] = $this->item(__('Notifications'), 'settings.notifications.show', 'bell', 'settings.notifications.*');
                }
                if (Route::has('settings.payments.show')) {
                    $settings[] = $this->item(__('Payments'), 'settings.payments.show', 'wallet', 'settings.payments.*');
                }
            }
            if (Route::has('account.plan')) {
                $settings[] = $this->item(__('Plan & billing'), 'account.plan', 'card', 'account.plan');
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
