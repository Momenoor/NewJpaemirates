<?php

namespace App\Models;

use App\Services\ClaimCollectionStatus;
use App\Services\ClaimsService;
use App\Services\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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
        'user_id',
        'expert_id',
        'court_id',
        'level_id',
        'type_id',
        'assign',
        'parent_id',
        'claim_status',
        'last_action_date',
        'level',
        'difficulty'
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

    public $timestamps = true;

    public const INDIVIDUAL = 'individual';
    public const COMMITTEE = 'committee';

    public int $commissionAmount = 0;

    public int $commissionPercent = 0;

    public int $commissionCompeletionPeriod = 0;

    public static function boot(): void
    {
        parent::boot();
//        static::retrieved(function ($matter) {
//            (new ClaimsService($matter));
//        });
    }

        public function getAssistantAttribute(): Expert|\Closure|null
    {
        //$this->unsetRelation('assistants');
        return $this->assistants->first();
    }

    public function getPlaintiffAttribute(): \Closure|Party|null
    {
        //$this->unsetRelation('plaintiffs');
        return $this->plaintiffs->first();
    }

    public function getDefendantAttribute(): \Closure|Party|null
    {
        //$this->unsetRelation('defendants');
        return $this->defendants->first();
    }

    public function getClaimsSumAmountAttribute()
    {
        //return $this->claims->sum('amount');
        return ClaimCollectionStatus::make($this)->getSumTotalClaims();
    }

    public function getClaimsSumAmountUnformattedAttribute()
    {
        //return $this->claims->sum('amount');
        return $this->claims->sum('amount');
    }

    public function getCashSumAmountAttribute()
    {
        return ClaimCollectionStatus::make($this)->getSumCollectedClaims();
    }

    public function court(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function expert(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function matterExperts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatterExpert::class);
    }

    public function matter_experts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatterExpert::class);
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function assistants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Expert::class, 'matter_expert')
            ->wherePivot('type', '=', 'assistant')->withTimestamps();
    }

    public function experts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Expert::class, 'matter_expert')
            ->withPivot('type')->withTimestamps();
    }

    public function marketers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'matter_marketing')->withPivot('type')->withTimestamps();
    }

    public function internalMarketers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'matter_marketing')
            ->wherePivot('type', '=', 'marketer')->withTimestamps();
    }

    public function externalMarketers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'matter_party')
            ->wherePivot('type', '=', 'external_marketer')->withTimestamps();
    }

    public function plaintiffs(): \LaravelIdea\Helper\App\Models\_IH_Party_QB|\Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->parties()->wherePivot('type', 'plaintiff');
    }

    public function defendants(): \LaravelIdea\Helper\App\Models\_IH_Party_QB|\Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->parties()->wherePivot('type', 'defendant');
    }

    public function lawyers(): \LaravelIdea\Helper\App\Models\_IH_Party_QB|\Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->parties()
            ->wherePivot('type', 'lawyer')
            ->with('representedParty'); // See logic below
    }

    public function parties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'matter_party')
            ->using(MatterParty::class) // Tell Laravel to use your custom class
            ->withPivot(['type','role', 'parent_id'])
            ->withTimestamps();
    }

    public function matterParties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Party::class)->withPivot(['type', 'parent_id'])->withTimestamps();
    }

    public function onlyParties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Party::class)->withPivot(['type', 'parent_id'])->wherePivotIn('type', ['defendant', 'plaintiff', 'implicat-litigant'])->withTimestamps();
    }

    public function procedures(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Procedure::class);
    }

    public function nextSessionDateProcedureList()
    {
        return $this->procedures()
            ->where('type', 'next_session_date');
    }

    public function claims(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function claimsWithOutVat(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Claim::class)->where('type', '!=', 'vat');
    }

    public function cashes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cash::class);
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('matters.status', 'current');
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('matters.status', ['reported', 'submitted']);
    }

    public function isCurrent(): bool
    {
        return (!$this->isSubmitted() and !$this->isReported()) or $this->status == 'current';
    }

    public function isReported(): bool
    {
        return (!is_null($this->reported_date)) or $this->status == 'reported';
    }

    public function isSubmitted(): bool
    {
        return $this->isReported() && (!is_null($this->submitted_date) or $this->status == 'submitted');
    }

    public function isOverPaid(): bool
    {
        return Cash::OVERPAID == ClaimCollectionStatus::make($this)->getClaimStatus();
    }

    public function isPaid(): bool
    {
        return Cash::PAID == ClaimCollectionStatus::make($this)->getClaimStatus();
    }

    public function isUnpaid(): bool
    {
        return Cash::UNPAID == ClaimCollectionStatus::make($this)->getClaimStatus();
    }

    public function isPartial(): bool
    {
        return Cash::PARTIAL == ClaimCollectionStatus::make($this)->getClaimStatus();
    }

    public function claimsOpen(): bool
    {
        return $this->isUnpaid() or $this->isPartial();
    }

    public function dueAmount()
    {
        return ClaimCollectionStatus::make($this)->getSumDueClaims();
    }

    public function dueClaims()
    {
        return ClaimCollectionStatus::make($this)->getDueClaims();
    }

    public function isPrivate(): bool
    {
        return $this->whereNotIn('experts.id', config('system.experts.main'));
    }

    public function isOffice(): bool
    {
        return !$this->isPrivate();
    }

    public function isNotPrivate(): bool
    {
        return $this->isOffice();
    }

    public function getClaimStatusColorAttribute()
    {
        return config('system.claims.status.' . $this->claim_status . '.color');
    }

    public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Request::class);
    }

}
