<?php

namespace App\Filament\Resources\ConversaResource\Pages;

use App\Filament\Resources\ConversaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConversa extends CreateRecord
{
    protected static string $resource = ConversaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
