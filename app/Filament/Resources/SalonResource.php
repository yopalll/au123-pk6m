<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalonResource\Pages;
use App\Filament\Resources\SalonResource\RelationManagers;
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

    protected static string | \UnitEnum | null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_salon';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Basic Info')
                    ->schema([
                        Forms\Components\TextInput::make('nama_salon')
                            ->required()
                            ->maxLength(255)
                            ->label('Salon Name'),
                        Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->label('Slug'),
                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Description')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'pending' => 'Pending',
                            ])
                            ->required(),
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
                            ->label('Opens'),
                        Forms\Components\TimePicker::make('closing_time')
                            ->label('Closes'),
                    ])->columns(3),

                \Filament\Schemas\Components\Section::make('Metrics')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('total_review')
                            ->disabled()
                            ->numeric()
                            ->label('Total Reviews'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Salon $record): string => static::getUrl('view', ['record' => $record->id_salon]))
            ->columns([
                Tables\Columns\TextColumn::make('id_salon')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_salon')
                    ->label('Salon Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kota.nama_kota')
                    ->label('City')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_review')
                    ->label('Reviews')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id_salon', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\SelectFilter::make('kota')
                    ->relationship('kota', 'nama_kota')
                    ->searchable()
                    ->preload()
                    ->label('City'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn (Salon $record): string => static::getUrl('view', ['record' => $record->id_salon])),
                \Filament\Actions\EditAction::make()
                    ->url(fn (Salon $record): string => static::getUrl('edit', ['record' => $record->id_salon])),
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Salon $record) => $record->update(['status' => 'active']))
                    ->visible(fn (Salon $record) => $record->status !== 'active'),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Salon $record) => $record->update(['status' => 'inactive']))
                    ->visible(fn (Salon $record) => $record->status === 'active'),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\ForceDeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
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

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id_salon';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalons::route('/'),
            'create' => Pages\CreateSalon::route('/create'),
            'view' => Pages\ViewSalon::route('/{record}'),
            'edit' => Pages\EditSalon::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
