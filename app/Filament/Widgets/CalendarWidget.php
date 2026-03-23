<?php

namespace App\Filament\Widgets;

use App\Models\Matter;
use App\Services\OutlookCalendarService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public \Illuminate\Database\Eloquent\Model | int | string | null $record = null;

    /**
     * @param array $info
     * @throws ConnectionException
     */
    public function fetchEvents(array $info): array
    {
        return \App\Models\Calendar::query()
            ->where('start_at', '>=', $info['start'])
            ->where('end_at', '<=', $info['end'])
            ->get()
            ->map(fn($event) => [
                'id' => $event->outlook_event_id ?? $event->id,
                'title' => $event->title,
                'start' => $event->start_at,
                'end' => $event->end_at,
                'extendedProps' => [
                    'location' => $event->location,
                    'description' => $event->description,
                ],
            ])
            ->toArray();
    }

    public function eventDidMount(): string
    {
        return 'function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }){
            el.setAttribute("x-tooltip", "tooltip");
            el.setAttribute("x-data", "{ tooltip: \'"+event.title+"\' }");
        }';
    }


    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'timeGridDay,timeGridWeek',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
            'initialView' => 'timeGridWeek',
            'eventDisplay' => 'block',
            'timeZone' => 'Aisa/Dubai',
            'scrollTime' => '09:00:00',
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mountUsing(
                    fn(Schema $form, array $arguments) => $form->fill([
                        'start_at' => $arguments['start'] ?? null,
                        'end_at' => $arguments['end'] ?? null,
                    ])
                )
                ->model(\App\Models\Calendar::class),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mountUsing(
                    fn(Schema $form, array $arguments) => $form->fill([
                        'title' => $arguments['event']['title'] ?? null,
                        'start_at' => $arguments['event']['start'] ?? null,
                        'end_at' => $arguments['event']['end'] ?? null,
                        'location' => $arguments['event']['extendedProps']['location'] ?? null,
                        'description' => $arguments['event']['extendedProps']['description'] ?? null,
                    ])
                ),
            Actions\DeleteAction::make(),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('Title'))
                ->required(),
            Grid::make()
                ->schema([
                    DateTimePicker::make('start_at')
                        ->label(__('Start At'))
                        ->seconds(false)
                        ->lazy()
                        ->afterStateUpdated(fn($set, $state) => $set('end_at', Carbon::parse($state)->addHour()))
                        ->required(),

                    DateTimePicker::make('end_at')
                        ->label(__('End At'))
                        ->seconds(false)
                        ->afterOrEqual('start_at')
                        ->required(),
                ]),
            Forms\Components\Select::make('matter_id')
                ->label(__('Matter'))
                ->options(
                    Matter::whereNull(['initial_report_at', 'final_report_at'])
                        ->get()
                        ->mapWithKeys(fn($matter) => [
                            $matter->id => $matter->number . '/' . $matter->year
                        ])
                )
                ->required()
                ->lazy()
                ->afterStateUpdated(fn($set, $state) => $set('description', app(OutlookCalendarService::class)->buildEventBody($state)))
                ->searchable(),
            TextInput::make('location')
                ->label(__('Location')),
            Textarea::make('description')
                ->label(__('Description'))
                ->reactive(),
        ];
    }
}
