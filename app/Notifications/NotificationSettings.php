<?php

namespace App\Notifications;

use App\Enums\Module;
use App\Models\Org;
use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;

/**
 * Org Settings -> Notifications. One row per org; firstOrNew() gives the defaults.
 *
 * @property bool $smtp_enabled
 * @property bool $whatsapp_enabled
 */
class NotificationSettings extends Model
{
    use BelongsToOrg;

    protected $table = 'notification_settings';

    protected $fillable = [
        'org_id', 'from_name', 'reply_to_email', 'send_reminders', 'send_checkin_confirmation',
        'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        'smtp_from_email', 'smtp_from_name', 'smtp_verified_at',
        'whatsapp_enabled', 'whatsapp_provider', 'whatsapp_phone_number_id', 'whatsapp_token',
        'whatsapp_template_confirmed', 'whatsapp_template_reminder', 'whatsapp_template_payment', 'whatsapp_language', 'whatsapp_verified_at',
    ];

    protected $hidden = ['smtp_password', 'whatsapp_token'];

    protected $attributes = [
        'send_reminders' => true,
        'send_checkin_confirmation' => false,
        'smtp_enabled' => false,
        'whatsapp_enabled' => false,
        'whatsapp_provider' => 'meta',
        'whatsapp_language' => 'en',
    ];

    protected function casts(): array
    {
        return [
            'send_reminders' => 'boolean',
            'send_checkin_confirmation' => 'boolean',
            'smtp_enabled' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
            'smtp_verified_at' => 'immutable_datetime',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_token' => 'encrypted',
            'whatsapp_verified_at' => 'immutable_datetime',
        ];
    }

    public static function for(Org $org): self
    {
        return static::withoutGlobalScopes()->firstOrNew(['org_id' => $org->id]);
    }

    /** Every field own SMTP needs is filled in. */
    public function smtpConfigured(): bool
    {
        return filled($this->smtp_host) && filled($this->smtp_port) && filled($this->smtp_from_email);
    }

    /** The org's own SMTP is used for participant mail. Free on every plan: no add-on needed. */
    public function usesCustomSmtp(Org $org): bool
    {
        return $this->smtp_enabled
            && $this->smtpConfigured()
            && $this->smtp_verified_at !== null;
    }

    /** Platform mail (Brevo / SES via Indefine): only with the paid add-on (module email_branding). */
    public function usesPlatformMail(Org $org): bool
    {
        return ! $this->usesCustomSmtp($org) && self::platformMailIncluded($org);
    }

    public static function platformMailIncluded(Org $org): bool
    {
        return NotificationModules::enabled($org, Module::EmailBranding);
    }

    /** Participant emails go out (own SMTP or Platform mail); otherwise they are held and logged. */
    public function canSendParticipantMail(Org $org): bool
    {
        return $this->usesCustomSmtp($org) || $this->usesPlatformMail($org);
    }

    public function whatsappConfigured(): bool
    {
        return filled($this->whatsapp_phone_number_id) && filled($this->whatsapp_token)
            && filled($this->whatsapp_template_confirmed);
    }

    /** WhatsApp messages go out for this org. */
    public function usesWhatsApp(Org $org): bool
    {
        return $this->whatsapp_enabled
            && $this->whatsappConfigured()
            && NotificationModules::enabled($org, Module::SmsWhatsapp);
    }
}
