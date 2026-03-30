<?php

namespace App\Filament\Exports;

use App\Enums\FeeType;
use App\Models\MatterParty;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AssistantMattersExporter extends Exporter
{
    protected static ?string $model = MatterParty::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference')
                ->label(__('Matter'))
                ->getStateUsing(fn($record) => $record->matter?->year . '/' . $record->matter?->number
                ),
            ExportColumn::make('assistant')
                ->label(__('Assistant'))
                ->getStateUsing(fn($record) => $record->party?->name ?? '—'),
            ExportColumn::make('court')
                ->label(__('Court'))
                ->getStateUsing(fn($record) => $record->matter?->court?->name ?? '—'),
            ExportColumn::make('type')
                ->label(__('Type'))
                ->getStateUsing(fn($record) => $record->matter?->type?->name ?? '—'),
            ExportColumn::make('status')
                ->label(__('Status'))
                ->getStateUsing(fn($record) => $record->matter?->status ?? '—'),
            ExportColumn::make('matter.mainExpertsOnly.name')
                ->label(__('Experts')),
            ExportColumn::make('plaintiffs')
                ->label(__('Plaintiffs'))
                ->getStateUsing(fn($record) => MatterParty::where('matter_id', $record->matter_id)
                    ->where('role', 'party')->where('type', 'plaintiff')
                    ->with('party')->get()
                    ->map(fn($mp) => $mp->party?->name ?? '—')
                    ->join('\n')
                ),

            ExportColumn::make('defendants')
                ->label(__('Defendants'))
                ->getStateUsing(fn($record) => MatterParty::where('matter_id', $record->matter_id)
                    ->where('role', 'party')->where('type', 'defendant')
                    ->with('party')->get()
                    ->map(fn($mp) => $mp->party?->name ?? '—')
                    ->join(' | ')
                ),
            ExportColumn::make('matter.distributed_at')
                ->label(__('Distributed At')),
            ExportColumn::make('matter.initial_report_at')
                ->label(__('Initial Report Date')),
            ExportColumn::make('matter.final_report_at')
                ->label(__('Final Report Date')),
            ExportColumn::make('total_fees')
                ->label(__('Total Fees (excl. VAT)'))
                ->getStateUsing(fn($record) => $record->total_fees ?? 0),
            ExportColumn::make('total_collected')
                ->label(__('Total Collected (excl. VAT)'))
                ->getStateUsing(fn($record) => $record->total_allocations ?? 0),
            ExportColumn::make('notes')
                ->label(__('Notes'))
                ->getStateUsing(fn($record) => $record->matter?->notes
                    ->map(fn($note) => $note->content)->filter()->join(' | ') ?? '—'
                ),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->where('matter_party.role', 'expert')
            ->where('matter_party.type', 'assistant')
            ->whereHas('matter')
            ->withSum(['matter_fees as total_fees' => function ($q) {
                $q->where('type', '!=', FeeType::VAT->value);
            }], 'amount')
            ->withSum(['matter_allocations as total_allocations' => function ($q) {
                $q->whereHas('fee', function ($f) {
                    $f->where('type', '!=', FeeType::VAT->value);
                });
            }], 'amount')
            ->with(['party',
                'matter' => function ($q) {
                    $q->with(['court', 'type', 'notes']);
                }
            ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('export_completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
