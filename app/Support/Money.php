<?php

namespace App\Support;

/**
 * Currency helper for the salon/booking module.
 *
 * Salon service prices (service.harga, order.total_pembayaran,
 * order_detail.harga_at_order, promo.min_transaksi, ...) are stored in GBP
 * because the seed data was scraped from a UK source. Payments, however, are
 * charged in IDR via Midtrans using a fixed exchange rate.
 *
 * To keep what the user SEES identical to what they PAY, every Rupiah amount
 * shown in the UI must be converted through here — the same single source of
 * truth used by PaymentController::convertGbpToIdr().
 */
class Money
{
    /** Convert a GBP amount to IDR (integer Rupiah), rounded. */
    public static function gbpToIdr(float|int|string $gbp): int
    {
        $rate = (float) config('services.midtrans.exchange_rate', 20000);

        return (int) round((float) $gbp * $rate);
    }

    /** Format a GBP-stored amount as a Rupiah string, e.g. "Rp 1.187.000". */
    public static function rupiah(float|int|string $gbp): string
    {
        return 'Rp ' . number_format(self::gbpToIdr($gbp), 0, ',', '.');
    }
}
