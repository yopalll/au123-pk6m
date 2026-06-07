<?php

namespace App\Filament\Store\Resources\Lookbooks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LookbooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
                    ->searchable(),
                TextColumn::make('tema')
                    ->badge()
                    ->searchable(),
                TextColumn::make('slides_count')
                    ->counts('slides')
                    ->label('Slides'),
                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                TextColumn::make('view_count')
                    ->numeric()
                    ->sortable()
                    ->label('Views'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id_lookbook', 'desc')
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
