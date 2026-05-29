<?php

namespace App\Filament\Resources\TechnicianJobs;

use App\Filament\Resources\TechnicianJobs\Pages\EditTechnicianJob;
use App\Filament\Resources\TechnicianJobs\Pages\ListTechnicianJobs;
use App\Filament\Resources\TechnicianJobs\Pages\ViewTechnicianJob;
use App\Filament\Resources\TechnicianJobs\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\TechnicianJobs\Schemas\TechnicianJobForm;
use App\Filament\Resources\TechnicianJobs\Schemas\TechnicianJobInfolist;
use App\Filament\Resources\TechnicianJobs\Tables\TechnicianJobsTable;
use App\Models\TechnicianJob;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
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

class TechnicianJobResource extends Resource
{
    protected static ?string $model = TechnicianJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Ticketing';

    protected static ?string $recordTitleAttribute = 'job_no';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return TechnicianJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TechnicianJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TechnicianJobsTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isTechnician() ? 'My Jobs' : 'Technician Jobs';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['customer', 'technician', 'ticket']);

        $user = self::currentUser();

        if (! $user) {
            return $query->whereKey(-1);
        }

        if ($user->canViewAllTechnicianJobs()) {
            return $query;
        }

        $technicianId = $user->technicianProfileId();

        if ($technicianId === null) {
            return $query->whereKey(-1);
        }

        return $query->forTechnician($technicianId);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', TechnicianJob::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof TechnicianJob && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof TechnicianJob && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof TechnicianJob && (auth()->user()?->can('delete', $record) ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<Action>
     */
    public static function workflowActions(): array
    {
        return [
            self::startJobAction(),
            self::completeJobAction(),
            self::cancelJobAction(),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTechnicianJobs::route('/'),
            'view' => ViewTechnicianJob::route('/{record}'),
            'edit' => EditTechnicianJob::route('/{record}/edit'),
        ];
    }

    public static function canViewJob(TechnicianJob $job): bool
    {
        return auth()->user()?->can('view', $job) ?? false;
    }

    public static function canStartJob(TechnicianJob $job): bool
    {
        return auth()->user()?->can('start', $job) ?? false;
    }

    public static function canCompleteJob(TechnicianJob $job): bool
    {
        return auth()->user()?->can('complete', $job) ?? false;
    }

    public static function canCancelJob(TechnicianJob $job): bool
    {
        return auth()->user()?->can('cancel', $job) ?? false;
    }

    protected static function startJobAction(): Action
    {
        return Action::make('startJob')
            ->label('Start job')
            ->icon(Heroicon::OutlinedPlay)
            ->color('warning')
            ->modalHeading('Start technician job')
            ->authorize(fn (TechnicianJob $record): bool => self::canStartJob($record))
            ->visible(fn (TechnicianJob $record): bool => $record->canStart() && self::canStartJob($record))
            ->successNotificationTitle('Job started')
            ->action(function (TechnicianJob $record): void {
                try {
                    $record->startJob();

                    $record->refresh();
                } catch (AuthorizationException|DomainException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'status' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function completeJobAction(): Action
    {
        return Action::make('completeJob')
            ->label('Complete job')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->modalHeading('Complete technician job')
            ->authorize(fn (TechnicianJob $record): bool => self::canCompleteJob($record))
            ->visible(fn (TechnicianJob $record): bool => $record->canComplete() && self::canCompleteJob($record))
            ->successNotificationTitle('Job completed')
            ->action(function (TechnicianJob $record): void {
                try {
                    $record->completeJob();

                    $record->refresh();
                } catch (AuthorizationException|DomainException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'status' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function cancelJobAction(): Action
    {
        return Action::make('cancelJob')
            ->label('Cancel job')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel technician job')
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->rows(3),
            ])
            ->authorize(fn (TechnicianJob $record): bool => self::canCancelJob($record))
            ->visible(fn (TechnicianJob $record): bool => $record->canCancel() && self::canCancelJob($record))
            ->successNotificationTitle('Job cancelled')
            ->action(function (TechnicianJob $record, array $data): void {
                try {
                    $record->cancelJob($data['reason'] ?? null);
                    $record->refresh();
                } catch (AuthorizationException|DomainException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'status' => $exception->getMessage(),
                    ]);
                }
            });
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
