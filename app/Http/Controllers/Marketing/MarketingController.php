<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Saas\Plans;
use App\Support\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public SaaS pages that sell EventFlow. Event landings (/{org}/{slug}) are separate.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home', $this->pricingData());
    }

    public function pricing(): View
    {
        return view('marketing.pricing', $this->pricingData());
    }

    /** /app: where "Go to app" lands, by who is signed in. */
    public function app(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isPlatformAdmin() => redirect()->route('platform.orgs.index'),
            $user->isVolunteer() => redirect('/checkin'),
            default => redirect()->route('dashboard'),
        };
    }

    /** @return array<string, mixed> */
    private function pricingData(): array
    {
        return [
            'plans' => Plans::ordered(),
            'entry' => Plans::entry(),
            'addons' => Plans::addons(),
            'trialDays' => Plans::trialDays(),
            'contactEmail' => Contact::email(),
        ];
    }
}
