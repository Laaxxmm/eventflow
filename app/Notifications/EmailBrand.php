<?php

namespace App\Notifications;

use App\Enums\Role;
use App\Landing\Theme;
use App\Models\Registration;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

/**
 * Everything a branded email shows about the organiser, event and registration:
 * logo, colours (event branding, else org colours), event meta and links.
 */
final readonly class EmailBrand
{
    public const TIMEZONE = 'Asia/Kolkata';

    /** How long the "View pass" link in emails and WhatsApp stays valid. */
    public const PASS_LINK_DAYS = 120;

    public function __construct(
        public string $orgName,
        public string $eventName,
        public ?CarbonImmutable $date,
        public string $venue,
        public ?string $logoUrl,
        public string $primary,
        public string $onPrimary,
        public string $secondary,
        public string $onSecondary,
        public ?string $distanceLabel,
        public ?string $eventUrl,
        public ?string $passUrl,
        public ?string $calendarUrl,
        public ?string $replyTo,
    ) {}

    public static function for(Registration $registration): self
    {
        $registration->loadMissing('event.org');
        $event = $registration->event;
        $org = $event?->org;
        $config = $event?->config ?? [];

        $primary = self::colour($config['branding']['primary'] ?? null)
            ?? self::colour($event?->colours['primary'] ?? null)
            ?? self::colour($org?->colours['primary'] ?? null)
            ?? Theme::FALLBACK['primary'];
        $secondary = self::colour($config['branding']['secondary'] ?? null)
            ?? self::colour($event?->colours['secondary'] ?? null)
            ?? self::colour($org?->colours['secondary'] ?? null)
            ?? Theme::FALLBACK['secondary'];

        $date = $event?->date?->setTimezone(self::TIMEZONE);
        $distance = collect($config['distances'] ?? $event?->distances ?? [])
            ->firstWhere('code', $registration->distance_code);

        return new self(
            orgName: $org?->name ?? config('app.name'),
            eventName: $event?->name ?? __('your event'),
            date: $date,
            venue: (string) ($event?->venue ?? ''),
            logoUrl: self::absolute(Theme::url($config['branding']['logo'] ?? null)),
            primary: $primary,
            onPrimary: Theme::ink($primary),
            secondary: $secondary,
            onSecondary: Theme::ink($secondary),
            distanceLabel: $distance['label'] ?? $registration->distance_code,
            eventUrl: ($org && $event && $event->status === 'published') ? url("/{$org->slug}/{$event->slug}") : null,
            passUrl: $registration->exists ? self::passUrl($registration) : null,
            calendarUrl: $date ? self::calendarUrl($event->name, $date, (string) $event->venue) : null,
            replyTo: $org ? self::replyTo($org->id) : null,
        );
    }

    /** Signed link to the participant's pass PDF (also sent on WhatsApp). */
    public static function passUrl(Registration $registration): string
    {
        return URL::temporarySignedRoute(
            'notifications.pass',
            now()->addDays(self::PASS_LINK_DAYS),
            ['registration' => $registration->id],
        );
    }

    /** Google Calendar "add event" link; assumes a 3 hour event. */
    public static function calendarUrl(string $name, CarbonImmutable $start, string $venue): string
    {
        $fmt = fn (CarbonImmutable $d) => $d->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $name,
            'dates' => $fmt($start).'/'.$fmt($start->addHours(3)),
            'location' => $venue,
        ]);
    }

    /** Organiser reply-to: the settings value, else the org owner's email. */
    public static function replyTo(int $orgId): ?string
    {
        $settings = NotificationSettings::withoutGlobalScopes()->where('org_id', $orgId)->first();

        return $settings?->reply_to_email ?: User::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->where('role', Role::Owner)
            ->orderBy('id')
            ->value('email');
    }

    public function dateLine(): ?string
    {
        return $this->date?->format('l, j F Y · g:i A').' IST';
    }

    private static function colour(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    private static function absolute(?string $url): ?string
    {
        return $url === null ? null : (str_starts_with($url, 'http') ? $url : url($url));
    }
}
