<?php

namespace App\Filament\Resources\ConversaResource\Pages;

use App\Filament\Resources\ConversaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConversa extends EditRecord
{
    protected static string $resource = ConversaResource::class;

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
