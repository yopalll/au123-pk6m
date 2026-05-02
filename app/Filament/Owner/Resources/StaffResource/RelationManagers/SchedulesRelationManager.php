<?php

namespace App\Filament\Owner\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Working Hours';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('hari')
                ->label('Day')
                ->options([
                    'Monday'    => 'Monday',
                    'Tuesday'   => 'Tuesday',
                    'Wednesday' => 'Wednesday',
                    'Thursday'  => 'Thursday',
                    'Friday'    => 'Friday',
                    'Saturday'  => 'Saturday',
                    'Sunday'    => 'Sunday',
                ])
                ->required(),
            Forms\Components\TimePicker::make('start_time')
                ->seconds(false)->required()->label('Start'),
            Forms\Components\TimePicker::make('end_time')
                ->seconds(false)->required()->label('End'),
            Forms\Components\Toggle::make('is_available')
                ->default(true)
                ->label('Available'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari')->label('Day')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Start'),
                Tables\Columns\TextColumn::make('end_time')->label('End'),
                Tables\Columns\IconColumn::make('is_available')->boolean()->label('Available'),
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
