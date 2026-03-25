<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CalendarEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('Event Details'))->schema([
                TextEntry::make('title')
                    ->label(__('Title'))
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->columnSpanFull(),

                TextEntry::make('matter')
                    ->label(__('Matter'))
                    ->formatStateUsing(fn($state) => $state
                        ? $state->year . '/' . $state->number
                        : '—'
                    )
                    ->url(fn($record) => $record->matter_id
                        ? \App\Filament\Resources\Matters\MatterResource::getUrl('view', ['record' => $record->matter_id])
                        : null
                    ),

                TextEntry::make('start_datetime')
                    ->label(__('Start At'))
                    ->dateTime('d M Y H:i'),

                TextEntry::make('end_datetime')
                    ->label(__('End At'))
                    ->dateTime('d M Y H:i'),

                IconEntry::make('is_all_day')
                    ->label(__('All Day'))
                    ->boolean(),

                TextEntry::make('location')
                    ->label(__('Location'))
                    ->placeholder('—')
                    ->icon('heroicon-o-map-pin'),

                TextEntry::make('description')
                    ->label(__('Description'))
                    ->placeholder('—')
                    ->columnSpanFull()
                    ->html()
//                    ->formatStateUsing(function ($state) {
//                        if (!$state) return $state;
//
//                        // 1. Sanitize the input first to prevent XSS
//                        $safeState = e($state);
//
//                        // 2. Regex to find URLs and wrap them in <a> tags
//                        $pattern = '~(?<!@)\b(?:https?://|www\.)[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))~';
//
//                        return preg_replace_callback($pattern, function ($matches) {
//                            $url = $matches[0];
//                            $href = str_starts_with($url, 'www.') ? "https://{$url}" : $url;
//
//                            // Optional: Add specific styling if it's a Teams link
//                            $isTeams = str_contains($url, 'teams.microsoft.com');
//                            $class = $isTeams ? 'text-primary-600 font-bold underline' : 'text-primary-600 underline';
//
//                            return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer" class="' . $class . '">' . $url . '</a>';
//                        }, $safeState);
//                    }),

            ])->columns(2),

            Section::make(__('Sync Status'))->schema([
                TextEntry::make('outlook_event_id')
                    ->label(__('Outlook Event'))
                    ->placeholder(__('Not synced'))
                    ->formatStateUsing(fn($state) => $state ? __('Synced') : __('Not synced'))
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                TextEntry::make('online_meeting_url')
                    ->label(__('Teams Meeting'))
                    ->placeholder(__('No meeting link'))
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-video-camera')
                    ->iconColor('info')
                    ->visible(fn($record) => $record->online_meeting_url),

            ])->columns(2),

        ]);
    }
}
