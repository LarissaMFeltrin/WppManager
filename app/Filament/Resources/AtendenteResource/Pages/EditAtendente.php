<?php

namespace App\Filament\Resources\AtendenteResource\Pages;

use App\Filament\Resources\AtendenteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAtendente extends EditRecord
{
    protected static string $resource = AtendenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
