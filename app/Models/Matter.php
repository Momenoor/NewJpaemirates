<?php

namespace App\Models;

use App\Enums\MatterCollectionStatus;
use App\Enums\MatterStatus;
use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use App\Services\ClaimCollectionStatus;
use App\Services\ClaimsService;
use App\Services\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property mixed $status
 */
class Matter extends Model
{

    use MatterCommissionMethods;


    protected $fillable = [
        'year',
        'number',
        'status',
        'commissioning',
        'external_marketing_rate',
        'received_date',
        'next_session_date',
        'reported_date',
        'submitted_date',
        'court_id',
        'type_id',
        'level',
        'difficulty',
        'collection_status'
    ];

    protected $dates = [
        'received_date',
        'next_session_date',
        'reported_date',
        'submitted_date',
        'created_at',
        'updated_at',
        'last_action_date',
    ];

    protected $casts = [
        'status' => MatterStatus::class,
        'difficulty' => MatterDifficulty::class,
        'level' => MatterLevel::class,
        'collection_status' => MatterCollectionStatus::class,
    ];

    public $timestamps = true;

    public const INDIVIDUAL = 'individual';
    public const COMMITTEE = 'committee';

    public int $commissionAmount = 0;

    public int $commissionPercent = 0;

    public int $commissionCompeletionPeriod = 0;

    public function court(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * All matter_party rows (including representatives).
     */
    public function matterParties(): HasMany
    {
        return $this->hasMany(MatterParty::class, 'matter_id');
    }

    /**
     * Top-level matter_party rows only (excludes representatives).
     * Used by the outer Filament Repeater.
     */
    public function mainParties(): HasMany
    {
        return $this->hasMany(MatterParty::class, 'matter_id')
            ->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
    }

    // -----------------------------------------------------------------------
    // FOR QUERYING / READING
    // BelongsToMany gives you Party models directly, useful outside forms.
    // e.g. $matter->parties, $matter->mainPartiesQuery
    // -----------------------------------------------------------------------

    /**
     * All parties in this matter via belongsToMany.
     * e.g. $matter->parties->pluck('name')
     */
    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'matter_party')
            ->withPivot('id', 'type', 'role', 'parent_id');
    }

    /**
     * Top-level parties only via belongsToMany (no representatives).
     */
    public function mainPartiesQuery(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'matter_party')
            ->withPivot('id', 'type', 'role', 'parent_id')
            ->wherePivot(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
    }

    public function fees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * Update the matter's collection status based on its fees.
     */
    public function updateCollectionStatus(): void
    {
        $fees = $this->fees()->get();

        if ($fees->isEmpty()) {
            $this->collection_status = MatterCollectionStatus::NO_FEES;
        } else {
            $totalAmount = (float) $fees->sum('amount');
            $totalAllocated = (float) $this->allocations()->sum('amount');

            if ($totalAllocated <= 0) {
                $this->collection_status = MatterCollectionStatus::UNPAID;
            } elseif ($totalAllocated < $totalAmount) {
                $this->collection_status = MatterCollectionStatus::PARTIAL;
            } else {
                $this->collection_status = MatterCollectionStatus::PAID;
            }
        }

        if ($this->isDirty('collection_status')) {
            $this->save();
        }
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Type::class);
    }



    public function procedures(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Procedure::class);
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attachment::class);
    }

      public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Request::class);
    }

}
