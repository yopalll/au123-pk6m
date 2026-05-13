<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoResource\Pages;
use App\Models\Promo;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';
    protected static string | \UnitEnum | null $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Promo Info')->schema([
                Forms\Components\TextInput::make('nama_promo')->required()->label('Promo Name'),
                Forms\Components\Textarea::make('deskripsi_promo')->label('Description'),
                Forms\Components\TextInput::make('kode_promo')->required()->unique(ignoreRecord: true)->label('Code'),
                Forms\Components\Select::make('tipe_promo')
                    ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount'])->required()->label('Type'),
            ])->columns(2),
            \Filament\Schemas\Components\Section::make('Discount')->schema([
                Forms\Components\TextInput::make('diskon')->numeric()->required()->label('Discount'),
                Forms\Components\TextInput::make('diskon_max')->numeric()->prefix('£')->label('Max Discount'),
                Forms\Components\TextInput::make('min_transaksi')->numeric()->prefix('£')->label('Min Transaction'),
            ])->columns(3),
            \Filament\Schemas\Components\Section::make('Availability')->schema([
                Forms\Components\DateTimePicker::make('time_start')->label('Start'),
                Forms\Components\DateTimePicker::make('time_expired')->label('Expires'),
                Forms\Components\TextInput::make('stock')->numeric()->label('Stock'),
                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])->required(),
            ])->columns(2),
            \Filament\Schemas\Components\Section::make('Usage')->schema([
                Forms\Components\TextInput::make('used_counter')->disabled()->numeric()->label('Used'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_promo')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('nama_promo')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('kode_promo')->label('Code')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('tipe_promo')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('diskon')->suffix(fn ($record) => $record->tipe_promo === 'percentage' ? '%' : ''),
                Tables\Columns\TextColumn::make('stock'),
                Tables\Columns\TextColumn::make('used_counter')->label('Used'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('time_expired')->label('Expires')->date('d M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                Tables\Filters\SelectFilter::make('tipe_promo')
                    ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed'])->label('Type'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\ForceDeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromos::route('/'),
            'create' => Pages\CreatePromo::route('/create'),
            'edit' => Pages\EditPromo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
