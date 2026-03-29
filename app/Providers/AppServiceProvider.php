<?php

namespace App\Providers;

use App\Events\FilamentActionEvent;
use App\Filament\Schemas\Components\FontSizeSlider;
use App\Listeners\SendFilamentActionNotifications;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            FilamentActionEvent::class,
            SendFilamentActionNotifications::class,
        );


    }
}
