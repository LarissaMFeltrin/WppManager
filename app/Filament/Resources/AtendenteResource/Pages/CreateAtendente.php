<?php

namespace App\Filament\Resources\AtendenteResource\Pages;

use App\Filament\Resources\AtendenteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAtendente extends CreateRecord
{
    protected static string $resource = AtendenteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
