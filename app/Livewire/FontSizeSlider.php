<?php

namespace App\Livewire;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class FontSizeSlider extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public static function make(): static
    {
        return new static();
    }

    public function mount(): void
    {
        $this->form->fill([
            'font_size' => auth()->user()->font_size,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Slider::make('font_size')
                    ->live()
                    ->label(fn($state) => __('Font Size').': ' . $state . 'px')
                    ->step(1)
                    ->fillTrack()
                    ->default(16)
                    ->minValue(12)
                    ->maxValue(24)
                    ->afterStateUpdated(fn($state) => $this->updateUserFont($state)),
            ])
            ->statePath('data');
    }

    public function updateUserFont($state): void
    {
        auth()->user()->update(['font_size' => $state]);
        \Illuminate\Support\Facades\Cache::forget('user_font_size_' . auth()->id());
        $this->js("document.documentElement.style.setProperty('--user-font-size', '{$state}px')");
        $this->dispatch('font-size-updated', size: $state);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
    {
        return view('livewire.font-size-slider');
    }
}
