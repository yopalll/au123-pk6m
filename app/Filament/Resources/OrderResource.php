<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string | \UnitEnum | null $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'success' => 'Success',
                    'canceled' => 'Canceled',
                ])->required(),
            Forms\Components\TextInput::make('total_diskon')
                ->numeric()->prefix('£')->label('Discount'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_order')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('kode_order')->label('Order Code')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.first_name')->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-'),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(30),
                Tables\Columns\TextColumn::make('date_order')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('total_pembayaran')->money('GBP')->label('Total'),
                Tables\Columns\TextColumn::make('total_diskon')->money('GBP')->label('Discount'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning', 'confirmed' => 'info',
                        'success' => 'success', 'canceled' => 'danger', default => 'gray',
                    })->sortable(),
            ])
            ->defaultSort('id_order', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending'=>'Pending','confirmed'=>'Confirmed','success'=>'Success','canceled'=>'Canceled']),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('mark_success')->label('Mark Success')
                    ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['status' => 'success']))
                    ->visible(fn (Order $r) => $r->status !== 'success'),
                \Filament\Actions\Action::make('cancel')->label('Cancel')
                    ->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['status' => 'canceled']))
                    ->visible(fn (Order $r) => !in_array($r->status, ['canceled','success'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\OrderDetailsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool { return false; }
}
