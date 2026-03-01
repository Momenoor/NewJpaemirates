<?php

namespace App\Models;

use App\Contracts\MatterPartyContract;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MatterParty extends Pivot
{

    //
    protected $fillable = [
        'matter_id',
        'party_id',
        'parent_id',
        'type',
        'role',
    ];

    public $timestamps = true;

    public function representedParty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Party::class, 'parent_id');
    }

    public function party(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
