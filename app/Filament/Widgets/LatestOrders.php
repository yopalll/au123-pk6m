<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::with(['user', 'salon'])->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('kode_order')
                    ->label('Order Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-'),
                Tables\Columns\TextColumn::make('salon.nama_salon')
                    ->label('Salon')
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_pembayaran')
                    ->label('Total')
                    ->money('GBP'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date order')
                    ->dateTime('d M Y, H:i:s'),
            ]);
    }
}
