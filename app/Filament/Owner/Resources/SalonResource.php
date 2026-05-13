<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\SalonResource\Pages;
use App\Filament\Owner\Resources\SalonResource\RelationManagers;
use App\Models\Salon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalonResource extends Resource
{
    protected static ?string $model = Salon::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'Salon';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My Salon';

    protected static ?string $recordTitleAttribute = 'nama_salon';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Basic Info')
                ->schema([
                    Forms\Components\TextInput::make('nama_salon')
                        ->required()
                        ->maxLength(255)
                        ->label('Salon Name'),
                    Forms\Components\TextInput::make('slug')
                        ->disabled()
                        ->dehydrated(false)
                        ->label('Slug'),
                    Forms\Components\Textarea::make('deskripsi')
                        ->label('Description')
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Location')
                ->schema([
                    Forms\Components\Select::make('id_kota')
                        ->relationship('kota', 'nama_kota')
                        ->searchable()
                        ->preload()
                        ->label('City'),
                    Forms\Components\TextInput::make('alamat')
                        ->label('Address'),
                    Forms\Components\TextInput::make('latitude')
                        ->numeric(),
                    Forms\Components\TextInput::make('longitude')
                        ->numeric(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Contact & Hours')
                ->schema([
                    Forms\Components\TextInput::make('phone_number')
                        ->tel()
                        ->label('Phone'),
                    Forms\Components\TimePicker::make('opening_time')
                        ->seconds(false)
                        ->label('Opens'),
                    Forms\Components\TimePicker::make('closing_time')
                        ->seconds(false)
                        ->label('Closes'),
                ])->columns(3),

            \Filament\Schemas\Components\Section::make('Metrics (read-only)')
                ->schema([
                    Forms\Components\TextInput::make('rating')
                        ->disabled()
                        ->dehydrated(false)
                        ->numeric(),
                    Forms\Components\TextInput::make('total_review')
                        ->disabled()
                        ->dehydrated(false)
                        ->numeric()
                        ->label('Total Reviews'),
                    Forms\Components\Select::make('status')
                        ->disabled()
                        ->dehydrated(false)
                        ->options([
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                            'pending'  => 'Pending',
                        ])
                        ->helperText('Status is set by VIYGO admin moderators.'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_salon')
                    ->label('Salon Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('kota.nama_kota')
                    ->label('City')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_review')
                    ->label('Reviews')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id_salon', 'desc')
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalons::route('/'),
            'view'  => Pages\ViewSalon::route('/{record}'),
            'edit'  => Pages\EditSalon::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Owners cannot create new salons via the panel — only admin can.
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id_user', auth()->id())
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
