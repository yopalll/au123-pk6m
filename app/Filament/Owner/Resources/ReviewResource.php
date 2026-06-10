<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\ReviewResource\Pages;
use App\Models\Review;
use App\Models\Salon;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-star';

    protected static string | \UnitEnum | null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $modelLabel = 'Review';

    protected static ?string $pluralModelLabel = 'Reviews';

    public static function canCreate(): bool
    {
        return false;
    }

    /** Badge: jumlah review yang belum dibalas. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->whereNull('owner_reply')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ulasan Pelanggan')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.full_name')->label('Pelanggan'),
                    TextEntry::make('salon.nama_salon')->label('Salon'),
                    TextEntry::make('rating')->label('Rating')
                        ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state).str_repeat('☆', 5 - (int) $state)),
                    TextEntry::make('created_at')->label('Tanggal')->dateTime('d M Y, H:i'),
                    TextEntry::make('komentar')->label('Komentar')->columnSpanFull()->placeholder('—'),
                ]),
            Section::make('Balasan Kamu')
                ->schema([
                    TextEntry::make('owner_reply')->label('Balasan')->columnSpanFull()
                        ->placeholder('Belum dibalas.'),
                    TextEntry::make('owner_reply_at')->label('Dibalas pada')->dateTime('d M Y, H:i')
                        ->visible(fn (Review $r) => $r->owner_reply_at !== null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id_review', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Pelanggan')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(22),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state.' ★')
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('komentar')->label('Komentar')->limit(40)->wrap(),
                Tables\Columns\IconColumn::make('owner_reply')
                    ->label('Dibalas')
                    ->boolean()
                    ->getStateUsing(fn (Review $r) => filled($r->owner_reply)),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date('d M Y')->sortable(),
            ])
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([5 => '5 ★', 4 => '4 ★', 3 => '3 ★', 2 => '2 ★', 1 => '1 ★']),
                Tables\Filters\Filter::make('belum_dibalas')
                    ->label('Belum dibalas')
                    ->query(fn (Builder $q) => $q->whereNull('owner_reply')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\Action::make('reply')
                    ->label(fn (Review $r) => filled($r->owner_reply) ? 'Edit Balasan' : 'Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->modalHeading('Balas Ulasan')
                    ->modalDescription(fn (Review $r) => 'Rating '.$r->rating.'★ — '.($r->komentar ?: 'tanpa komentar'))
                    ->fillForm(fn (Review $r) => ['owner_reply' => $r->owner_reply])
                    ->schema([
                        Forms\Components\Textarea::make('owner_reply')
                            ->label('Balasan kamu')
                            ->placeholder('Terima kasih atas ulasannya! ...')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function (Review $r, array $data) {
                        $r->update([
                            'owner_reply'    => $data['owner_reply'],
                            'owner_reply_at' => now(),
                        ]);
                        Notification::make()->title('Balasan tersimpan.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'view'  => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'salon'])
            ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()));
    }
}
