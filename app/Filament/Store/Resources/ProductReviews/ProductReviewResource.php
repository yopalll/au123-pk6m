<?php

namespace App\Filament\Store\Resources\ProductReviews;

use App\Filament\Store\Resources\ProductReviews\Pages\EditProductReview;
use App\Filament\Store\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Store\Resources\ProductReviews\Schemas\ProductReviewForm;
use App\Filament\Store\Resources\ProductReviews\Tables\ProductReviewsTable;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    // PRD 10.3 — Moderasi: Read + Update (hide) + Delete, bukan Create.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProductReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'edit' => EditProductReview::route('/{record}/edit'),
        ];
    }
}
