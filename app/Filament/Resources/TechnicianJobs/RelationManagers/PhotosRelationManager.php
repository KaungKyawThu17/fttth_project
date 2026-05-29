<?php

namespace App\Filament\Resources\TechnicianJobs\RelationManagers;

use App\Models\JobPhoto;
use App\Models\TechnicianJob;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Job photos';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof TechnicianJob
            && (auth()->user()?->can('view', $ownerRecord) ?? false)
            && (auth()->user()?->can('viewAny', JobPhoto::class) ?? false);
    }

    public function isReadOnly(): bool
    {
        return ! $this->canUploadPhoto();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->directory(fn (): string => $this->photoDirectory())
                    ->visibility('public')
                    ->image()
                    ->maxSize(5120)
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->imageHeight(240)
                    ->url(fn (JobPhoto $record): ?string => $record->photo_url, shouldOpenInNewTab: true)
                    ->columnSpanFull(),
                TextEntry::make('uploaded_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('photo_url')
                    ->label('Public URL')
                    ->url(fn (?string $state): ?string => $state, shouldOpenInNewTab: true)
                    ->placeholder('No URL'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('photo_path')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->latest('uploaded_at')
                ->latest())
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Preview')
                    ->disk('public')
                    ->square()
                    ->imageHeight(72)
                    ->imageWidth(72)
                    ->url(fn (JobPhoto $record): ?string => $record->photo_url, shouldOpenInNewTab: true),
                TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not captured'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('photo_path')
                    ->label('File')
                    ->url(fn (JobPhoto $record): ?string => $record->photo_url, shouldOpenInNewTab: true)
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload photo')
                    ->createAnother(false)
                    ->authorize(fn (): bool => $this->canUploadPhoto())
                    ->visible(fn (): bool => $this->canUploadPhoto())
                    ->using(function (array $data): JobPhoto {
                        $technicianJob = $this->getOwnerRecord();

                        Gate::authorize('createForJob', [JobPhoto::class, $technicianJob]);

                        return $this->getRelationship()->create([
                            ...$data,
                            'photo_type' => JobPhoto::TYPE_ISSUE,
                            'uploaded_at' => now(),
                        ]);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->authorize(fn (JobPhoto $record): bool => auth()->user()?->can('delete', $record) ?? false)
                    ->visible(fn (JobPhoto $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ]);
    }

    protected function canUploadPhoto(): bool
    {
        $technicianJob = $this->getOwnerRecord();

        return $technicianJob instanceof TechnicianJob
            && (auth()->user()?->can('createForJob', [JobPhoto::class, $technicianJob]) ?? false);
    }

    protected function photoDirectory(): string
    {
        return 'technician-jobs/'.$this->getOwnerRecord()->getKey();
    }
}
