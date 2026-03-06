<?php

namespace App\Filament\Resources\WhatsappAccountResource\Pages;

use App\Filament\Resources\WhatsappAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappAccount extends CreateRecord
{
    protected static string $resource = WhatsappAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
