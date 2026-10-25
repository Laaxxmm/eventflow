<?php

namespace App\Models;

use App\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = [
        'event_id', 'pass_no', 'status', 'participant_name', 'email', 'phone', 'distance_code',
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
        ];
    }

    /** New pass number in the prototype's format: EF- + 12 uppercase hex chars. */
    public static function newPassNo(): string
    {
        return config('eventflow.pass.prefix').strtoupper(bin2hex(random_bytes(6)));
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
