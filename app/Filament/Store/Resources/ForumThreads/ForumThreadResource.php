<?php

namespace App\Filament\Store\Resources\ForumThreads;

use App\Filament\Store\Resources\ForumThreads\Pages\ListForumThreads;
use App\Models\ForumThread;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ForumThreadResource extends Resource
{
    protected static ?string $model = ForumThread::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Komunitas';

    protected static ?string $navigationLabel = 'Moderasi Forum';

    protected static ?string $recordTitleAttribute = 'judul';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_pinned')->boolean()->label('Pin'),
                TextColumn::make('judul')->searchable()->limit(40),
                TextColumn::make('category.nama')->label('Kategori')->badge(),
                TextColumn::make('user.full_name')->label('Author')->searchable(),
                TextColumn::make('reply_count')->label('Balasan'),
                TextColumn::make('like_count')->label('Like'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'published' => 'success', 'hidden' => 'warning', 'deleted' => 'danger', default => 'gray',
                }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id_thread', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'published' => 'Published', 'hidden' => 'Hidden', 'deleted' => 'Deleted',
                ]),
            ])
            ->recordActions([
                Action::make('pin')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->label(fn (ForumThread $r) => $r->is_pinned ? 'Unpin' : 'Pin')
                    ->action(fn (ForumThread $r) => $r->update(['is_pinned' => ! $r->is_pinned])),
                Action::make('hide')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('warning')
                    ->label(fn (ForumThread $r) => $r->status === 'published' ? 'Hide' : 'Publish')
                    ->action(fn (ForumThread $r) => $r->update(['status' => $r->status === 'published' ? 'hidden' : 'published'])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForumThreads::route('/'),
        ];
    }
}
