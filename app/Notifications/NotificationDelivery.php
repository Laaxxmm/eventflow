<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * Outcome of one notification (per registration, channel and type), shown to organisers.
 */
class NotificationDelivery extends Model
{
    use BelongsToOrg;

    public const QUEUED = 'queued';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const SKIPPED = 'skipped';

    protected $fillable = [
        'org_id', 'registration_id', 'channel', 'type', 'recipient', 'status',
        'attempts', 'provider_message_id', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'immutable_datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /** Find or start the row for this registration + channel + type (tests have no registration). */
    public static function track(?int $orgId, ?int $registrationId, string $channel, string $type, string $recipient): self
    {
        $query = static::withoutGlobalScopes();

        $delivery = $registrationId === null
            ? new static
            : $query->firstOrNew(['registration_id' => $registrationId, 'channel' => $channel, 'type' => $type]);

        $delivery->fill([
            'org_id' => $orgId,
            'registration_id' => $registrationId,
            'channel' => $channel,
            'type' => $type,
            'recipient' => $recipient,
        ]);
        $delivery->status ??= self::QUEUED;

        return $delivery;
    }

    public function markSent(?string $providerMessageId = null): void
    {
        $this->forceFill([
            'status' => self::SENT,
            'attempts' => $this->attempts + 1,
            'provider_message_id' => $providerMessageId,
            'error' => null,
            'sent_at' => now(),
        ])->save();
    }

    public function markFailed(Throwable|string $error, bool $final = false): void
    {
        $message = $error instanceof Throwable ? $error::class.': '.$error->getMessage() : $error;

        $this->forceFill([
            'status' => $final ? self::FAILED : ($this->status === self::SENT ? self::SENT : self::QUEUED),
            'attempts' => $this->attempts + 1,
            'error' => mb_substr($message, 0, 2000),
        ])->save();
    }

    public function markSkipped(string $reason): void
    {
        $this->forceFill(['status' => self::SKIPPED, 'error' => $reason])->save();
    }
}
