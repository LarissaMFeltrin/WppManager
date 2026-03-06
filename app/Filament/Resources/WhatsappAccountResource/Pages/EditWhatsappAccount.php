<?php

namespace App\Filament\Resources\WhatsappAccountResource\Pages;

use App\Filament\Resources\WhatsappAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappAccount extends EditRecord
{
    protected static string $resource = WhatsappAccountResource::class;

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
