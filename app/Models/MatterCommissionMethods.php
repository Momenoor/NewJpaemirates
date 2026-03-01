<?php

namespace App\Models;

// Add these methods to your existing Matter model

use App\Services\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait MatterCommissionMethods
{
    public function calculateWorkingDays(): int
    {
        if (empty($this->last_action_date) || empty($this->reported_date)) {
            return $this->commissionCompeletionPeriod = 0;
        }

        $start = Carbon::parse($this->last_action_date);
        $end   = Carbon::parse($this->reported_date);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start]; // swap if reversed
        }

        // Count weekdays (Mon–Fri). diffInDaysFiltered is inclusive of start, exclusive of end by default.
        // Add 1 day to include the end date consistently with your previous while-loop behavior.
        $days = $start->copy()->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $end->copy()->addDay());

        return $this->commissionCompeletionPeriod = $days;
    }

    public function calculateCommission()
    {
        $settings = config('system.commission');
        $byType = $settings['by_type'];
        $byPeriod = $settings['by_period'];
        $byCount = $settings['by_count'];

        // Initialize commission values
        $this->commissionPercent = 0;
        $this->commissionAmount = 0;

        // Determine which period settings to use (default or special)
        // Add special type IDs to config or define them here
        $specialTypeIds = config('system.commission.special_type_ids', []); // e.g., [76, 217]
        $periodKey = in_array($this->type_id, $specialTypeIds) ? 'special' : 'default';
        $periodSettings = $byPeriod[$periodKey];

        // Check if this is a fixed amount type (like type 76)
        $isFixedAmountType = isset($byType[$this->type_id]) && is_array($byType[$this->type_id]);

        if ($isFixedAmountType) {
            // For fixed amount types, just return the fixed amount without any bonus
            $this->commissionAmount = $this->calculateTypeBasedCommission($byType[$this->type_id]);
            $this->commissionPercent = 0; // No percentage for fixed amounts

            return $this->commissionAmount;
        }

        // Type-based percentage commission
        if (isset($byType[$this->type_id])) {
            $this->commissionPercent = $byType[$this->type_id];
        } else {
            // Period-based commission calculation
            $this->commissionPercent = $this->calculatePeriodBasedCommission($periodSettings);
        }

        // Count-based commission calculation (adds extra percentage)
        $count = $this->calculateCasesCount();
        $extraPercent = $this->calculateCountBasedCommission($byCount, $count);
        $this->commissionPercent += $extraPercent;

        // Calculate final commission amount
        $this->commissionAmount = ($this->commissionPercent / 100) * $this->claimsWithOutVat->sum('amount');
        return $this->commissionAmount;
    }

    private function calculateTypeBasedCommission($typeSetting): float
    {
        if (is_array($typeSetting)) {
            // Fixed amount based on expert involvement
            $notesText = $this->notes->pluck('text')->implode(' / / / / ');
            // Return the fixed amount directly (200 or 400)
            return str_contains($notesText, 'رضا') ? ($typeSetting['with_expert']/100) * $this->claimsWithOutVat->sum('amount') : ($typeSetting['without_expert']/100) * $this->claimsWithOutVat->sum('amount');
        } else {
            // This shouldn't be called for percentage types in the new flow
            // But keeping for backward compatibility
            return ($typeSetting / 100) * $this->claimsWithOutVat->sum('amount');
        }
    }

    private function calculatePeriodBasedCommission(array $periods): int
    {
        $completionPeriod = $this->calculateWorkingDays();

        foreach ($periods as $period) {
            // Handle periods without start (catch-all)
            if (!isset($period['start'])) {
                return $period['percent'];
            }

            // Check if completion period falls within this range
            if ($completionPeriod >= $period['start'] && $completionPeriod <= $period['end']) {
                return $period['percent'];
            }
        }

        // Return last period's percent if no match found
        return end($periods)['percent'];
    }


    private function calculateCasesCount(?int $assistantId = null): int
    {
        $startDay = config('system.commission.start_day', 26);
        $endDay = config('system.commission.end_day', 25);

        // Determine which assistant we’re counting for.
        if (!$assistantId) {
            // Fallback to first assistant (kept for BC),
            // but ideally always pass $assistantId explicitly.
            $assistant = \DB::table('matter_expert')
                ->where('matter_id', $this->id)
                ->where('type', 'assistant')
                ->first()
                ?: \DB::table('matter_expert')
                    ->where('matter_id', $this->id)
                    ->where('type', 'assistant')
                    ->first();

            if (!$assistant) {
                \Log::warning('No assistant found for matter', ['matter_id' => $this->id]);
                return 0;
            }
            $assistantId = $assistant->expert_id;
        }

        $reportedDate = \Carbon\Carbon::parse($this->reported_date);
        if ($reportedDate->day >= $startDay) {
            $periodStart = $reportedDate->copy()->day($startDay);
            $periodEnd = $reportedDate->copy()->addMonth()->day($endDay);
        } else {
            $periodStart = $reportedDate->copy()->subMonth()->day($startDay);
            $periodEnd = $reportedDate->copy()->day($endDay);
        }

        return self::whereBetween('reported_date', [$periodStart, $periodEnd])
            ->where(function ($query) use ($assistantId) {
                $query->whereHas('matterExperts', function ($q) use ($assistantId) {
                        $q->where('expert_id', $assistantId)->where('type', 'assistant');
                    });
            })
            ->count();
    }

    private static function assistantsMapForMatters(array $matterIds): array
    {
        $map = [];

        if (!empty($matterIds)) {
            // matter_expert (singular)
            $singular = \DB::table('matter_expert')
                ->select('matter_id', 'expert_id')
                ->whereIn('matter_id', $matterIds)
                ->where('type', 'assistant')
                ->get();

            foreach ($singular as $row) {
                $map[$row->matter_id][] = (int) $row->expert_id;
            }

            // matter_expert (plural)
            $plural = \DB::table('matter_expert')
                ->select('matter_id', 'expert_id')
                ->whereIn('matter_id', $matterIds)
                ->where('type', 'assistant')
                ->get();

            foreach ($plural as $row) {
                $map[$row->matter_id][] = (int) $row->expert_id;
            }

            // de-duplicate
            foreach ($map as $mid => $ids) {
                $map[$mid] = array_values(array_unique($ids));
            }
        }

        return $map;
    }

    private function calculateCountBasedCommission(array $countSettings, int $count): float
    {
        // Handle explicit count values
        if (isset($countSettings[$count])) {
            return $countSettings[$count];
        }

        // Handle "more_than_X" cases
        $maxDefinedCount = max(array_filter(array_keys($countSettings), 'is_numeric'));

        if ($count > $maxDefinedCount && isset($countSettings['more_than_' . $maxDefinedCount])) {
            return $countSettings['more_than_' . $maxDefinedCount];
        }

        // No extra commission for counts below threshold
        return 0;
    }

    public function getCommissionAttribute(): array
    {
        $this->calculateCommission();

        // Get count for debugging
        $count = $this->calculateCasesCount();
        $countBonus = $this->calculateCountBasedCommission(config('system.commission.by_count'), $count);

        return [
            'amount' => app(Money::class)->getFormattedNumber($this->commissionAmount),
            'percent' => $this->commissionPercent,
            'period' => $this->commissionCompeletionPeriod,
            'count' => $count, // Add count for debugging
            'count_bonus' => $countBonus, // Add bonus percentage for debugging
        ];
    }

