<?php

namespace App\Filament\Resources\Parties\RelationManagers;

use App\Filament\Resources\Matters\MatterResource;
use App\Filament\Resources\Matters\Tables\MattersTable;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MattersRelationManager extends RelationManager
{
    protected static string $relationship = 'matters';

    protected static ?string $relatedResource = MatterResource::class;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Matters');
    }

    public function table(Table $table): Table
    {
        return MattersTable::configure($table)
            ->headerActions([])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}
