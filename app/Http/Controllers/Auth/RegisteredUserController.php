<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Org;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    public function create(): View
    {
        return view('auth.register');
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
        ]);

        // The signing-up organiser becomes the owner of a new org.
        $user = DB::transaction(function () use ($request) {
            $org = Org::create([
                'name' => $request->organization_name,
                'slug' => Org::uniqueSlug($request->organization_name),
                'phone' => $request->phone,
            ]);

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

        return redirect(route('dashboard', absolute: false));
    }
}
