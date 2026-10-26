<?php

namespace App\Notifications\Http;

use App\Enums\Module;
use App\Mail\CheckInConfirmedMail;
use App\Mail\EventReminderMail;
use App\Mail\RegistrationConfirmedMail;
use App\Mail\TestMail;
use App\Models\Org;
use App\Models\Registration;
use App\Notifications\NotificationDelivery;
use App\Notifications\NotificationModules;
use App\Notifications\NotificationSettings;
use App\Notifications\OrgMailer;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Org Settings -> Email and WhatsApp (owner only): sender details, own SMTP (free on every
 * plan), Platform mail (paid add-on, module email_branding), WhatsApp (paid add-on, module
 * sms_whatsapp), previews and delivery log.
 */
class NotificationSettingsController extends Controller
{
    public const PREVIEWS = [
        'registration-confirmed' => RegistrationConfirmedMail::class,
        'event-reminder' => EventReminderMail::class,
        'checkin-confirmed' => CheckInConfirmedMail::class,
    ];

    public function show(Request $request): View
    {
        $org = $this->org($request);
        $settings = NotificationSettings::for($org);

        return view('notifications.settings', [
            'org' => $org,
            'settings' => $settings,
            'platformMailIncluded' => NotificationSettings::platformMailIncluded($org),
            'heldCount' => NotificationDelivery::query()->where('status', NotificationDelivery::SKIPPED)->where('channel', 'mail')->where('updated_at', '>=', now()->subDays(30))->count(),
            'whatsappModule' => NotificationModules::enabled($org, Module::SmsWhatsapp),
            'platformFrom' => OrgMailer::from($org, $settings->replicate()->fill(['smtp_enabled' => false])),
            'deliveries' => NotificationDelivery::query()->with('registration')->latest('updated_at')->limit(25)->get(),
            'failedCount' => NotificationDelivery::query()->where('status', NotificationDelivery::FAILED)->where('updated_at', '>=', now()->subDays(30))->count(),
            'hasRegistration' => Registration::query()->exists(),
        ]);
    }

