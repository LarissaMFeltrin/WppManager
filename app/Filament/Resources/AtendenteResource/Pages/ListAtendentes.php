<?php

namespace App\Filament\Resources\AtendenteResource\Pages;

use App\Filament\Resources\AtendenteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAtendentes extends ListRecords
{
    protected static string $resource = AtendenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
