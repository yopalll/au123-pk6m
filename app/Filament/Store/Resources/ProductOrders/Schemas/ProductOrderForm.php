<?php

namespace App\Filament\Store\Resources\ProductOrders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_user')
                    ->required()
                    ->numeric(),
                TextInput::make('id_address')
                    ->required()
                    ->numeric(),
                TextInput::make('id_promo')
                    ->numeric(),
                TextInput::make('kode_order')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('biaya_kirim')
                    ->required()
                    ->numeric(),
                TextInput::make('total_diskon')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('poin_digunakan')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('potongan_poin')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric(),
                TextInput::make('kurir')
                    ->required(),
                TextInput::make('layanan_kirim')
                    ->required(),
                TextInput::make('estimasi_tiba'),
                TextInput::make('resi'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }
}
