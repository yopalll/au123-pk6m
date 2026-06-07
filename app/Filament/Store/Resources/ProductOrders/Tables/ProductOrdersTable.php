<?php

namespace App\Filament\Store\Resources\ProductOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_user')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_address')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_promo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kode_order')
                    ->searchable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('biaya_kirim')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_diskon')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('poin_digunakan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('potongan_poin')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kurir')
                    ->searchable(),
                TextColumn::make('layanan_kirim')
                    ->searchable(),
                TextColumn::make('estimasi_tiba')
                    ->searchable(),
                TextColumn::make('resi')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
