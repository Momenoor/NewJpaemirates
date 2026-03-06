<?php

namespace App\Filament\Pages;

use App\Models\Matter;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class FixMattersDifficulty extends Page
{
    protected string $view = 'filament.pages.fix-matters-difficulty';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Fix Matters Difficulty';
    protected static ?string $title = 'Fix Matters Difficulty';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Current Statistics')->schema([
                TextEntry::make('total_matters')
                    ->label('Total Matters')
                    ->default(Matter::count()),
                TextEntry::make('simple_count')
                    ->label('Matters with "simple" difficulty')
                    ->default(Matter::where('difficulty', 'simple')->count()),
                TextEntry::make('exceptional_count')
                    ->label('Matters with "exceptional" difficulty')
                    ->default(Matter::where('difficulty', 'exceptional')->count()),
                TextEntry::make('medium_count')
                    ->label('Matters with "medium" difficulty')
                    ->default(Matter::where('difficulty', 'medium')->count()),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fixDifficulty')
                ->label('Fix Difficulty Values')
                ->color('primary')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Fix Matters Difficulty')
                ->modalDescription('This will change "simple" to "easy" and "exceptional" to "hard" in the matters table. Are you sure?')
                ->action(fn() => $this->runFix()),
        ];
    }

    protected function runFix(): void
    {
        $totalSimple = Matter::where('difficulty', 'simple')->count();
        $totalExceptional = Matter::where('difficulty', 'exceptional')->count();
        $total = $totalSimple + $totalExceptional;

        if ($total === 0) {
            Notification::make()->title('Difficulty Values Already Fixed')->info()->send();
            return;
        }

        DB::transaction(function () {
            // Fix simple -> easy
            Matter::where('difficulty', 'simple')->chunkById(100, function ($matters) {
                Matter::whereIn('id', $matters->pluck('id'))->update(['difficulty' => 'easy']);
            });

            // Fix exceptional -> hard
            Matter::where('difficulty', 'exceptional')->chunkById(100, function ($matters) {
                Matter::whereIn('id', $matters->pluck('id'))->update(['difficulty' => 'hard']);
            });
        });

        Notification::make()
            ->title('Difficulty Values Fixed')
            ->body("Successfully updated $total records.")
            ->success()
            ->send();
    }
}
