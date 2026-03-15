<?php

namespace App\Providers;

use App\Events\FilamentActionEvent;
use App\Listeners\SendFilamentActionNotifications;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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

        if (class_exists(\BezhanSalleh\LanguageSwitch\LanguageSwitch::class)) {
            \BezhanSalleh\LanguageSwitch\LanguageSwitch::configureUsing(function (\BezhanSalleh\LanguageSwitch\LanguageSwitch $switch) {
                $switch
                    ->locales(['ar', 'en']); // also accepts a closure
            });
        }
    }
}
