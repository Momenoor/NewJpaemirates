<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MatterRequest extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

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
        'type' => RequestType::class,
        'status' => RequestStatus::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (MatterRequest $request) {
            $request->matter->decrement('review_count');
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

    public function attachments(): MatterRequest|\Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Attachment::class, Matter::class, 'id', 'matter_id', 'id');
    }
}
