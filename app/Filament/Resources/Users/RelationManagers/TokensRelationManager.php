<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $title = 'API tokens';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('abilities')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                // Tokens are created here (not a normal create form) so the
                // one-time plain-text value can be surfaced exactly once.
                Action::make('generate')
                    ->label('Generate token')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->label('Token name')
                            ->default('api-token')
                            ->required(),
                        TextInput::make('abilities')
                            ->label('Abilities')
                            ->helperText('Comma-separated. Leave blank to grant all abilities (*).'),
                    ])
                    ->action(function (array $data): void {
                        $abilities = collect(explode(',', (string) ($data['abilities'] ?? '')))
                            ->map(fn ($a) => trim($a))
                            ->filter()
                            ->values()
                            ->all();

                        $token = $this->getOwnerRecord()->createToken(
                            $data['name'] ?: 'api-token',
                            empty($abilities) ? ['*'] : $abilities,
                        );

                        Notification::make()
                            ->title('API token created')
                            ->body('Copy it now — it will not be shown again: '.$token->plainTextToken)
                            ->persistent()
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->label('Revoke'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
