<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Saas\Plans;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The organiser's plan, trial and add-ons (shown right after signup). */
class AccountPlanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $org = $request->user()->org ?? abort(404);
        $plan = $org->plan ? (Plans::all()[$org->plan] ?? null) : null;

        return view('marketing.account-plan', [
            'org' => $org,
            'plan' => $plan,
            'addons' => array_intersect_key(Plans::addons(), array_flip($org->addons ?? [])),
            'welcome' => $request->boolean('welcome'),
            'contactEmail' => config('plans.brand.contact_email'),
        ]);
    }
}
