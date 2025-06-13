<?php

namespace App\Filament\Resources\CategorieResource\Pages;

use App\Filament\Resources\CategorieResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategorieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
