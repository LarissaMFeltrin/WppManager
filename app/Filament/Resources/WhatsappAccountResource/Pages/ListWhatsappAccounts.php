<?php

namespace App\Filament\Resources\WhatsappAccountResource\Pages;

use App\Filament\Resources\WhatsappAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappAccounts extends ListRecords
{
    protected static string $resource = WhatsappAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
