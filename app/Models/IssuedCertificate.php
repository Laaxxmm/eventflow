<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use App\Models\Tenancy\OrgScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A participant certificate: public number EF-CERT-XXXXXXXX (printed, encoded
 * in the verify QR) and the PDF last rendered for it.
 */
class IssuedCertificate extends Model
{
    use BelongsToOrg;

    protected $table = 'certificates';

    protected $fillable = ['org_id', 'event_id', 'registration_id', 'cert_no', 'template', 'fingerprint', 'pdf_path', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'immutable_datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** The certificate record for a registration, created with a fresh number on first use. */
    public static function for(Registration $r): self
    {
        return static::withoutGlobalScope(OrgScope::class)->firstOrCreate(
            ['registration_id' => $r->id],
            ['org_id' => $r->org_id, 'event_id' => $r->event_id, 'cert_no' => static::newNumber()],
        );
    }

    /** EF-CERT- plus 8 unambiguous characters (no 0/O/1/I). */
    public static function newNumber(): string
    {
        do {
            $no = 'EF-CERT-'.collect(range(1, 8))->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])->implode('');
        } while (static::withoutGlobalScope(OrgScope::class)->where('cert_no', $no)->exists());

        return $no;
    }

    public static function findByNumber(string $certNo): ?self
    {
        return static::withoutGlobalScope(OrgScope::class)->where('cert_no', Str::upper($certNo))->first();
    }
}
