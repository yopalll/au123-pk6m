<?php

namespace App\Filament\Owner\Widgets;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OwnerStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $salonIds = $user?->ownedSalonIds() ?? [];

        if (empty($salonIds)) {
            return [
                Stat::make('No salon yet', '—')
                    ->description('Contact VIYGO admin to register your salon.')
                    ->color('gray'),
            ];
        }

        $todayCount = Order::whereIn('id_salon', $salonIds)
            ->whereDate('date_order', today())
            ->whereNotIn('status', [OrderStatus::CANCELED])
            ->count();

        // Revenue covers both `confirmed` (paid via Midtrans, appointment not
        // yet completed) and `success` (appointment delivered). Excludes
        // `pending` (booking exists, payment not collected yet) and `canceled`.
        $thisMonthRevenue = Order::whereIn('id_salon', $salonIds)
            ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::SUCCESS])
            ->whereYear('date_order', now()->year)
            ->whereMonth('date_order', now()->month)
            ->sum('total_pembayaran');

        $pendingCount = Order::whereIn('id_salon', $salonIds)
            ->where('status', OrderStatus::PENDING)
            ->count();

        $serviceCount = Service::whereIn('id_salon', $salonIds)->count();

        $staffCount = Staff::whereIn('id_salon', $salonIds)->count();

        $avgRating = Review::whereIn('id_salon', $salonIds)
            ->where('is_visible', true)
            ->avg('rating');

        return [
            Stat::make("Today's Bookings", number_format($todayCount))
                ->description('Active orders scheduled for today')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Pending Approvals', number_format($pendingCount))
                ->description('Orders awaiting confirmation')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Revenue This Month', '£' . number_format((float) $thisMonthRevenue, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Services', number_format($serviceCount))
                ->description('Active + inactive')
                ->descriptionIcon('heroicon-o-scissors'),

            Stat::make('Staff', number_format($staffCount))
                ->description('On your roster')
                ->descriptionIcon('heroicon-o-users'),

            Stat::make('Average Rating', $avgRating ? number_format((float) $avgRating, 2) : '—')
                ->description('From visible reviews')
                ->descriptionIcon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
