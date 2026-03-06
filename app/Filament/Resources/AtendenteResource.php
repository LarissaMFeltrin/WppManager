<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AtendenteResource\Pages;
use App\Models\Atendente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class AtendenteResource extends Resource
{
    protected static ?string $model = Atendente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Configuracoes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Atendente';

    protected static ?string $pluralModelLabel = 'Atendentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Atendente')
                    ->schema([
                        Forms\Components\Select::make('empresa_id')
                            ->label('Empresa')
                            ->relationship('empresa', 'nome')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->label('Usuário do Sistema')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('senha')
                            ->label('Senha')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'online' => 'Online',
                                'offline' => 'Offline',
                                'ocupado' => 'Ocupado',
                            ])
                            ->default('offline')
                            ->required(),
                        Forms\Components\TextInput::make('max_conversas')
                            ->label('Máximo de Conversas')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(50),
                        Forms\Components\Select::make('whatsappAccounts')
                            ->label('Instâncias WhatsApp')
                            ->relationship('whatsappAccounts', 'session_name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('empresa.nome')
                    ->label('Empresa')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'ocupado',
                    ]),
                Tables\Columns\TextColumn::make('conversas_ativas')
                    ->label('Conversas Ativas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_conversas')
                    ->label('Máx. Conversas')
                    ->numeric(),
                Tables\Columns\TextColumn::make('ultimo_acesso')
                    ->label('Último Acesso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'ocupado' => 'Ocupado',
                    ]),
                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'nome'),
            ])
            ->actions([
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
            'index' => Pages\ListAtendentes::route('/'),
            'create' => Pages\CreateAtendente::route('/create'),
            'edit' => Pages\EditAtendente::route('/{record}/edit'),
        ];
    }
}
