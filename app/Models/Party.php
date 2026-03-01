<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

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
        'old_id',
    ];

    protected $casts = [
        'role' => 'array', // This tells Laravel to JSON encode/decode automatically
    ];

    public function party(): \Illuminate\Database\Eloquent\Relations\BelongsTo // This is the method Laravel is looking for
    {
        return $this->belongsTo(Party::class, 'parent_id');
    }
}
