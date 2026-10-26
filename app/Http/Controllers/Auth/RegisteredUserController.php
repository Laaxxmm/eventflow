<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventController;
use App\Models\Org;
use App\Models\User;
use App\Saas\PlanAssigner;
use App\Saas\Plans;
use App\Saas\ReservedSlugs;
use App\Saas\Trial;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller implements HasMiddleware
{
    /** 5 attempts per minute per IP (RateLimiter 'auth'). */
    public static function middleware(): array
    {
        return [new Middleware('throttle:auth', only: ['store'])];
    }

    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $plan = $request->query('plan');

        return view('auth.register', [
            'plans' => array_intersect_key(Plans::all(), array_flip(Plans::selfServe())),
            'addons' => Plans::addons(),
            'selectedPlan' => in_array($plan, Plans::selfServe(), true) ? $plan : config('plans.default'),
            'selectedAddons' => array_values(array_intersect((array) $request->query('addons', []), array_keys(Plans::addons()))),
            'template' => EventController::galleryTemplate($request->query('template')),
            'trial' => ['days' => Trial::days(), 'events' => Trial::eventLimit(), 'registrations' => Trial::registrationLimit()],
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Self-serve plans start a free trial; "contact us" plans cannot be picked here.
            'plan' => ['nullable', Rule::in(Plans::selfServe())],
            'addons' => ['nullable', 'array'],
            'addons.*' => [Rule::in(array_keys(Plans::addons()))],
            'template' => ['nullable', Rule::in(array_keys(config('eventflow.landing_templates')))],
        ]);

        // The signing-up organiser becomes the owner of a new org.
        $user = DB::transaction(function () use ($request) {
            $org = Org::create([
                'name' => $request->organization_name,
                'slug' => ReservedSlugs::orgSlug($request->organization_name),
                'phone' => $request->phone,
            ]);

            PlanAssigner::startTrial($org, $request->input('plan') ?: config('plans.default'), $request->input('addons', []));

            return User::create([
                'org_id' => $org->id,
                'role' => Role::Owner,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        // Plan + trial welcome (says to verify the email before creating events).
        // A template chosen on /templates carries through to "Create your first event".
        return redirect(route('account.plan', array_filter(['welcome' => 1, 'template' => $request->input('template')]), absolute: false));
    }
}
