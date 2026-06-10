<?php

namespace App\Filament\Owner\Resources;

use App\Constants\OrderStatus;
use App\Filament\Owner\Resources\OrderResource\Pages;
use App\Filament\Owner\Resources\OrderResource\RelationManagers\OrderDetailsRelationManager;
use App\Models\Order;
use App\Models\Salon;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Bookings';

    protected static ?string $recordTitleAttribute = 'kode_order';

    // Booking dibuat oleh customer dari sisi web, bukan dari panel owner.
    public static function canCreate(): bool
    {
        return false;
    }

    /** Badge navigasi: jumlah booking yang menunggu tindakan owner. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Booking')
                ->columns(2)
                ->schema([
                    TextEntry::make('kode_order')->label('Kode Booking')->copyable(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => static::statusLabel($state))
                        ->color(fn (string $state) => static::statusColor($state)),
                    TextEntry::make('user.full_name')->label('Pelanggan'),
                    TextEntry::make('salon.nama_salon')->label('Salon'),
                    TextEntry::make('date_order')->label('Tanggal Booking')->date('d M Y'),
                    TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y, H:i'),
                ]),

            Section::make('Pembayaran')
                ->columns(2)
                ->schema([
                    TextEntry::make('pembayaran.metode_pembayaran')
                        ->label('Metode')
                        ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '—'),
                    TextEntry::make('pembayaran.status_pembayaran')
                        ->label('Status Pembayaran')
                        ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '—'),
                    TextEntry::make('total_diskon')->label('Diskon')->money('GBP'),
                    TextEntry::make('total_pembayaran')->label('Total')->money('GBP')->weight('bold'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_order', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode_order')
                    ->label('Kode')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Pelanggan')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(25),
                Tables\Columns\TextColumn::make('date_order')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('total_pembayaran')->label('Total')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => static::statusLabel($state))
                    ->color(fn (string $state) => static::statusColor($state)),
            ])
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        OrderStatus::PENDING   => 'Menunggu Konfirmasi',
                        OrderStatus::CONFIRMED => 'Terkonfirmasi (Dibayar)',
                        OrderStatus::SUCCESS   => 'Selesai',
                        OrderStatus::CANCELED  => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('id_salon')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->where('id_user', auth()->id())
                        ->pluck('nama_salon', 'id_salon')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),

                // Konfirmasi booking yang masih menunggu (mis. pembayaran manual/transfer).
                Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi booking ini?')
                    ->modalDescription('Booking akan ditandai terkonfirmasi dan pelanggan dapat melihatnya.')
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::CONFIRMED]);
                        Notification::make()->title('Booking dikonfirmasi.')->success()->send();
                    }),

                // Tandai treatment sudah selesai dikerjakan → status success.
                Actions\Action::make('complete')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::CONFIRMED)
                    ->requiresConfirmation()
                    ->modalHeading('Tandai treatment selesai?')
                    ->modalDescription('Pelanggan akan melihat status "Treatment Selesai" dan dapat memberikan ulasan.')
                    ->action(function (Order $record) {
                        static::markComplete($record);
                        Notification::make()->title('Booking ditandai selesai.')->success()->send();
                    }),

                // Batalkan booking yang belum selesai.
                Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED], true))
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan booking ini?')
                    ->modalDescription('Tindakan ini tidak bisa dibatalkan. Pastikan sudah berkoordinasi dengan pelanggan.')
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::CANCELED]);
                        $record->details()->update(['status' => 'cancelled']);
                        Notification::make()->title('Booking dibatalkan.')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('completeSelected')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai booking terpilih sebagai selesai?')
                        ->action(function (Collection $records) {
                            $done = 0;
                            foreach ($records as $record) {
                                if ($record->status === OrderStatus::CONFIRMED) {
                                    static::markComplete($record);
                                    $done++;
                                }
                            }
                            Notification::make()
                                ->title("{$done} booking ditandai selesai.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Belum ada booking')
            ->emptyStateDescription('Booking dari pelanggan akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view'  => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'salon', 'pembayaran'])
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()));
    }

    // ──── Helpers ───────────────────────────────────────────

    /** Tandai order selesai + semua item-nya completed. */
    protected static function markComplete(Order $record): void
    {
        $record->update(['status' => OrderStatus::SUCCESS]);
        $record->details()->update(['status' => 'completed']);
    }

    protected static function statusLabel(string $state): string
    {
        return match ($state) {
            OrderStatus::PENDING   => 'Menunggu Konfirmasi',
            OrderStatus::CONFIRMED => 'Terkonfirmasi',
            OrderStatus::SUCCESS   => 'Selesai',
            OrderStatus::CANCELED  => 'Dibatalkan',
            default                => ucfirst($state),
        };
    }

    protected static function statusColor(string $state): string
    {
        return match ($state) {
            OrderStatus::PENDING   => 'warning',
            OrderStatus::CONFIRMED => 'info',
            OrderStatus::SUCCESS   => 'success',
            OrderStatus::CANCELED  => 'danger',
            default                => 'gray',
        };
    }
}
