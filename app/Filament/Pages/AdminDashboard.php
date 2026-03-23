<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class AdminDashboard extends Dashboard
{
    public function getWidgets(): array
    {
        return[
            CalendarWidget::class,
        ];

    }
}
