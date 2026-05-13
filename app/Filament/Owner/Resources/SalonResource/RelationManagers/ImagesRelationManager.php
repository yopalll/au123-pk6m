<?php

namespace App\Filament\Owner\Resources\SalonResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'image_url';

    protected static ?string $title = 'Gallery';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('image_url')
                ->required()->url()->label('Image URL')->columnSpanFull()
                ->helperText('Paste a public URL or upload to your CDN first.'),
            Forms\Components\Toggle::make('is_primary')
                ->label('Primary Image')
                ->helperText('The primary image is used as the cover photo on listings.'),
            Forms\Components\TextInput::make('urutan')
                ->numeric()->default(0)->label('Sort Order'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')->label('Image')->square()->size(80),
                Tables\Columns\IconColumn::make('is_primary')->boolean()->label('Primary'),
                Tables\Columns\TextColumn::make('urutan')->label('Order')->sortable(),
            ])
            ->defaultSort('urutan')
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('mark_primary')
                    ->label('Make Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record) => ! $record->is_primary)
                    ->action(function ($record) {
                        // Demote any existing primary image, then promote this one.
                        $record->salon
                            ->images()
                            ->where('is_primary', true)
                            ->update(['is_primary' => false]);
                        $record->update(['is_primary' => true]);
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
