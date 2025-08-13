<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->modalHeading('Crear nuevo cupón')
                ->modalDescription('Completa la información del cupón')
                ->modalSubmitActionLabel('Crear cupón')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}
