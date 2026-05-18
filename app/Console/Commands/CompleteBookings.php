<?php

namespace App\Console\Commands;

use App\Constants\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Moves confirmed (paid) bookings to 'success' once their appointment date
 * has passed. Run daily via the scheduler.
 *
 * Why: PaymentController marks orders 'confirmed' on payment. There is no
 * real-time signal from the salon when a service is delivered, so we use
 * date_order < today as a proxy for "service completed". This unlocks the
 * review flow for those customers.
 */
class CompleteBookings extends Command
{
    protected $signature   = 'bookings:complete {--dry-run : Report without writing}';
    protected $description = 'Transition past confirmed bookings to success';

    public function handle(): int
    {
        // BUG-A06: Use OrderStatus constants instead of hardcoded strings.
        $query = Order::where('status', OrderStatus::CONFIRMED)
            ->whereDate('date_order', '<', now()->toDateString());

        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run — {$count} booking(s) would be marked as success.");
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('No confirmed bookings to complete.');
            return self::SUCCESS;
        }

        $query->update(['status' => OrderStatus::SUCCESS]);

        Log::info('bookings:complete', ['completed' => $count]);
        $this->info("Marked {$count} booking(s) as success.");

        return self::SUCCESS;
    }
}
