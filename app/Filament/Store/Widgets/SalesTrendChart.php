<?php

namespace App\Filament\Store\Widgets;

use App\Models\ProductOrder;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Penjualan 7 Hari Terakhir';

    protected function getData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->translatedFormat('D');
            $values[] = (float) ProductOrder::whereDate('created_at', $day->toDateString())
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered', 'completed'])
                ->sum('grand_total');
        }

        return [
            'datasets' => [[
                'label' => 'Revenue (Rp)',
                'data' => $values,
                'borderColor' => '#1B2D6B',
                'backgroundColor' => 'rgba(75, 163, 204, 0.2)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
