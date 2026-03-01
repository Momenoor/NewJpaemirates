<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'name',
        'path',
    ];

    public function request(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\Request');
    }
}
