<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;


    protected $fillable = [
        'matter_id',
        'user_id',
        'text',
        'datetime'
    ];

    protected $dates = [
        'datetime'
    ];

    protected $with = [
        'user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($query) {
            $query->user_id = auth()->id();
            $query->datetime = now();
        });
    }


    public function user()
    {
        return  $this->belongsTo(User::class);
    }
}
