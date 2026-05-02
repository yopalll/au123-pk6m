<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UpcomingOrdersTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Upcoming Bookings';

    public function table(Table $table): Table
    {
        $userId = Auth::id();

        return $table
            ->query(
                Order::query()
                    ->with(['user', 'salon'])
                    ->whereHas('salon', fn (Builder $q) => $q->where('id_user', $userId))
                    ->whereDate('date_order', '>=', today())
                    ->whereNotIn('status', ['canceled', 'success'])
                    ->orderBy('date_order')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode_order')->label('Code')->copyable(),
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-'),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\TextColumn::make('date_order')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('total_pembayaran')->label('Total')->money('GBP'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'info',
                        default     => 'gray',
                    }),
            ])
            ->emptyStateHeading('No upcoming bookings')
            ->emptyStateDescription('When customers book a service at your salon, they will show up here.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
