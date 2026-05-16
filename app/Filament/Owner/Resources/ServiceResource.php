<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\ServiceResource\Pages;
use App\Models\Salon;
use App\Models\Service;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scissors';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Services';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('Service')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\TextColumn::make('kategori.name')->label('Category'),
                Tables\Columns\TextColumn::make('harga')->money('GBP')->label('Price')->sortable(),
                Tables\Columns\TextColumn::make('durasi')->suffix(' min')->label('Duration')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
            ])
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                Tables\Filters\SelectFilter::make('id_salon')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->where('id_user', auth()->id())
                        ->pluck('nama_salon', 'id_salon')),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()))
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
