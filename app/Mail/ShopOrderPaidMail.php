<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderPaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pesanan Dikonfirmasi — ' . $this->order->kode_order,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop-order-paid',
        );
    }
}
