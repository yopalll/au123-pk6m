<?php

namespace App\Filament\Owner\Resources;

use App\Constants\OrderStatus;
use App\Filament\Owner\Resources\OrderResource\Pages;
use App\Filament\Owner\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Orders';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    OrderStatus::PENDING   => 'Pending',
                    OrderStatus::CONFIRMED => 'Confirmed',
                    OrderStatus::SUCCESS   => 'Success',
                    OrderStatus::CANCELED  => 'Canceled',
                ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_order')->label('Code')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.first_name')->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['users.first_name', 'users.last_name']),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\TextColumn::make('date_order')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('total_pembayaran')->money('GBP')->label('Total'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        OrderStatus::PENDING   => 'warning',
                        OrderStatus::CONFIRMED => 'info',
                        OrderStatus::SUCCESS   => 'success',
                        OrderStatus::CANCELED  => 'danger',
                        default                => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('date_order', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        OrderStatus::PENDING   => 'Pending',
                        OrderStatus::CONFIRMED => 'Confirmed',
                        OrderStatus::SUCCESS   => 'Success',
                        OrderStatus::CANCELED  => 'Canceled',
                    ]),
                Tables\Filters\Filter::make('date_order')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('date_order', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('date_order', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['status' => OrderStatus::CONFIRMED]))
                    ->visible(fn (Order $r) => $r->status === OrderStatus::PENDING),
                Tables\Actions\Action::make('mark_success')
                    ->label('Mark Success')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['status' => OrderStatus::SUCCESS]))
                    ->visible(fn (Order $r) => in_array($r->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED])),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['status' => OrderStatus::CANCELED]))
                    ->visible(fn (Order $r) => ! in_array($r->status, [OrderStatus::CANCELED, OrderStatus::SUCCESS])),
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
            'view'  => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool { return false; }

    public static function canEdit($record): bool { return false; }

    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()))
            ->with(['user', 'salon']);
    }
}
