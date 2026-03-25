<?php

namespace App\Filament\Resources\Requests;

use App\Filament\Resources\Requests\Pages\CreateRequest;
use App\Filament\Resources\Requests\Pages\EditRequest;
use App\Filament\Resources\Requests\Pages\ListRequests;
use App\Filament\Resources\Requests\Pages\ViewRequest;
use App\Filament\Resources\Requests\Schemas\RequestForm;
use App\Filament\Resources\Requests\Schemas\RequestInfolist;
use App\Filament\Resources\Requests\Tables\RequestsTable;
use App\Models\MatterRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RequestResource extends Resource
{
    protected static ?string $model = MatterRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Newspaper;
    protected static ?int $navigationSort = 5;

    public static function getPluralModelLabel(): string
    {
        return __('Requests');
    }

    public static function getModelLabel(): string
    {
        return __('Requests');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        // The $record is passed in automatically by Filament
        return $record?->type?->getLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return RequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestsTable::configure($table);
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
            'index' => ListRequests::route('/'),
            //'create' => CreateRequest::route('/create'),
            'view' => ViewRequest::route('/{record}'),
            //'edit' => EditRequest::route('/{record}/edit'),
        ];
    }
}
