<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MitraApplicationResource\Pages;
use App\Models\MitraApplication;
use App\Services\ApproveSalonApplicationService;
use Filament\Forms;
use Filament\Notifications\Notification;
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
            \Filament\Schemas\Components\Section::make('Salon Details')->schema([
                Forms\Components\TextInput::make('nama_salon')
                    ->label('Salon Name')->disabled(),
                Forms\Components\TextInput::make('nama_pemilik')
                    ->label('Owner Name')->disabled(),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Contact')->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email')->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone')->disabled(),
                Forms\Components\TextInput::make('kota.nama_kota')
                    ->label('City')->disabled(),
            ])->columns(3),

            \Filament\Schemas\Components\Section::make('Notes & Status')->schema([
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
                    ->disabled()
                    ->helperText('Use the "Approve & Create Salon" or "Update Status" actions on the list to change status.')
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
                // Approve + auto-provision: create owner user + salon (inactive)
                // + email password reset link. Hidden once the application is
                // already approved (id_salon set) to prevent double provisioning.
                \Filament\Actions\Action::make('approveAndCreateSalon')
                    ->label('Approve & Create Salon')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (MitraApplication $record) =>
                        $record->status !== 'approved' && $record->id_salon === null
                    )
                    ->requiresConfirmation()
                    ->modalHeading(fn (MitraApplication $record) => 'Approve: ' . $record->nama_salon)
                    ->modalDescription(
                        'This will create the owner user account and a salon record '
                        . '(status=inactive, hidden from public). The owner can log in '
                        . 'at /owner with their email and the default password "password". '
                        . 'Please instruct them to change it via Profile after first login.'
                    )
                    ->modalSubmitActionLabel('Yes, approve & provision')
                    ->action(function (MitraApplication $record) {
                        try {
                            $salon = app(ApproveSalonApplicationService::class)->approve($record);

                            Notification::make()
                                ->title('Salon provisioned')
                                ->body(
                                    "Salon '{$salon->nama_salon}' created (inactive). "
                                    . "Owner can log in with: email={$record->email}, password=password. "
                                    . 'Tell them to change it from /owner/profile after login.'
                                )
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Approval failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Quick status-flip for non-approval transitions (new/contacted/rejected).
                // Does NOT bypass the auto-provision flow for approval —
                // setting 'approved' here is blocked.
                \Filament\Actions\Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (MitraApplication $record) => $record->status !== 'approved')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new'       => 'New',
                                'contacted' => 'Contacted',
                                'rejected'  => 'Rejected',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status),
                    ])
                    ->action(fn (MitraApplication $record, array $data) =>
                        $record->update(['status' => $data['status']])
                    )
                    ->modalHeading(fn ($record) => 'Update: ' . $record->nama_salon)
                    ->modalDescription(
                        'To approve this application use the "Approve & Create Salon" action — '
                        . 'that one provisions the owner account automatically.'
                    )
                    ->modalSubmitActionLabel('Save Status'),

                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('markContacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->action(fn ($records) => $records->each->update(['status' => 'contacted']))
                        ->requiresConfirmation(),

                    \Filament\Actions\BulkAction::make('markRejected')
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
