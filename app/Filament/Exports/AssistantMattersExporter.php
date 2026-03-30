<?php

namespace App\Filament\Exports;

use App\Enums\FeeType;
use App\Models\MatterParty;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

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
            ExportColumn::make('difficulty')
                ->label(__('Difficulty'))
                ->getStateUsing(fn($record) => $record->matter?->difficulty?->getLabel() ?? '—'),
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
    public function getXlsxWriterOptions(): ?Options
    {
        $options = new Options();

        // 1: Matter (Reference)
        $options->setColumnWidth(15, 1);

        // 2: Assistant
        $options->setColumnWidth(25, 2);

        // 3: Court
        $options->setColumnWidth(22, 3);

        // 4: Type
        $options->setColumnWidth(18, 4);

        // 5: Status
        $options->setColumnWidth(15, 5);

        // 6: Level (العمود الجديد الذي أضفته)
        $options->setColumnWidth(15, 6);

        // 7: Experts
        $options->setColumnWidth(30, 7);

        // 8: Plaintiffs (المدعون)
        $options->setColumnWidth(40, 8);

        // 9: Defendants (المدعى عليهم)
        $options->setColumnWidth(40, 9);

        // 10: Distributed At
        $options->setColumnWidth(18, 10);

        // 11: Initial Report Date
        $options->setColumnWidth(18, 11);

        // 12: Final Report Date
        $options->setColumnWidth(18, 12);

        // 13: Total Fees (excl. VAT)
        $options->setColumnWidth(22, 13);

        // 14: Total Collected (excl. VAT)
        $options->setColumnWidth(22, 14);

        // 15: Notes
        $options->setColumnWidth(50, 15);

        return $options;
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

    public function getXlsxCellStyle(): ?Style
    {
        return new Style()
            ->setShouldWrapText()
            ->setFontSize(11)
            ->setFontName('Arial');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getXlsxHeaderCellStyle(): ?Style
    {
        return new Style()
            ->setFontBold()
            ->setShouldWrapText()
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(31, 56, 100))
            ->setCellAlignment(CellAlignment::CENTER);
    }

    /**
     * @throws InvalidSheetNameException
     * @throws WriterNotOpenedException
     * @throws InvalidArgumentException
     */
    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheetView = new SheetView();
        $sheetView->setFreezeRow(2);
        $sheetView->setRightToLeft(app()->getLocale() == 'ar');

        $sheet = $writer->getCurrentSheet();
        $sheet->setSheetView($sheetView);
        $sheet->setName('Assistant Matters');

        return $writer;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = trans_choice('export_completed', $export->successful_rows, ['count' => Number::format($export->successful_rows)]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . trans_choice('export_failed', $failedRowsCount, ['count' => Number::format($failedRowsCount)]);
        }

        return $body;
    }
}
