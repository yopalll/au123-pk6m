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
            // Upload helper — not stored in DB directly. Fills `image_url`
            // below with the public URL once the file is uploaded.
            Forms\Components\FileUpload::make('upload_file')
                ->label('Upload from your device')
                ->image()
                ->disk('public')
                ->directory('salon-images')
                ->visibility('public')
                ->maxSize(5120) // 5 MB
                ->helperText('Pilih file gambar (jpg/png/webp, maks 5 MB). URL akan terisi otomatis di bawah.')
                ->columnSpanFull()
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    // $state during live update is a TemporaryUploadedFile (or
                    // array of them). Store it to public disk now and set the
                    // final public URL into the image_url field.
                    $file = is_array($state) ? ($state[array_key_first($state)] ?? null) : $state;

                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $path = $file->store('salon-images', 'public');
                        $set('image_url', \Illuminate\Support\Facades\Storage::disk('public')->url($path));
                    }
                }),

            Forms\Components\TextInput::make('image_url')
                ->required()->url()->label('Image URL')->columnSpanFull()
                ->helperText('Terisi otomatis jika Anda upload di atas, atau paste URL publik dari CDN.'),

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
