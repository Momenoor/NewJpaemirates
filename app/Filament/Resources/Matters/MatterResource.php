<?php

namespace App\Filament\Resources\Matters;

use App\Filament\Resources\Matters\Pages\CreateMatter;
use App\Filament\Resources\Matters\Pages\EditMatter;
use App\Filament\Resources\Matters\Pages\ListMatters;
use App\Filament\Resources\Matters\Pages\ViewMatter;
use App\Filament\Resources\Matters\Schemas\MatterForm;
use App\Filament\Resources\Matters\Schemas\MatterInfolist;
use App\Filament\Resources\Matters\Tables\MattersTable;
use App\Models\Matter;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatterResource extends Resource
{
    protected static ?string $model = Matter::class;

    public static function getModelLabel(): string
    {
        return __('Matter');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Matters');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return MatterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MatterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MattersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMatters::route('/'),
            'create' => CreateMatter::route('/create'),
            'view'   => ViewMatter::route('/{record}'),
            'edit'   => EditMatter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            // SoftDeletingScope left intact — tabs use onlyTrashed() to control visibility
            ->with([
                'mainPartiesOnly.party',
                'mainPartiesOnly.representatives.party',
                'mainExpertsOnly.party',
                'fees.allocations',
                'court',
                'type',
            ])
            ->orderByRaw('COALESCE(parent_id, id) ASC, id ASC');

        $user = auth()->user();

        if (!$user->can('ViewAny:Matter') && $user->party) {
            $query->whereHas('matterParties', fn(Builder $q) =>
            $q->where('party_id', $user->party->id)
            );
        }

        return $query;
    }
}