    public function updatePlatform(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_name' => ['nullable', 'string', 'max:120'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'send_reminders' => ['boolean'],
            'send_checkin_confirmation' => ['boolean'],
        ]);

        $this->settings($request)->fill($data)->save();

        return back()->with('status', 'platform-saved');
    }

    /** Own SMTP: free on every plan. */
    public function updateSmtp(Request $request): RedirectResponse
    {

        $data = $request->validate([
            'smtp_enabled' => ['boolean'],
            'smtp_host' => ['nullable', 'required_if:smtp_enabled,1', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'required_if:smtp_enabled,1', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_email' => ['nullable', 'required_if:smtp_enabled,1', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
        ]);

        $settings = $this->settings($request);
        if (blank($data['smtp_password'] ?? null)) {
            unset($data['smtp_password']); // blank keeps the stored password
        }
        $settings->fill($data);

        // New server details must be tested again before mail goes through them.
        if ($settings->isDirty(['smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email'])) {
            $settings->smtp_verified_at = null;
        }
        $settings->save();

        return back()->with('status', 'smtp-saved');
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        $to = $request->validate(['test_email' => ['required', 'email']])['test_email'];

        $settings = $this->settings($request);
        if (! $settings->smtpConfigured()) {
            return back()->withErrors(['smtp_test' => __('Save the SMTP host, port and from-email first.')]);
        }

        try {
            Mail::mailer(OrgMailer::registerSmtp($org, $settings))
                ->to($to)
                ->send(new TestMail($org, new Address($settings->smtp_from_email, $settings->smtp_from_name ?: $org->name), __('your SMTP server')));
        } catch (Throwable $e) {
            return back()->withErrors(['smtp_test' => __('Test failed: :error', ['error' => mb_substr($e->getMessage(), 0, 300)])]);
        }

        $settings->forceFill(['smtp_verified_at' => now()])->save();

        return back()->with('status', 'smtp-verified');
    }

    /** Platform mail test: only with the add-on. */
    public function testPlatform(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        abort_unless(NotificationSettings::platformMailIncluded($org), 403);
        $to = $request->validate(['test_email' => ['required', 'email']])['test_email'];
        $settings = $this->settings($request);
        $from = OrgMailer::from($org, $settings->replicate()->fill(['smtp_enabled' => false]));

        try {
            Mail::mailer(config('mail.default'))->to($to)->send(new TestMail($org, new Address($from->getAddress(), $from->getName()), __('the platform mailer')));
        } catch (Throwable $e) {
            return back()->withErrors(['platform_test' => __('Test failed: :error', ['error' => mb_substr($e->getMessage(), 0, 300)])]);
        }

        return back()->with('status', 'platform-tested');
    }

    public function updateWhatsApp(Request $request): RedirectResponse
    {
        $org = $this->org($request);
        abort_unless(NotificationModules::enabled($org, Module::SmsWhatsapp), 403);

        $data = $request->validate([
            'whatsapp_enabled' => ['boolean'],
            'whatsapp_provider' => ['required', Rule::in(array_keys(WhatsAppGatewayFactory::PROVIDERS))],
            'whatsapp_phone_number_id' => ['nullable', 'required_if:whatsapp_enabled,1', 'regex:/^\d{5,30}$/'],
            'whatsapp_token' => ['nullable', 'string', 'max:1000'],
            'whatsapp_template_confirmed' => ['nullable', 'required_if:whatsapp_enabled,1', 'regex:/^[a-z0-9_]{1,120}$/'],
            'whatsapp_template_reminder' => ['nullable', 'regex:/^[a-z0-9_]{1,120}$/'],
            'whatsapp_template_payment' => ['nullable', 'regex:/^[a-z0-9_]{1,120}$/'], // Session N2 payment reminders
            'whatsapp_language' => ['required', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
        ]);

        $settings = $this->settings($request);
        if (blank($data['whatsapp_token'] ?? null)) {
            unset($data['whatsapp_token']);
        }
        $settings->fill($data);
        if ($settings->isDirty(['whatsapp_provider', 'whatsapp_phone_number_id', 'whatsapp_token'])) {
            $settings->whatsapp_verified_at = null;
        }
        if ($settings->whatsapp_enabled && blank($settings->whatsapp_token)) {
            return back()->withErrors(['whatsapp_token' => __('Paste the access token to turn WhatsApp on.')])->withInput();
        }
        $settings->save();

        return back()->with('status', 'whatsapp-saved');
    }

    /** Sends Meta's built-in "hello_world" template, which every WhatsApp Business account has. */
    public function testWhatsApp(Request $request, WhatsAppGatewayFactory $gateways): RedirectResponse
    {
        $org = $this->org($request);
        abort_unless(NotificationModules::enabled($org, Module::SmsWhatsapp), 403);
        $phone = $request->validate(['test_phone' => ['required', 'string', 'max:20']])['test_phone'];

        $settings = $this->settings($request);
        $to = PhoneNumber::toWhatsApp($phone);
        if ($to === null || blank($settings->whatsapp_phone_number_id) || blank($settings->whatsapp_token)) {
            return back()->withErrors(['whatsapp_test' => __('Save the phone number ID and token, and enter a valid mobile number.')]);
        }

        try {
            $gateways->for($settings)->sendTemplate(new WhatsAppMessage($to, 'hello_world', 'en_US'));
        } catch (Throwable $e) {
            return back()->withErrors(['whatsapp_test' => __('Test failed: :error', ['error' => mb_substr($e->getMessage(), 0, 300)])]);
        }

        $settings->forceFill(['whatsapp_verified_at' => now()])->save();

        return back()->with('status', 'whatsapp-verified');
    }

    /** Renders an email with the org's latest registration, for checking the design. */
    public function preview(Request $request, string $type): Response
    {
        $this->org($request);
        $class = self::PREVIEWS[$type] ?? abort(404);
        $registration = Registration::query()->latest('id')->first();
        abort_if($registration === null, 404, __('Create a registration first to preview emails.'));

        return response((new $class($registration->id))->render());
    }

    private function org(Request $request): Org
    {
        return $request->user()->org ?? abort(403);
    }

    private function settings(Request $request): NotificationSettings
    {
        return NotificationSettings::for($this->org($request));
    }
}
