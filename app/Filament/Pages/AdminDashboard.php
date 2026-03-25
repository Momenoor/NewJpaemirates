<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\MattersPerYearWidget;
use App\Filament\Widgets\MatterStatsWidget;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class AdminDashboard extends Dashboard
{
    public function getWidgets(): array
    {
        return[
            MatterStatsWidget::class,
            MattersPerYearWidget::class,

            CalendarWidget::class,

        ];

    }
}
