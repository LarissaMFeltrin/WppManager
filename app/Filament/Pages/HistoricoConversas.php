<?php

namespace App\Filament\Pages;

use App\Models\Conversa;
use App\Models\WhatsappAccount;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HistoricoConversas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Historico Conversas';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.historico-conversas';

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        return $table
            ->query(Conversa::whereIn('account_id', $accountIds)->where('status', 'finalizada'))
            ->columns([
                Tables\Columns\TextColumn::make('cliente_nome')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cliente_numero')
                    ->label('Numero')
                    ->searchable(),
                Tables\Columns\TextColumn::make('atendente.nome')
                    ->label('Atendente'),
                Tables\Columns\TextColumn::make('account.session_name')
                    ->label('Instancia'),
                Tables\Columns\TextColumn::make('iniciada_em')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finalizada_em')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duracao')
                    ->label('Duracao')
                    ->getStateUsing(function ($record) {
                        if ($record->iniciada_em && $record->finalizada_em) {
                            return $record->iniciada_em->diff($record->finalizada_em)->format('%H:%I:%S');
                        }
                        return '-';
                    }),
            ])
            ->defaultSort('finalizada_em', 'desc')
            ->filters([
                Tables\Filters\Filter::make('data')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('de')
                            ->label('De'),
                        \Filament\Forms\Components\DatePicker::make('ate')
                            ->label('Ate'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['de'], fn($q) => $q->whereDate('finalizada_em', '>=', $data['de']))
                            ->when($data['ate'], fn($q) => $q->whereDate('finalizada_em', '<=', $data['ate']));
                    }),
                Tables\Filters\SelectFilter::make('atendente_id')
                    ->label('Atendente')
                    ->relationship('atendente', 'nome'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver Conversa')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ChatAtendimento::getUrl(['conversa' => $record->id])),
            ]);
    }
}
