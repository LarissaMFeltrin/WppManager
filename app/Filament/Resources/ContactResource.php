<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Contatos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Contato';

    protected static ?string $pluralModelLabel = 'Contatos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Contato')
                    ->schema([
                        Forms\Components\TextInput::make('push_name')
                            ->label('Nome')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('remote_jid')
                            ->label('JID')
                            ->disabled(),
                        Forms\Components\Select::make('account_id')
                            ->label('Instancia')
                            ->relationship('account', 'session_name')
                            ->disabled(),
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_picture_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=random'),
                Tables\Columns\TextColumn::make('push_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remote_jid')
                    ->label('Numero')
                    ->formatStateUsing(fn ($state) => explode('@', $state)[0])
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.session_name')
                    ->label('Instancia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->label('Instancia')
                    ->relationship('account', 'session_name'),
            ])
            ->actions([
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
            'index' => Pages\ListContacts::route('/'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
