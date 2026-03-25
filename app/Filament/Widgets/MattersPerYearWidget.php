<?php

namespace App\Filament\Widgets;

use App\Models\Matter;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class MattersPerYearWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 1;
    public function getHeading(): string
    {
        return __('Matters Received Per Year');
    }

    protected function getData(): array
    {
        $data = Trend::model(Matter::class)
            ->dateColumn('received_at')
            ->between(
                start: now()->subYears(10)->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perYear()
            ->count();
//            Matter::query()
//            ->selectRaw('YEAR(received_at) as year, count(*) as total')
//            ->groupBy('year')
//            ->orderBy('year', 'desc')
//            ->get();
        return [
            'datasets' => [
                [
                    'label' => __('Matters Per Year'),
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),

                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
