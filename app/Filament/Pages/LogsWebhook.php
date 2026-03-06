<?php

namespace App\Filament\Pages;

use App\Models\LogSistema;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;

class LogsWebhook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Logs de Webhook';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.logs-webhook';

    public function table(Table $table): Table
    {
        return $table
            ->query(LogSistema::where('tipo', 'webhook'))
            ->columns([
                Tables\Columns\TextColumn::make('nivel')
                    ->label('Nivel')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'error' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('mensagem')
                    ->label('Mensagem')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_origem')
                    ->label('IP'),
                Tables\Columns\TextColumn::make('criada_em')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('criada_em', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('nivel')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Detalhes')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detalhes do Log')
                    ->modalContent(fn ($record) => view('filament.log-details', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ]);
    }
}
