<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use App\Models\Tenancy\OrgScope;
use App\Payments\PaymentStatus;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use BelongsToOrg, HasFactory;

    protected $fillable = [
        'org_id', 'event_id', 'pass_no', 'status', 'participant_name', 'email', 'phone', 'distance_code',
        'amount_paise', 'gateway', 'gateway_order_id', 'gateway_payment_id', 'answers',
        'pass_png_path', 'pass_pdf_path', 'paid_at', 'checked_in_at', 'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_paise' => 'integer',
            'answers' => 'array',
            'paid_at' => 'immutable_datetime',
            'checked_in_at' => 'immutable_datetime',
            'reminders_opted_out_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Registration $r) {
            // org_id always follows the event, even on public pages with no current org.
            $r->org_id = Event::withoutGlobalScope(OrgScope::class)->whereKey($r->event_id)->value('org_id');
            $r->pass_no ??= static::newPassNo();
        });
    }

    /** New pass number in the prototype's format: EF- + 12 uppercase hex chars. */
    public static function newPassNo(): string
    {
        return config('eventflow.pass.prefix').strtoupper(bin2hex(random_bytes(6)));
    }

    /** Payment reminders sent or queued for this registration (Session N2). */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(RegistrationReminderLog::class)->withoutGlobalScopes();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
