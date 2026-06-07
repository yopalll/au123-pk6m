<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductOrderController extends Controller
{
    public function index()
    {
        $orders = ProductOrder::where('id_user', auth()->id())
            ->with('items.product.primaryImage')
            ->latest()->paginate(10);

        return view('shop.pesanan-list', compact('orders'));
    }

    public function show(string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['items.product.primaryImage', 'address', 'pembayaran'])
            ->firstOrFail();

        return view('shop.pesanan-detail', compact('order'));
    }

    public function invoice(string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['items', 'address', 'user', 'pembayaran'])
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.invoice-produk', compact('order'))->setPaper('a4', 'portrait');

        return $pdf->download("VIYGO-Shop-Invoice-{$kode}.pdf");
    }
}
