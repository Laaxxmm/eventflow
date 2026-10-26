<?php

namespace App\Saas;

use App\Support\Contact;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out members of an org that Indefine suspended (platform admins are never blocked).
 */
class EnsureOrgNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isPlatformAdmin() && $user->org?->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('This organisation\'s account is suspended. Contact :email.', ['email' => Contact::email()]),
            ]);
        }

        return $next($request);
    }
}
