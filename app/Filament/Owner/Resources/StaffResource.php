<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\StaffResource\Pages;
use App\Filament\Owner\Resources\StaffResource\RelationManagers;
use App\Models\Salon;
use App\Models\Staff;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Staff';

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
            Forms\Components\TextInput::make('name')
                ->required()->maxLength(255),
            Forms\Components\TextInput::make('profile_url')
                ->url()->label('Profile URL')->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\TextColumn::make('schedules_count')
                    ->counts('schedules')
                    ->label('Schedules'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
            ])
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit'   => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()))
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
