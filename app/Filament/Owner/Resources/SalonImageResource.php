<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\SalonImageResource\Pages;
use App\Models\Salon;
use App\Models\SalonImage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalonImageResource extends Resource
{
    protected static ?string $model = SalonImage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static string | \UnitEnum | null $navigationGroup = 'Salon';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Gallery';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('id_salon')
                ->label('Salon')
                ->options(fn () => Salon::query()
                    ->where('id_user', auth()->id())
                    ->pluck('nama_salon', 'id_salon'))
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('image_url')
                ->required()->url()->label('Image URL')->columnSpanFull(),
            Forms\Components\Toggle::make('is_primary')->label('Primary Image'),
            Forms\Components\TextInput::make('urutan')->numeric()->default(0)->label('Sort Order'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')->label('Image')->square()->size(80),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\IconColumn::make('is_primary')->boolean()->label('Primary'),
                Tables\Columns\TextColumn::make('urutan')->label('Order')->sortable(),
            ])
            ->defaultSort('urutan')
            ->filters([
                Tables\Filters\SelectFilter::make('id_salon')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->where('id_user', auth()->id())
                        ->pluck('nama_salon', 'id_salon')),
                Tables\Filters\TernaryFilter::make('is_primary')->label('Primary only'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('mark_primary')
                    ->label('Make Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record) => ! $record->is_primary)
                    ->action(function ($record) {
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalonImages::route('/'),
            'create' => Pages\CreateSalonImage::route('/create'),
            'edit'   => Pages\EditSalonImage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()));
    }
}
