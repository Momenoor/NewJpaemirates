<?php

namespace App\Models;

use App\Enums\MatterCollectionStatus;
use App\Enums\MatterCommissiong;
use App\Enums\MatterStatus;
use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * @property mixed $status
 */
class Matter extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    protected static function booted(): void
    {
        static::creating(function (Matter $matter) {
            if (!$matter->collection_status) {
                $matter->collection_status = MatterCollectionStatus::NO_FEES;
            }
        });

        static::created(function (Matter $matter) {
            $matter->updateCollectionStatus();
        });

        static::updated(function (Matter $matter) {
            $matter->updateCollectionStatus();
        });

        static::deleting(function (Matter $matter) {


            // Cascade soft delete to children
            $matter->children()->delete();
        });

        static::restoring(function (Matter $matter) {
            // Cascade restore to children
            $matter->children()->onlyTrashed()->restore();
        });

        static::forceDeleting(function (Matter $matter) {
            // Children — force delete recursively so their own relations also fire
            $matter->children()->withTrashed()->each(
                fn(Matter $child) => $child->forceDelete()
            );

            // Pivot rows (matter_party — parties, experts, representatives)
            $matter->matterParties()->delete();

            // Fees and their allocations
            $matter->fees()->each(function ($fee) {
                $fee->allocations()->delete();
                $fee->delete();
            });

            // Other relations
            $matter->allocations()->delete();
            $matter->notes()->delete();
            $matter->attachments()->delete();
            $matter->requests()->delete();
        });
    }

    protected $fillable = [
        'year',
        'number',
        'commissioning',
        'received_at',
        'next_session_date',
        'initial_report_at',
        'final_report_at',
        'court_id',
        'type_id',
        'level',
        'difficulty',
        'collection_status',
        'review_count',
        'has_substantive_changes',
        'has_court_penalty',
        'final_report_memo_date',
        'is_office_work'
    ];

    protected array $dates = [
        'received_at',
        'next_session_date',
        'initial_report_at',
        'final_report_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'difficulty' => MatterDifficulty::class,
        'level' => MatterLevel::class,
        'collection_status' => MatterCollectionStatus::class,
        'commissioning' => MatterCommissiong::class,
        'review_count' => 'integer',
        'has_substantive_changes' => 'boolean',
        'has_court_penalty' => 'boolean',
        'final_report_memo_date' => 'date',
        'is_office_work' => 'boolean',
    ];

    public $timestamps = true;

    public function children(): HasMany
    {
        return $this->hasMany(Matter::class, 'parent_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Matter::class, 'parent_id');
    }

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

    public function assistantsOnly(): HasMany
    {
        return $this->hasMany(MatterParty::class, 'matter_id')
            ->where(['role' => 'expert', 'type' => 'assistant']);
    }

    public function isOfficeWork(): bool
    {
        return $this->is_office_work;
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
    // Add this at the top of the class — a plain PHP property, invisible to Eloquent
    protected array $_cache = [];

    public function getIndexedPartiesAttribute()
    {
        if (isset($this->_cache['indexedParties'])) {
            return $this->_cache['indexedParties'];
        }

        $relation = $this->relationLoaded('mainPartiesOnly')
            ? $this->mainPartiesOnly
            : $this->mainPartiesOnly()->with('party', 'representatives.party')->get();

        $result = $relation
            ->groupBy('type')
            ->flatMap(function ($group) {
                return $group->values()->map(function ($mp, $i) {
                    $mp->role_index = $i + 1;
                    return $mp;
                });
            });

        $this->_cache['indexedParties'] = $result;
        return $result;
    }

    public function getIndexedExpertsAttribute()
    {
        if (isset($this->_cache['indexedExperts'])) {
            return $this->_cache['indexedExperts'];
        }

        $relation = $this->relationLoaded('mainExpertsOnly')
            ? $this->mainExpertsOnly
            : $this->mainExpertsOnly()->with('party')->get();

        $result = $relation
            ->groupBy('type')
            ->flatMap(function ($group) {
                return $group->values()->map(function ($mp, $i) {
                    $mp->role_index = $i + 1;
                    return $mp;
                });
            });

        $this->_cache['indexedExperts'] = $result;
        return $result;
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
            $totalAmount = (float)$fees->sum('amount');
            $totalAllocated = (float)$this->allocations()->sum('amount');

            if ($totalAllocated <= 0) {
                $this->collection_status = MatterCollectionStatus::UNPAID;
            } elseif ($totalAllocated < $totalAmount) {
                $this->collection_status = MatterCollectionStatus::PARTIAL;
            } else {
                $this->collection_status = MatterCollectionStatus::PAID;
            }
        }

        if ($this->isDirty('collection_status')) {
            $this->saveQuietly();
        }
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function status(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->initial_report_at) return 'In Progress';
                if (!$this->final_report_at) return 'Initial Report';
                return 'Final Report';
            }
        );
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

    public function incentiveLines(): HasMany
    {
        return $this->hasMany(IncentiveLine::class);
    }

    public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Request::class);
    }

    public function finalReportSubmission(): void
    {
        if (!$this->initial_report_at) {
            $this->initial_report_at = now();
        }
        $this->final_report_at = now();
        $this->saveQuietly();
    }

    public function hasFinalReport(): bool
    {
        return $this->final_report_at !== null;
    }

}
