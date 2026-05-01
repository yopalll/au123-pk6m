<?php

namespace App\Filament\Resources\SalonResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'image_url';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('image_url')
                    ->required()
                    ->url()
                    ->label('Image URL')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Image'),
                Forms\Components\TextInput::make('urutan')
                    ->numeric()
                    ->label('Sort Order'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary'),
                Tables\Columns\TextColumn::make('urutan')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
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
