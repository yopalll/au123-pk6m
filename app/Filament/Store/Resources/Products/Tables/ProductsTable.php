<?php

namespace App\Filament\Store\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_product_category')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_collection')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nama')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('harga')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('harga_diskon')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stok')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('berat_gram')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('volume_ml')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('skin_type')
                    ->searchable(),
                TextColumn::make('skin_concern')
                    ->searchable(),
                TextColumn::make('brand')
                    ->searchable(),
                TextColumn::make('badge')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_review')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_sold')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->boolean(),
                TextColumn::make('fresh_product_id')
                    ->searchable(),
                TextColumn::make('fresh_url')
                    ->searchable(),
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
