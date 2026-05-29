<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\TicketCommentService;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Ticket history';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Ticket
            && (auth()->user()?->can('viewComments', $ownerRecord) ?? false);
    }

    public function isReadOnly(): bool
    {
        return ! $this->canAddComment();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('comment')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('author')
                    ->label('Author')
                    ->state(fn (TicketComment $record): string => $record->authorName()),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('comment')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->poll('5s')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['user', 'technician'])
                ->oldestFirst())
            ->columns([
                TextColumn::make('author')
                    ->label('Author')
                    ->state(fn (TicketComment $record): string => $record->authorName()),
                TextColumn::make('comment')
                    ->searchable()
                    ->wrap()
                    ->limit(120),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add comment')
                    ->createAnother(false)
                    ->authorize(fn (): bool => $this->canAddComment())
                    ->visible(fn (): bool => $this->canAddComment())
                    ->using(function (array $data): TicketComment {
                        try {
                            return app(TicketCommentService::class)->addManualComment(
                                ticket: $this->getOwnerRecord(),
                                comment: (string) $data['comment'],
                            );
                        } catch (AuthorizationException|InvalidArgumentException $exception) {
                            throw ValidationException::withMessages([
                                'comment' => $exception->getMessage(),
                            ]);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    protected function canAddComment(): bool
    {
        return app(TicketCommentService::class)->canCurrentUserComment($this->getOwnerRecord());
    }
}
