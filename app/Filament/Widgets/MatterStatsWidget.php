<?php

namespace App\Filament\Widgets;

use App\Enums\MatterCollectionStatus;
use App\Enums\MatterStatus;
use App\Models\Matter;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MatterStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 2;

    public function getColumns(): int|array
    {
        return 4;
    }

    protected function getStats(): array
    {
        $matters = Matter::all();
        $totalCount = $matters->count();
        $unpaidCount = Matter::whereIn('collection_status', [MatterCollectionStatus::UNPAID, MatterCollectionStatus::PARTIAL])->whereNotNull('final_report_at')->count();
        $currentCount = $matters->filter(fn($matter) => $matter->status === MatterStatus::IN_PROGRESS)->count();
        $submittedCount = $matters->filter(fn($matter) => $matter->status === MatterStatus::FINALIZED)->count();
        return [
            Stat::make(__('Total Matters'), $totalCount)
                ->description(__('Total matters in the system'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color(Color::Indigo),
            Stat::make(__('Unpaid Matters'), $unpaidCount)
                ->description(__('Total Matters pending payment'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger')
            ->url('https://new.jpaemirates.com/admin/matters?tab=final_submitted&filters[collection_status][values][0]=unpaid&filters[collection_status][values][1]=partial&filters[type][type_filter_mode]=only_selected'),
            Stat::make(__('In Progress Matters'), $currentCount)
                ->description(__('Total Ongoing matters'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            Stat::make(__('Finalized Matters'), $submittedCount)
                ->description(__('Total Finalized matters'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color(Color::Green),
        ];
    }
}
