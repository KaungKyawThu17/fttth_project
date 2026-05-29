<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Technician;
use App\Models\Ticket;
use App\Services\TicketAssignmentService;
use App\Services\TicketCommentService;
use App\Services\Tickets\TicketWorkflowService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Ticketing';

    protected static ?string $recordTitleAttribute = 'ticket_no';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('viewAny', Ticket::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['category', 'customer', 'technician']);

        if (! (auth()->user()?->can('viewAny', Ticket::class) ?? false)) {
            return $query->whereKey(-1);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Ticket::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Ticket::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Ticket && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Ticket && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Ticket && (auth()->user()?->can('delete', $record) ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canManageTickets() ?? false;
    }

    /**
     * @return array<Action>
     */
    public static function workflowActions(): array
    {
        return [
            self::addCommentAction(),
            self::assignTechnicianAction(),
            self::markInProgressAction(),
            self::closeTicketAction(),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    protected static function addCommentAction(): Action
    {
        return Action::make('addComment')
            ->label('Add comment')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->modalHeading('Add ticket comment')
            ->schema([
                Textarea::make('comment')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->authorize(fn (Ticket $record, TicketCommentService $comments): bool => $comments->canCurrentUserComment($record))
            ->visible(fn (Ticket $record, TicketCommentService $comments): bool => $comments->canCurrentUserComment($record))
            ->successNotificationTitle('Comment added')
            ->action(function (Ticket $record, array $data, TicketCommentService $comments): void {
                try {
                    $comments->addManualComment(
                        ticket: $record,
                        comment: (string) $data['comment'],
                    );

                    $record->refresh();
                } catch (AuthorizationException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'comment' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function assignTechnicianAction(): Action
    {
        return Action::make('assignTechnician')
            ->label('Assign technician')
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalHeading('Assign technician')
            ->schema([
                Select::make('technician_id')
                    ->label('Technician')
                    ->options(fn (): array => Technician::query()
                        ->where('status', Technician::STATUS_ACTIVE)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->fillForm(fn (Ticket $record): array => [
                'technician_id' => $record->technician_id,
            ])
            ->authorize(fn (Ticket $record): bool => auth()->user()?->can('assignTechnician', $record) ?? false)
            ->visible(fn (Ticket $record): bool => auth()->user()?->can('assignTechnician', $record) ?? false)
            ->successNotificationTitle('Technician assigned')
            ->action(function (Ticket $record, array $data, TicketAssignmentService $assignmentService): void {
                try {
                    $assignmentService->assign($record, (int) $data['technician_id'], auth()->id());
                } catch (AuthorizationException|DomainException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'technician_id' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function markInProgressAction(): Action
    {
        return Action::make('markInProgress')
            ->label('Mark in progress')
            ->icon(Heroicon::OutlinedPlay)
            ->color('warning')
            ->authorize(fn (Ticket $record): bool => auth()->user()?->can('markInProgress', $record) ?? false)
            ->visible(fn (Ticket $record): bool => auth()->user()?->can('markInProgress', $record) ?? false)
            ->successNotificationTitle('Ticket marked in progress')
            ->action(function (Ticket $record, TicketWorkflowService $workflow): void {
                try {
                    $workflow->markInProgress($record);
                } catch (AuthorizationException|DomainException $exception) {
                    throw ValidationException::withMessages([
                        'status' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function closeTicketAction(): Action
    {
        return Action::make('closeTicket')
            ->label('Close ticket')
            ->icon(Heroicon::OutlinedLockClosed)
            ->requiresConfirmation()
            ->color('gray')
            ->authorize(fn (Ticket $record): bool => auth()->user()?->can('close', $record) ?? false)
            ->visible(fn (Ticket $record): bool => auth()->user()?->can('close', $record) ?? false)
            ->successNotificationTitle('Ticket closed')
            ->action(function (Ticket $record, TicketWorkflowService $workflow): void {
                try {
                    $workflow->close($record);
                } catch (AuthorizationException|DomainException $exception) {
                    throw ValidationException::withMessages([
                        'status' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
