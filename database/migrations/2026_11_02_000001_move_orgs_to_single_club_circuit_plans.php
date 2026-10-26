<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Plans became Single Event / Club Season / Circuit Season / Custom (config/plans.php).
 * Orgs on the earlier placeholder keys move to the nearest successor.
 */
return new class extends Migration
{
    private const MAP = ['starter' => 'club', 'growth' => 'circuit', 'scale' => 'custom'];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('orgs')->where('plan', $old)->update(['plan' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('orgs')->where('plan', $new)->update(['plan' => $old]);
        }
    }
};
