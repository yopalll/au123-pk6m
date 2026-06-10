<?php

namespace App\Filament\Store\Resources\EmptyReturns;

use App\Filament\Store\Resources\EmptyReturns\Pages\ListEmptyReturns;
use App\Models\EmptyReturn;
use App\Services\PointService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmptyReturnResource extends Resource
{
    protected static ?string $model = EmptyReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|\UnitEnum|null $navigationGroup = 'Empty Return';

    protected static ?string $recordTitleAttribute = 'nama_produk';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_produk')->disabled(),
            TextInput::make('jumlah')->disabled(),
            TextInput::make('status')->disabled(),
            Textarea::make('catatan_admin')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_return')->label('ID')->sortable(),
                ImageColumn::make('foto')
                    ->label('Foto')
                    ->square()
                    // URL penuh berbasis host request (bukan APP_URL) agar tidak salah
                    // port saat `php artisan serve`. ImageColumn meneruskan URL valid apa adanya.
                    ->getStateUsing(fn (EmptyReturn $r) => ($p = $r->photos->first())
                        ? url('/storage/'.ltrim($p->photo_url, '/'))
                        : null)
                    ->extraImgAttributes(['class' => 'object-cover'])
                    ->tooltip(fn (EmptyReturn $r) => $r->photos->count() > 1 ? $r->photos->count().' foto — klik "Lihat Foto"' : null),
                TextColumn::make('user.full_name')->label('Customer')->searchable(),
                TextColumn::make('nama_produk')->searchable()->limit(30),
                TextColumn::make('jumlah')->label('Botol'),
                TextColumn::make('metode')->badge(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning', default => 'gray',
                }),
                TextColumn::make('poin_earned')->label('Poin'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id_return', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                    'picked_up' => 'Picked up', 'received' => 'Received',
                ]),
            ])
            ->recordActions([
                Action::make('photos')
                    ->label('Lihat Foto')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->color('info')
                    ->visible(fn (EmptyReturn $r) => $r->photos->isNotEmpty())
                    ->modalHeading(fn (EmptyReturn $r) => 'Foto Botol — '.$r->nama_produk)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (EmptyReturn $r) => view('filament.store.empty-return-photos', [
                        'record' => $r->load('photos'),
                    ])),
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (EmptyReturn $r) => $r->status === 'pending')
                    ->schema([
                        TextInput::make('poin_earned')
                            ->label('Poin diberikan')
                            ->numeric()
                            ->default(fn (EmptyReturn $r) => $r->jumlah * 5)
                            ->required(),
                        Textarea::make('catatan_admin')->label('Catatan (opsional)'),
                    ])
                    ->action(function (EmptyReturn $r, array $data) {
                        $r->update([
                            'status' => 'approved',
                            'poin_earned' => $data['poin_earned'],
                            'catatan_admin' => $data['catatan_admin'] ?? null,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        PointService::creditFromEmptyReturn($r->refresh());
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (EmptyReturn $r) => $r->status === 'pending')
                    ->schema([
                        Textarea::make('catatan_admin')->label('Alasan penolakan')->required(),
                    ])
                    ->action(fn (EmptyReturn $r, array $data) => $r->update([
                        'status' => 'rejected',
                        'catatan_admin' => $data['catatan_admin'],
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmptyReturns::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['photos', 'user']);
    }
}
