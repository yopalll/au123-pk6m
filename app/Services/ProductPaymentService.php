<?php

namespace App\Services;

use App\Mail\ShopOrderPaidMail;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductPembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single source of truth for settling a shop (ProductOrder) payment.
 *
 * Both entry points feed an AUTHORITATIVE Midtrans status into this service:
 *   - ProductPaymentController::finish()  → after server-side Transaction::status()
 *   - PaymentController::webhook()         → after SHA512 signature verification
 *
 * The client-supplied transaction_status is never trusted; only the status
 * fetched/verified from Midtrans reaches here. settle() is idempotent and
 * concurrency-safe (row lock), so the browser callback and the webhook can
 * both fire without double-decrementing stock or downgrading a paid order.
 */
class ProductPaymentService
{
    /**
     * Apply a verified Midtrans status to a product order.
     *
     * @param  array{payment_type?: ?string, transaction_id?: ?string, gross_amount?: float|int|null}  $meta
     */
    public function settle(ProductOrder $order, string $transactionStatus, ?string $fraudStatus = null, array $meta = []): void
    {
        $isPaid = $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && $fraudStatus !== 'challenge');

        $sendMail = false;

        DB::transaction(function () use ($order, $transactionStatus, $isPaid, $meta, &$sendMail) {
            // Lock the order row so concurrent finish()/webhook deliveries serialise.
            $order = ProductOrder::where('id_product_order', $order->id_product_order)
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            $payment = ProductPembayaran::firstOrNew(['id_product_order' => $order->id_product_order]);
            $payment->id_user = $order->id_user;
            if (! empty($meta['payment_type'])) {
                $payment->metode = $meta['payment_type'];
            }
            if (! empty($meta['transaction_id'])) {
                $payment->midtrans_transaction_id = $meta['transaction_id'];
            }

            // Already finalised → idempotent. Never double-decrement stock,
            // never downgrade a paid order from a late failure notification.
            if ($order->status !== 'pending') {
                if ($isPaid && $payment->status !== 'success') {
                    $payment->status = 'success';
                    $payment->paid_at = $payment->paid_at ?? now();
                }
                $payment->save();

                return;
            }

            if ($isPaid) {
                $order->status = 'paid';
                $order->save();

                foreach ($order->items as $item) {
                    Product::where('id_product', $item->id_product)->decrement('stok', $item->qty);
                    Product::where('id_product', $item->id_product)->increment('total_sold', $item->qty);
                }

                $payment->status = 'success';
                $payment->paid_at = now();
                $sendMail = true;

                Log::info('Shop order paid', ['kode' => $order->kode_order]);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel', 'failure'], true)) {
                // Keep order at 'pending' so the user can retry payment.
                $payment->status = 'failed';
            } else {
                // 'pending' or capture-with-challenge: awaiting confirmation.
                $payment->status = 'pending';
            }

            $payment->save();
        });

        // Send outside the transaction so a mail failure never rolls back a settled payment.
        if ($sendMail) {
            $order->load(['user', 'items', 'address']);
            Mail::to($order->user->email)->queue(new ShopOrderPaidMail($order));
        }
    }
}
