<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One payment reminder on one channel: the registration's reminder timeline and idempotency record. */
class RegistrationReminderLog extends Model
{
    use BelongsToOrg;

    public const QUEUED = 'queued';

    public const SENT = 'sent';

    public const SKIPPED = 'skipped';

    public const FAILED = 'failed';

    protected $fillable = [
        'org_id', 'registration_id', 'kind', 'sequence', 'dispatch_key', 'channel', 'template',
        'status', 'detail', 'provider_id', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'sent_at' => 'immutable_datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
