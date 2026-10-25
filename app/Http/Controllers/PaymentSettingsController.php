<?php

namespace App\Http\Controllers;

use App\Models\Org;
use App\Payments\ConnectionCheck;
use App\Payments\GatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Settings → Payments: the organiser connects their own Cashfree or Razorpay
 * account, so registration money settles straight into it. Without one, the
 * platform account (env keys) is used. Secrets are stored encrypted and never
 * shown again; a blank secret field keeps the saved value.
 */
class PaymentSettingsController extends Controller
{
    public function show(Request $request, GatewayManager $gateways): View
    {
        $org = $this->org($request);
        $active = $gateways->resolveProvider($org);

        return view('settings.payments', [
            'org' => $org,
            'active' => $active,
            'ownActive' => $org->ownPaymentKeys($active) !== null,
            'saved' => collect(GatewayManager::PROVIDERS)->mapWithKeys(fn ($p) => [$p => $this->masked($org, $p)])->all(),
            'webhooks' => collect(GatewayManager::PROVIDERS)->mapWithKeys(fn ($p) => [$p => GatewayManager::webhookUrl($org, $p)])->all(),
            'platformReady' => $this->platformReady($active),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        $provider = $request->validate(['provider' => ['required', Rule::in(GatewayManager::PROVIDERS)]])['provider'];
        $current = (array) ($org->payment_credentials[$provider] ?? []);
        $has = fn (string $field) => filled($current[$field] ?? null);

        $data = $request->validate(match ($provider) {
            'cashfree' => [
                'mode' => ['required', Rule::in(['test', 'live'])],
                'app_id' => ['required', 'string', 'max:120'],
                'secret_key' => [$has('secret_key') ? 'nullable' : 'required', 'string', 'max:255'],
                'webhook_secret' => ['nullable', 'string', 'max:255'],
            ],
            'razorpay' => [
                'key_id' => ['required', 'string', 'max:120', 'regex:/^rzp_(test|live)_[A-Za-z0-9]+$/'],
                'key_secret' => [$has('key_secret') ? 'nullable' : 'required', 'string', 'max:255'],
                'webhook_secret' => [$has('webhook_secret') ? 'nullable' : 'required', 'string', 'max:255'],
            ],
        }, [
            'key_id.regex' => __('A Razorpay Key ID starts with rzp_test_ or rzp_live_.'),
        ]);

        $keys = $current;
        foreach ($data as $field => $value) {
            if (filled($value)) {
                $keys[$field] = trim((string) $value); // blank secret fields keep the saved value
            }
        }
        if ($provider === 'razorpay') {
            $keys['mode'] = ConnectionCheck::razorpayMode($keys['key_id']);
        }
        if (array_diff_assoc(array_diff_key($keys, ['verified_at' => 1]), array_diff_key($current, ['verified_at' => 1]))) {
            $keys['verified_at'] = null; // changed keys must be tested again
        }

        $org->payment_credentials = array_merge((array) $org->payment_credentials, [$provider => $keys]);
        $org->payment_provider = $provider; // the account just connected takes the payments
        $org->save();

        return redirect()->route('settings.payments.show')->with('status', __(':provider keys saved. Run “Test connection” to check them.', ['provider' => ucfirst($provider)]));
    }

    public function test(Request $request, GatewayManager $gateways): RedirectResponse
    {
        $org = $this->org($request);
        $provider = $gateways->resolveProvider($org);
        $keys = $org->ownPaymentKeys($provider);

        if ($keys === null) {
            return back()->withErrors(['test' => __('Save your :provider keys first.', ['provider' => ucfirst($provider)])]);
        }

        $result = ConnectionCheck::run($provider, $keys);
        if (! $result['ok']) {
            return back()->withErrors(['test' => $result['message']]);
        }

        $credentials = (array) $org->payment_credentials;
        $credentials[$provider]['verified_at'] = now()->toIso8601String();
        $org->payment_credentials = $credentials;
        $org->save();

        return back()->with('status', $result['message']);
    }

    /** Remove the org's keys for a provider; payments fall back to the platform account. */
    public function disconnect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, GatewayManager::PROVIDERS, true), 404);
        $org = $this->org($request);

        $credentials = (array) $org->payment_credentials;
        unset($credentials[$provider]);
        $org->payment_credentials = $credentials ?: null;
        if ($org->payment_provider === $provider) {
            $org->payment_provider = collect($credentials)->keys()->first(fn ($p) => $org->ownPaymentKeys($p) !== null);
        }
        $org->save();

        return redirect()->route('settings.payments.show')->with('status', __(':provider disconnected.', ['provider' => ucfirst($provider)]));
    }

    private function org(Request $request): Org
    {
        $org = $request->user()->org;
        abort_if($org === null, 403);

        return $org;
    }

    /**
     * Saved values safe to show: ids in full, secrets as the last 4 characters.
     *
     * @return array<string, mixed>
     */
    private function masked(Org $org, string $provider): array
    {
        $keys = (array) ($org->payment_credentials[$provider] ?? []);
        $mask = fn (?string $v) => filled($v) ? '••••'.substr($v, -4) : null;

        return [
            'connected' => $org->ownPaymentKeys($provider) !== null,
            'mode' => $keys['mode'] ?? 'test',
            'app_id' => $keys['app_id'] ?? null,
            'key_id' => $keys['key_id'] ?? null,
            'secret_key' => $mask($keys['secret_key'] ?? null),
            'key_secret' => $mask($keys['key_secret'] ?? null),
            'webhook_secret' => $mask($keys['webhook_secret'] ?? null),
            'verified_at' => $keys['verified_at'] ?? null,
        ];
    }

    private function platformReady(string $provider): bool
    {
        $cfg = (array) config("eventflow.payment.providers.{$provider}");

        return $provider === 'cashfree'
            ? filled($cfg['app_id'] ?? null) && filled($cfg['secret_key'] ?? null)
            : filled($cfg['key_id'] ?? null) && filled($cfg['key_secret'] ?? null);
    }
}
