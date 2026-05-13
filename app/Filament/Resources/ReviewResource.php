<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-star';
    protected static string | \UnitEnum | null $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Toggle::make('is_visible')->label('Visible'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_review')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.first_name')->label('User')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-'),
                Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('rating')->sortable(),
                Tables\Columns\TextColumn::make('komentar')->label('Comment')->limit(50),
                Tables\Columns\ToggleColumn::make('is_visible')->label('Visible'),
                Tables\Columns\TextColumn::make('created_at')->date('d M Y')->sortable(),
            ])
            ->defaultSort('id_review', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')->label('Visible'),
                Tables\Filters\SelectFilter::make('rating')
                    ->options(array_combine(range(1,5), range(1,5)))->label('Rating'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('toggle_visibility')
                    ->icon(fn (Review $r) => $r->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->label(fn (Review $r) => $r->is_visible ? 'Hide' : 'Show')
                    ->action(fn (Review $r) => $r->update(['is_visible' => !$r->is_visible])),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('hide')
                        ->label('Hide Selected')->icon('heroicon-o-eye-slash')->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_visible' => false])),
                    \Filament\Actions\BulkAction::make('show')
                        ->label('Show Selected')->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['is_visible' => true])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool { return false; }
}
