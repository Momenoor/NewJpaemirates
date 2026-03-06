<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Party extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'fax',
        'address',
        'email',
        'type',
        'role',
        'extra',
        'parent_id',
        'user_id',
        'black_list',
        'old_id'
    ];

    protected $casts = [
        'role' => 'array', // This tells Laravel to JSON encode/decode automatically
    ];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Party::class, 'parent_id');
    }

    public function representatives(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatterParty::class, 'parent_id', 'party_id');
    }

    public function matters(): BelongsToMany
    {
        return $this->belongsToMany(Matter::class, 'matter_party')
            ->withPivot('id', 'type', 'role', 'parent_id');
    }
}
