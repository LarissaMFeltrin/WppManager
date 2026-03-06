<?php

namespace App\Filament\Resources\ConversaResource\Pages;

use App\Filament\Resources\ConversaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConversas extends ListRecords
{
    protected static string $resource = ConversaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