// Add this method to see commission breakdown by period
    public static function getCommissionSummaryByPeriod($startDate, $endDate)
    {
        $startDay = config('system.commission.start_day', 26);
        $endDay   = config('system.commission.end_day', 25);

        // 1) Pull matters in range
        $cases = self::whereBetween('reported_date', [$startDate, $endDate])
            ->orderBy('reported_date')
            ->get();

        $matterIds = $cases->pluck('id')->all();

        // 2) Build assistants rows via UNION ALL across both pivot tables
        //    IMPORTANT: Remove the type filter first to prove data exists. We’ll add it back after verifying.
        $assistantRows = DB::query()
            ->fromSub(function ($q) {
                $q->from('matter_expert')
                    ->select([
                        'matter_id',
                        'expert_id',
                        DB::raw("TRIM(LOWER(COALESCE(type,''))) AS pivot_type")
                    ])
                    ->unionAll(
                        DB::table('matter_expert')
                            ->select([
                                'matter_id',
                                'expert_id',
                                DB::raw("TRIM(LOWER(COALESCE(type,''))) AS pivot_type")
                            ])
                    );
            }, 'mx')
            ->whereIn('matter_id', $matterIds)
            ->select(['matter_id', 'expert_id', 'pivot_type'])
            ->get();

        // 3) If this shows rows with pivot_type like 'Assistant', ' assistant ', '', null, etc.,
        //    normalize or broaden the filter. For now, filter in PHP for visibility:
        $assistantRows = $assistantRows->filter(function ($r) {
            return in_array($r->pivot_type, ['assistant','assistant_expert','asst'], true)
                || $r->pivot_type === 'assistant ' // common trailing space
                || $r->pivot_type === '';          // temporarily allow empty to see if data is wrongly blank
        });

        // 4) Group assistants per matter_id
        $assistantsByMatter = $assistantRows
            ->groupBy('matter_id')
            ->map(function ($rows) {
                return array_values(array_unique(array_map('intval', array_column($rows->toArray(), 'expert_id'))));
            });

        $summary = [];

        foreach ($cases as $case) {
            // Compute period for this case
            $reportedDate = \Carbon\Carbon::parse($case->reported_date);
            if ($reportedDate->day >= $startDay) {
                $periodStart = $reportedDate->copy()->day($startDay);
                $periodEnd   = $reportedDate->copy()->addMonth()->day($endDay);
            } else {
                $periodStart = $reportedDate->copy()->subMonth()->day($startDay);
                $periodEnd   = $reportedDate->copy()->day($endDay);
            }
            $periodKey = $periodStart->toDateString().' to '.$periodEnd->toDateString();

            $workingDays = $case->calculateWorkingDays();

            $assistantIds = $assistantsByMatter->get($case->id, []);

            // If no assistants, still emit a row (optional)
            if (empty($assistantIds)) {
                // matter-level commission
                $case->calculateCommission();
                $summary[] = [
                    'case_id'            => $case->id,
                    'case_number'        => $case->number ?? $case->id,
                    'reported_date'      => $case->reported_date,
                    'assistant_id'       => null,
                    'period'             => $workingDays,
                    'count_in_period'    => 0,
                    'commission_percent' => $case->commissionPercent,
                    'commission_amount'  => $case->commissionAmount,
                ];
                continue;
            }

            $totalAssistants = count($assistantIds) ?: 1; // avoid division by zero
            $case->calculateCommission();
            $sharedAmount = $case->commissionAmount / $totalAssistants;
            // One row PER assistant
            foreach ($assistantIds as $assistantId) {
                $countForAssistant = $case->calculateCasesCount($assistantId);

                $summary[] = [
                    'case_id'            => $case->id,
                    'case_number'        => $case->number ?? $case->id,
                    'reported_date'      => $case->reported_date,
                    'assistant_id'       => $assistantId,
                    'period'             => $workingDays,
                    'count_in_period'    => $countForAssistant,
                    'commission_percent' => $case->commissionPercent,
                    'commission_amount'  => $sharedAmount,
                ];
            }
        }

        // IMPORTANT: Ensure caller is NOT deduplicating by case_id (unique/keyBy/groupBy)
        return $summary;
    }




