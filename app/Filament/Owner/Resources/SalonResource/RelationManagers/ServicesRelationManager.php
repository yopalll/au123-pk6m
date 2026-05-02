<?php

namespace App\Filament\Owner\Resources\SalonResource\RelationManagers;

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
        return $form->schema([
            Forms\Components\Select::make('id_kategori')
                ->relationship('kategori', 'name')
                ->searchable()->preload()->label('Category'),
            Forms\Components\TextInput::make('nama')
                ->required()->label('Service Name'),
            Forms\Components\TextInput::make('harga')
                ->numeric()->prefix('£')->required()->label('Price'),
            Forms\Components\TextInput::make('durasi')
                ->numeric()->suffix('min')->required()->label('Duration'),
            Forms\Components\Select::make('status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->default('active')
                ->required(),
            Forms\Components\Textarea::make('deskripsi')
                ->label('Description')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('Service')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('kategori.name')->label('Category'),
                Tables\Columns\TextColumn::make('harga')->money('GBP')->label('Price')->sortable(),
                Tables\Columns\TextColumn::make('durasi')->suffix(' min')->label('Duration')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
