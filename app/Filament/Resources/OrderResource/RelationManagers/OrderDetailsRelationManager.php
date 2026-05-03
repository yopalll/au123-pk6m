<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.nama')->label('Service'),
                Tables\Columns\TextColumn::make('staff.name')->label('Staff'),
                Tables\Columns\TextColumn::make('start_time')->label('Start'),
                Tables\Columns\TextColumn::make('end_time')->label('End'),
                Tables\Columns\TextColumn::make('harga_at_order')->money('GBP')->label('Price'),
                Tables\Columns\TextColumn::make('subtotal')->money('GBP'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success', 'canceled' => 'danger', default => 'warning',
                    }),
            ]);
    }

    public function isReadOnly(): bool { return true; }
}
