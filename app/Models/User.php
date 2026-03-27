<?php

namespace App\Models;

use Andreia\FilamentUiSwitcher\Models\Traits\HasUiPreferences;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements FilamentUser
{
    use  HasFactory, Notifiable, HasRoles, HasUiPreferences;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    protected $fillable = [
        'name',
        'password',
        'language',
        'email',
        'display_name',
    ];

    protected $with = [
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ui_preferences' => 'array'
    ];


    public function party(): User|\Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Party::class);
    }

    public function incentiveCalculations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IncentiveCalculation::class, 'created_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
