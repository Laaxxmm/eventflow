<?php

namespace App\Http\Controllers\Marketing;

use App\Billing\Checkout;
use App\Domains\DomainAccess;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventController;
use App\Notifications\NotificationSettings;
use App\Saas\PlanLimits;
use App\Saas\Plans;
use App\Saas\Trial;
use App\Support\Contact;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The organiser's plan, term, caps and usage, add-ons, and the ways out of a cap. */
class AccountPlanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $org = $request->user()->org ?? abort(404);
        $plan = $org->plan ? (Plans::all()[$org->plan] ?? null) : null;
        $settings = NotificationSettings::for($org);

        return view('marketing.account-plan', [
            'org' => $org,
            'plan' => $plan,
            'pass' => PlanLimits::pass($org),
            'racesAllowed' => PlanLimits::racesAllowed($org),
            'racesUsed' => PlanLimits::racesUsed($org),
            'pressure' => PlanLimits::pressure($org),
            'addons' => array_intersect_key(Plans::addons(), array_flip($org->addons ?? [])),
            'mail' => match (true) {
                $settings->usesCustomSmtp($org) => 'smtp',
                $settings->usesPlatformMail($org) => 'platform',
                default => 'held',
            },
            'welcome' => $request->boolean('welcome'),
            'template' => EventController::galleryTemplate($request->query('template')),
            'trial' => Trial::onTrial($org) ? Trial::status($org) : null,
            'limitHit' => session('plan_limit'),
            'contactEmail' => Contact::email(),
            'domainIncluded' => DomainAccess::planIncludes($org),
            'checkoutReady' => Checkout::ready(),
        ]);
    }
}
