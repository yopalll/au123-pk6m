<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\PromoResource\Pages;
use App\Models\Promo;
use App\Models\Salon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Owner-facing Promo resource. Owners can create discount codes for their
 * own salon(s) only. Promos with id_salon set are validated at booking time
 * to ensure they're only used at the salon that owns them.
 */
class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Discounts';

    protected static ?string $modelLabel = 'Discount';

    protected static ?string $pluralModelLabel = 'Discounts';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Promo Info')->schema([
                Forms\Components\Select::make('id_salon')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->where('id_user', auth()->id())
                        ->pluck('nama_salon', 'id_salon'))
                    ->required()
                    ->searchable()
                    ->helperText('Discount akan hanya valid di salon ini.'),

                Forms\Components\TextInput::make('nama_promo')
                    ->required()
                    ->maxLength(150)
                    ->label('Discount Name')
                    ->helperText('Misal: "Promo Lebaran 2026"'),

                Forms\Components\TextInput::make('kode_promo')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->label('Code')
                    ->placeholder('LEBARAN2026')
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state ?? '')))
                    ->helperText('Customer ketik kode ini saat booking. Otomatis dijadikan UPPERCASE.'),

                Forms\Components\Textarea::make('deskripsi_promo')
                    ->label('Description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Discount Amount')->schema([
                Forms\Components\Select::make('tipe_promo')
                    ->options(['percentage' => 'Percentage (%)', 'fixed' => 'Fixed Amount (£)'])
                    ->required()
                    ->live()
                    ->default('percentage')
                    ->label('Type'),

                Forms\Components\TextInput::make('diskon')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->label(fn (callable $get) => $get('tipe_promo') === 'percentage' ? 'Discount (%)' : 'Discount (£)')
                    ->suffix(fn (callable $get) => $get('tipe_promo') === 'percentage' ? '%' : null)
                    ->prefix(fn (callable $get) => $get('tipe_promo') === 'percentage' ? null : '£')
                    ->helperText('Misal: 10 untuk 10% atau £10'),

                Forms\Components\TextInput::make('diskon_max')
                    ->numeric()
                    ->prefix('£')
                    ->minValue(0)
                    ->label('Max Discount (optional)')
                    ->visible(fn (callable $get) => $get('tipe_promo') === 'percentage')
                    ->helperText('Cap untuk persentase. Kosongkan jika tidak ada batas.'),

                Forms\Components\TextInput::make('min_transaksi')
                    ->numeric()
                    ->prefix('£')
                    ->default(0)
                    ->minValue(0)
                    ->label('Min Transaction')
                    ->helperText('Minimum total booking untuk pakai promo. 0 = tanpa minimum.'),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Availability')->schema([
                Forms\Components\DateTimePicker::make('time_start')
                    ->required()
                    ->default(now())
                    ->label('Start'),

                Forms\Components\DateTimePicker::make('time_expired')
                    ->required()
                    ->default(now()->addMonth())
                    ->after('time_start')
                    ->label('Expires'),

                Forms\Components\TextInput::make('stock')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->label('Stock (Quota)')
                    ->helperText('Berapa kali promo boleh dipakai. 0 = unlimited.'),

                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->required()
                    ->default('active'),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Usage Stats (read-only)')->schema([
                Forms\Components\TextInput::make('used_counter')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->label('Times Used')
                    ->helperText('Otomatis ter-update setiap booking yang pakai promo ini.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id_promo', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nama_promo')
                    ->label('Name')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('kode_promo')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('salon.nama_salon')
                    ->label('Salon')
                    ->limit(20),

                Tables\Columns\TextColumn::make('tipe_promo')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'percentage' ? '%' : 'Fixed'),

                Tables\Columns\TextColumn::make('diskon')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state, $record) => $record->tipe_promo === 'percentage'
                        ? number_format((float) $state, 0) . '%'
                        : '£' . number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('used_counter')
                    ->label('Used')
                    ->formatStateUsing(fn ($state, $record) => $record->stock > 0
                        ? "{$state} / {$record->stock}"
                        : "{$state} / ∞"),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        'expired'  => 'gray',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('time_expired')
                    ->label('Expires')
                    ->date('d M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),

                Tables\Filters\SelectFilter::make('id_salon')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->where('id_user', auth()->id())
                        ->pluck('nama_salon', 'id_salon')),

                Tables\Filters\TrashedFilter::make(),
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
            'index'  => Pages\ListPromos::route('/'),
            'create' => Pages\CreatePromo::route('/create'),
            'edit'   => Pages\EditPromo::route('/{record}/edit'),
        ];
    }

    /**
     * Scope all queries to promos that belong to a salon owned by the
     * current user. Platform-wide promos (id_salon=NULL) are hidden from
     * the owner panel — those are admin-managed only.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()))
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
