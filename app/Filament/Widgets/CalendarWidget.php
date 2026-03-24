<?php

namespace App\Filament\Widgets;

use App\Models\CalendarEvent;
use App\Models\Matter;
use App\Services\OutlookCalendarService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{

    public Model | string | null $model = CalendarEvent::class;
    /**
     * @param array $info
     * @throws ConnectionException
     */
    public function fetchEvents(array $info): array
    {
        return $date = CalendarEvent::query()
            ->with('matters') // Eager load the relationship
            ->where('start_datetime', '>=', $info['start'])
            ->where('end_datetime', '<=', $info['end'])
            ->get()
            ->map(fn($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_datetime,
                'end' => $event->end_datetime,
                'extendedProps' => [
                    'location' => $event->location,
                    'description' => $event->description,
                    // Pass the matter numbers for the tooltip
                    'matters' => $event->matters->map(fn($m) => "{$m->number}/{$m->year}")->implode(', '),
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
            'timeZone' => 'Asia/Dubai',
            'scrollTime' => '09:00:00',
        ];
    }

    protected function headerActions(): array
    {
        return [
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
        return S;
    }
}
