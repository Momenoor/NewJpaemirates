<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements FilamentUser
{
    use  HasFactory, Notifiable, HasRoles;

    public const DEFAULT_PASSWORD = 123456;


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
    ];



    public function marketers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {

        return $this->belongsToMany(User::class, 'matter_marketing')->withPivot('type');
    }

    public function symbol(): string
    {
        return 'M';
    }

    public function color(): string
    {
        if ($this->pivot) {
            return 'info';
        }
        return 'warning';
    }

    public function category()
    {
        return $this->category;
    }

    public function field()
    {
        if ($this->pivot) {
            return $this->pivot->type;
        }
        return 'user';
    }

    public function pivotType(): string
    {
        return 'marketing';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
