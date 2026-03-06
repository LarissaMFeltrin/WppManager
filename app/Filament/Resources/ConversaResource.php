<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversaResource\Pages;
use App\Models\Conversa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConversaResource extends Resource
{
    protected static ?string $model = Conversa::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Painel de Conversas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Conversa';

    protected static ?string $pluralModelLabel = 'Conversas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Cliente')
                    ->schema([
                        Forms\Components\TextInput::make('cliente_numero')
                            ->label('Número do Cliente')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('cliente_nome')
                            ->label('Nome do Cliente')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Atendimento')
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->label('Instância WhatsApp')
                            ->relationship('account', 'session_name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('atendente_id')
                            ->label('Atendente')
                            ->relationship('atendente', 'nome')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'aguardando' => 'Aguardando',
                                'em_atendimento' => 'Em Atendimento',
                                'finalizada' => 'Finalizada',
                            ])
                            ->default('aguardando')
                            ->required(),
                        Forms\Components\Toggle::make('bloqueada')
                            ->label('Bloqueada'),
                    ])->columns(2),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas do Atendimento')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Datas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('iniciada_em')
                            ->label('Iniciada em')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('atendida_em')
                            ->label('Atendida em')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('finalizada_em')
                            ->label('Finalizada em')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('ultima_msg_em')
                            ->label('Última Mensagem')
                            ->disabled(),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente_nome')
                    ->label('Cliente')
                    ->description(fn ($record) => $record->cliente_numero)
                    ->searchable(['cliente_nome', 'cliente_numero']),
                Tables\Columns\TextColumn::make('account.session_name')
                    ->label('Instância')
                    ->sortable(),
                Tables\Columns\TextColumn::make('atendente.nome')
                    ->label('Atendente')
                    ->sortable()
                    ->placeholder('Na fila'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'aguardando',
                        'primary' => 'em_atendimento',
                        'success' => 'finalizada',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'aguardando' => 'Aguardando',
                        'em_atendimento' => 'Em Atendimento',
                        'finalizada' => 'Finalizada',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('bloqueada')
                    ->label('Bloq.')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),
                Tables\Columns\TextColumn::make('iniciada_em')
                    ->label('Iniciada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ultima_msg_em')
                    ->label('Última Msg')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('iniciada_em', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aguardando' => 'Aguardando',
                        'em_atendimento' => 'Em Atendimento',
                        'finalizada' => 'Finalizada',
                    ]),
                Tables\Filters\SelectFilter::make('atendente_id')
                    ->label('Atendente')
                    ->relationship('atendente', 'nome'),
                Tables\Filters\SelectFilter::make('account_id')
                    ->label('Instância')
                    ->relationship('account', 'session_name'),
                Tables\Filters\TernaryFilter::make('bloqueada')
                    ->label('Bloqueada'),
            ])
            ->actions([
                Tables\Actions\Action::make('atender')
                    ->label('Atender')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'aguardando'),
                Tables\Actions\Action::make('finalizar')
                    ->label('Finalizar')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'em_atendimento'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversas::route('/'),
            'create' => Pages\CreateConversa::route('/create'),
            'edit' => Pages\EditConversa::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'aguardando')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
