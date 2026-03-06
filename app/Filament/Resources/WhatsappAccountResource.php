<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappAccountResource\Pages;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsappAccountResource extends Resource
{
    protected static ?string $model = WhatsappAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Instancias';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Instância WhatsApp';

    protected static ?string $pluralModelLabel = 'Instâncias WhatsApp';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuração da Instância')
                    ->schema([
                        Forms\Components\Select::make('empresa_id')
                            ->label('Empresa')
                            ->relationship('empresa', 'nome')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->label('Usuário Responsável')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('session_name')
                            ->label('Nome da Sessão')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Identificador único da instância (ex: empresa-principal)'),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Número do WhatsApp')
                            ->placeholder('5544999999999')
                            ->required()
                            ->maxLength(20)
                            ->helperText('Formato: 5544999999999 (código país + DDD + número)'),
                    ])->columns(2),

                Forms\Components\Section::make('Status da Conexão')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Instância Ativa')
                            ->default(true),
                        Forms\Components\Toggle::make('is_connected')
                            ->label('Conectado')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('owner_jid')
                            ->label('Owner JID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('service_port')
                            ->label('Porta do Serviço')
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('last_connection')
                            ->label('Última Conexão')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('last_full_sync')
                            ->label('Última Sincronização')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session_name')
                    ->label('Sessão')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número')
                    ->searchable(),
                Tables\Columns\TextColumn::make('empresa.nome')
                    ->label('Empresa')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_connected')
                    ->label('Conectado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_connection')
                    ->label('Última Conexão')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chats_count')
                    ->label('Chats')
                    ->counts('chats'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_connected')
                    ->label('Conectado'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo'),
                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'nome'),
            ])
            ->actions([
                Tables\Actions\Action::make('connect')
                    ->label('Conectar')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_connected)
                    ->before(function ($record) {
                        // Criar instância na Evolution API antes de abrir o modal
                        $api = new EvolutionApiService();
                        $result = $api->createInstance($record->session_name, [
                            'number' => $record->phone_number,
                            'qrcode' => true,
                            'integration' => 'WHATSAPP-BAILEYS',
                        ]);

                        // Se já existe, não tem problema
                        if (!($result['success'] ?? false) && !str_contains($result['error'] ?? '', 'already')) {
                            Notification::make()
                                ->title('Instância criada na Evolution API')
                                ->success()
                                ->send();
                        }
                    })
                    ->modalHeading('Conectar WhatsApp')
                    ->modalDescription('Siga as instruções para conectar')
                    ->modalContent(fn ($record) => view('filament.qrcode-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
                Tables\Actions\Action::make('disconnect')
                    ->label('Desconectar')
                    ->icon('heroicon-o-link-slash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_connected)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $api = new EvolutionApiService();
                        $result = $api->disconnectInstance($record->session_name);

                        if ($result['success'] ?? false) {
                            $record->update(['is_connected' => false]);
                            Notification::make()
                                ->title('Desconectado com sucesso')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro ao desconectar')
                                ->body($result['error'] ?? 'Erro desconhecido')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sync')
                    ->label('Sincronizar Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($record) {
                        $api = new EvolutionApiService();
                        $result = $api->getConnectionState($record->session_name);

                        $isConnected = ($result['data']['state'] ?? '') === 'open';

                        $record->update([
                            'is_connected' => $isConnected,
                            'last_connection' => $isConnected ? now() : $record->last_connection,
                        ]);

                        Notification::make()
                            ->title($isConnected ? 'Conectado!' : 'Desconectado')
                            ->icon($isConnected ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color($isConnected ? 'success' : 'danger')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListWhatsappAccounts::route('/'),
            'create' => Pages\CreateWhatsappAccount::route('/create'),
            'edit' => Pages\EditWhatsappAccount::route('/{record}/edit'),
        ];
    }
}
