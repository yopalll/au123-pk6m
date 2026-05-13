<?php

namespace App\Filament\Resources\SalonResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $recordTitleAttribute = 'nama';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255)
                    ->label('Service Name'),
                Forms\Components\Select::make('id_kategori')
                    ->relationship('kategori', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Category'),
                Forms\Components\TextInput::make('harga')
                    ->numeric()
                    ->prefix('£')
                    ->required()
                    ->label('Price'),
                Forms\Components\TextInput::make('durasi')
                    ->numeric()
                    ->suffix('min')
                    ->required()
                    ->label('Duration'),
                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->required(),
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Description')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Service')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('kategori.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('harga')
                    ->money('GBP')
                    ->label('Price')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi')
                    ->suffix(' min')
                    ->label('Duration')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        default => 'danger',
                    }),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
