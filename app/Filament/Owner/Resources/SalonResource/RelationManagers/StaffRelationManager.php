<?php

namespace App\Filament\Owner\Resources\SalonResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'staff';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()->maxLength(255),
            Forms\Components\TextInput::make('profile_url')
                ->url()->label('Profile URL'),
            Forms\Components\Select::make('status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->default('active')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('profile_url')->label('Profile')
                    ->limit(30)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
