<?php

namespace App\Filament\Resources\ChurnPredictions;

use App\Filament\Resources\ChurnPredictions\Pages\ListChurnPredictions;
use App\Filament\Resources\ChurnPredictions\Pages\ViewChurnPrediction;
use App\Filament\Resources\ChurnPredictions\Schemas\ChurnPredictionInfolist;
use App\Filament\Resources\ChurnPredictions\Tables\ChurnPredictionsTable;
use App\Models\ChurnPrediction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChurnPredictionResource extends Resource
{
    protected static ?string $model = ChurnPrediction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'customer_code';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 30;

    public static function infolist(Schema $schema): Schema
    {
        return ChurnPredictionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChurnPredictionsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('customer')
            ->latest('predicted_at');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ChurnPrediction && self::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChurnPredictions::route('/'),
            'view' => ViewChurnPrediction::route('/{record}'),
        ];
    }
}
