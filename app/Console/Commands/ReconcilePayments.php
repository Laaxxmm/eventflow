<?php

namespace App\Console\Commands;

use App\Events\RegistrationPaid;
use App\Models\Registration;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Poll pending registrations older than 15 minutes and mark paid if the provider says so';

    public function handle(GatewayManager $gateways): int
    {
        $cutoff = now()->subMinutes(15);

        $pending = Registration::query()
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<=', $cutoff)
            ->whereNotNull('gateway_order_id')
            ->get();

        $updated = 0;

        foreach ($pending as $registration) {
            try {
                $gateway = $gateways->for($registration);
                $status = $gateway->fetchStatus($registration->gateway_order_id);

                if ($status === PaymentStatus::Paid) {
                    $registration->update([
                        'status' => PaymentStatus::Paid,
                        'paid_at' => now(),
                    ]);
                    event(new RegistrationPaid($registration->id));
                    $updated++;
                }
            } catch (\Throwable $e) {
                $this->error("Failed to reconcile registration {$registration->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reconciled {$updated} of {$pending->count()} pending registrations.");

        return self::SUCCESS;
    }
}
