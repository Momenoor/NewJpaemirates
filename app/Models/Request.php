<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'matter_id',
        'request_by',
        'type',
        'status',
        'comment',
        'approved_by',
        'approved_at',
        'approved_comment',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Request $request) {
            $request->attachments()->delete();
        });
    }

    public function matter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function requestBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'request_by');
    }

    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): Request|\Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Attachment::class, Matter::class, 'id', 'matter_id', 'id');
    }
}