// Add this method to help debug the commission calculation
    public function getCommissionDebugInfo(): array
    {
        $settings = config('system.commission');
        $count = $this->calculateCasesCount();

        // Get assistant info for debugging
        $assistant = $this->assistants()->first();
        $assistantData = null;
        if ($assistant) {
            $assistantData = [
                'attributes' => $assistant->getAttributes(),
                'relations' => array_keys($assistant->getRelations()),
            ];
        }

        // Calculate date range
        $filterStartDate = request()->input('start_date');
        if ($filterStartDate) {
            $startCountDate = Carbon::parse($filterStartDate);
            $endCountDate = $startCountDate->copy()->addMonth()->subDay();
        } else {
            $completionDate = Carbon::parse($this->reported_date);
            $startDay = config('system.commission.start_day', 26);
            $endDay = config('system.commission.end_day', 25);

            if ($completionDate->day >= $startDay) {
                $startCountDate = $completionDate->copy()->day($startDay);
                $endCountDate = $completionDate->copy()->addMonth()->day($endDay);
            } else {
                $startCountDate = $completionDate->copy()->subMonth()->day($startDay);
                $endCountDate = $completionDate->copy()->day($endDay);
            }
        }

        return [
            'case_id' => $this->id,
            'type_id' => $this->type_id,
            'assistant_data' => $assistantData,
            'reported_date' => $this->reported_date,
            'count_period' => [
                'start' => $startCountDate->toDateString(),
                'end' => $endCountDate->toDateString(),
            ],
            'cases_count' => $count,
            'working_days' => $this->calculateWorkingDays(),
            'base_percent' => $this->commissionPercent - $this->calculateCountBasedCommission($settings['by_count'], $count),
            'count_bonus' => $this->calculateCountBasedCommission($settings['by_count'], $count),
            'total_percent' => $this->commissionPercent,
            'commission_amount' => $this->commissionAmount,
        ];
    }
}


