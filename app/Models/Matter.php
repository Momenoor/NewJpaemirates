<?php

namespace App\Models;

use App\Enums\MatterCollectionStatus;
use App\Enums\MatterStatus;
use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property mixed $status
 */
class Matter extends Model
{

    protected static function booted(): void
    {
        static::deleting(function (Matter $matter) {
            $matter->matterParties()->delete();
            $matter->fees()->delete();
            $matter->allocations()->delete();
            $matter->procedures()->delete();
            $matter->notes()->delete();
            $matter->attachments()->delete();
            $matter->requests()->delete();
        });
    }

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

    /**
     * Top-level matter_party rows with role 'party' only.
     */
    public function mainPartiesOnly(): HasMany
    {
        return $this->hasMany(MatterParty::class, 'matter_id')
            ->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0))
            ->where('role', 'party');
    }

    /**
     * Top-level matter_party rows with role 'expert' only.
     */
    public function mainExpertsOnly(): HasMany
    {
        return $this->hasMany(MatterParty::class, 'matter_id')
            ->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0))
            ->where('role', 'expert');
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

    /**
     * Parties (role = 'party') grouped by type with per-type indexing.
     * e.g. Plaintiff #1, Plaintiff #2, Defendant #1, Defendant #2 ...
     *
     * Used by RepeatableEntry::make('indexedParties') in the infolist.
     */
    public function getIndexedPartiesAttribute()
    {
        if (isset($this->_indexedPartiesCache)) {
            return $this->_indexedPartiesCache;
        }

        // If eager loaded by getEloquentQuery() → use in-memory collection, zero queries
        // If not loaded (e.g. accessed outside resource) → query with eager loads
        if ($this->relationLoaded('mainPartiesOnly')) {
            $parties = $this->mainPartiesOnly;

            // representatives may or may not be loaded — load if missing
            $parties->each(function ($matterParty) {
                if (! $matterParty->relationLoaded('representatives')) {
                    $matterParty->load('representatives.party');
                } elseif ($matterParty->representatives->isNotEmpty()) {
                    $matterParty->representatives->each(function ($rep) {
                        if (! $rep->relationLoaded('party')) {
                            $rep->load('party');
                        }
                    });
                }
            });
        } else {
            $parties = $this->mainPartiesOnly()
                ->with(['party', 'representatives.party'])
                ->get();
        }

        $this->_indexedPartiesCache = $parties
            ->groupBy('type')
            ->flatMap(function ($group) {
                return $group->values()->map(function ($item, $index) {
                    $item->role_index = $index + 1;
                    $item->representatives->values()->each(function ($rep, $repIndex) {
                        $rep->rep_index = $repIndex + 1;
                    });
                    return $item;
                });
            })
            ->values();

        return $this->_indexedPartiesCache;
    }

    public function getIndexedExpertsAttribute()
    {
        if (isset($this->_indexedExpertsCache)) {
            return $this->_indexedExpertsCache;
        }

        if ($this->relationLoaded('mainExpertsOnly')) {
            $experts = $this->mainExpertsOnly;

            $experts->each(function ($matterParty) {
                if (! $matterParty->relationLoaded('party')) {
                    $matterParty->load('party');
                }
            });
        } else {
            $experts = $this->mainExpertsOnly()
                ->with('party')
                ->get();
        }

        $this->_indexedExpertsCache = $experts
            ->groupBy('type')
            ->flatMap(function ($group) {
                return $group->values()->map(function ($item, $index) {
                    $item->role_index = $index + 1;
                    return $item;
                });
            })
            ->values();

        return $this->_indexedExpertsCache;
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
