<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KotaResource\Pages;
use App\Models\Kota;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class KotaResource extends Resource
{
    protected static ?string $model = Kota::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';
    protected static string | \UnitEnum | null $navigationGroup = 'Marketplace';
    protected static ?int $navigationSort = 4;
    protected static ?string $label = 'City';
    protected static ?string $pluralLabel = 'Cities';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_kota')->required()->label('City Name'),
            Forms\Components\TextInput::make('provinsi')->label('Province / Region'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_kota')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('nama_kota')->label('City')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provinsi')->label('Region')->searchable(),
                Tables\Columns\TextColumn::make('salons_count')->counts('salons')->label('Salons')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKotas::route('/'),
            'edit' => Pages\EditKota::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool { return false; }
}
