<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EventController;
use App\Saas\Plans;
use App\Saas\Trial;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The organiser's plan, trial and add-ons (shown right after signup). */
class AccountPlanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $org = $request->user()->org ?? abort(404);
        $plan = Plans::forOrg($org->plan);

        return view('marketing.account-plan', [
            'org' => $org,
            'plan' => $plan,
            'addons' => array_intersect_key(Plans::addons(), array_flip($org->addons ?? [])),
            'welcome' => $request->boolean('welcome'),
            'template' => EventController::galleryTemplate($request->query('template')),
            'trial' => Trial::onTrial($org) ? Trial::status($org) : null,
            'plans' => array_intersect_key(Plans::ordered(), array_flip(Plans::selfServe())),
            'contactEmail' => config('plans.brand.contact_email'),
        ]);
    }
}
