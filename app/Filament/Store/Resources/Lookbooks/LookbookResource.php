<?php

namespace App\Filament\Store\Resources\Lookbooks;

use App\Filament\Store\Resources\Lookbooks\Pages\CreateLookbook;
use App\Filament\Store\Resources\Lookbooks\Pages\EditLookbook;
use App\Filament\Store\Resources\Lookbooks\Pages\ListLookbooks;
use App\Filament\Store\Resources\Lookbooks\Schemas\LookbookForm;
use App\Filament\Store\Resources\Lookbooks\Tables\LookbooksTable;
use App\Models\Lookbook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LookbookResource extends Resource
{
    protected static ?string $model = Lookbook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $recordTitleAttribute = 'judul';

    public static function form(Schema $schema): Schema
    {
        return LookbookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LookbooksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLookbooks::route('/'),
            'create' => CreateLookbook::route('/create'),
            'edit' => EditLookbook::route('/{record}/edit'),
        ];
    }
}
