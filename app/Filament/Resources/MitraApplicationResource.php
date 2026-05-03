<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MitraApplicationResource\Pages;
use App\Models\MitraApplication;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MitraApplicationResource extends Resource
{
    protected static ?string $model = MitraApplication::class;
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-building-storefront';
    protected static string|\UnitEnum|null   $navigationGroup = 'Partnerships';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Salon Applications';

    // ──── Form (used by the edit/view page) ─────────────────

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Section::make('Salon Details')->schema([
                Forms\Components\TextInput::make('nama_salon')
                    ->label('Salon Name')->disabled(),
                Forms\Components\TextInput::make('nama_pemilik')
                    ->label('Owner Name')->disabled(),
            ])->columns(2),

            Forms\Components\Section::make('Contact')->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email')->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone')->disabled(),
                Forms\Components\TextInput::make('kota.nama_kota')
                    ->label('City')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('Notes & Status')->schema([
                Forms\Components\Textarea::make('catatan')
                    ->label('Notes from Applicant')->disabled()->rows(4),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'new'       => 'New',
                        'contacted' => 'Contacted',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                    ])
                    ->required(),
            ])->columns(2),
        ]);
    }

    // ──── Table ──────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id_application')
                    ->label('ID')->sortable()->width('60px'),

                Tables\Columns\TextColumn::make('nama_salon')
                    ->label('Salon')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('nama_pemilik')
                    ->label('Owner')->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')->searchable()->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone'),

                Tables\Columns\TextColumn::make('kota.nama_kota')
                    ->label('City')->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'       => 'warning',
                        'contacted' => 'info',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')->date('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new'       => 'New',
                        'contacted' => 'Contacted',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                    ]),
            ])
            ->actions([
                // Quick status-flip without leaving the list page.
                Tables\Actions\Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new'       => 'New',
                                'contacted' => 'Contacted',
                                'approved'  => 'Approved',
                                'rejected'  => 'Rejected',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status),
                    ])
                    ->action(fn (MitraApplication $record, array $data) =>
                        $record->update(['status' => $data['status']])
                    )
                    ->modalHeading(fn ($record) => 'Update: ' . $record->nama_salon)
                    ->modalSubmitActionLabel('Save Status'),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markContacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->action(fn ($records) => $records->each->update(['status' => 'contacted']))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('markRejected')
                        ->label('Mark as Rejected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'rejected']))
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMitraApplications::route('/'),
            'view'  => Pages\ViewMitraApplication::route('/{record}'),
        ];
    }
}
