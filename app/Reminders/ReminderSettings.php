<?php

namespace App\Reminders;

use App\Models\Event;

/**
 * One event's payment-reminder settings (events.payment_reminders over config('reminders.defaults')).
 * Off unless the organiser switches it on for the event.
 */
final readonly class ReminderSettings
{
    /** @param  list<int>  $delaysHours */
    public function __construct(
        public bool $enabled,
        public array $delaysHours,
        public int $max,
        public int $beforeEventHours,
        public string $subject,
        public string $message,
    ) {}

    public static function for(Event $event): self
    {
        return self::fromArray(($event->payment_reminders ?? []) + config('reminders.defaults'));
    }

    public static function fromArray(array $data): self
    {
        $defaults = config('reminders.defaults');
        $delays = array_values(array_map('intval', array_filter((array) ($data['delays_hours'] ?? []), fn ($h) => is_numeric($h) && $h >= 1)));

        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            delaysHours: $delays ?: $defaults['delays_hours'],
            max: max(1, min((int) config('reminders.max_reminders'), (int) ($data['max'] ?? $defaults['max']))),
            beforeEventHours: max(0, (int) ($data['before_event_hours'] ?? $defaults['before_event_hours'])),
            subject: trim((string) ($data['subject'] ?? '')) ?: $defaults['subject'],
            message: trim((string) ($data['message'] ?? '')) ?: $defaults['message'],
        );
    }

    /** Hours to wait before reminder #$n (1-based): after creation for #1, after the previous one after that. */
    public function delayFor(int $n): int
    {
        return $this->delaysHours[$n - 1] ?? $this->delaysHours[array_key_last($this->delaysHours)];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'delays_hours' => $this->delaysHours,
            'max' => $this->max,
            'before_event_hours' => $this->beforeEventHours,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }

    /** {event}, {name}, {date}, {org} in the organiser's subject and message. */
    public static function fill(string $text, array $values): string
    {
        return strtr($text, collect($values)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => (string) $v])->all());
    }
}
