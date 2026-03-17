<?php

use App\Models\Matter;
use App\Filament\Exports\MatterExporter;
use Filament\Actions\Exports\Models\Export;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$export = new Export();
$export->exporter = MatterExporter::class;

$columnMap = [
    'unpaid_amount' => [
        'isEnabled' => true,
        'label' => 'Unpaid',
    ],
    'something_else' => [
        'isEnabled' => true,
        'label' => 'Something',
    ],
];

$exporter = $export->getExporter($columnMap, []);

$matter = Matter::first();
if (!$matter) {
    echo "No Matter record found to test.\n";
    exit;
}

$columns = $exporter->getCachedColumns();
echo "Columns: " . implode(', ', array_keys($columns)) . "\n";
echo "ColumnMap: " . implode(', ', array_keys($columnMap)) . "\n";

try {
    $data = ($exporter)($matter);
    echo "Success! Result: " . json_encode($data) . "\n";
} catch (\Throwable $e) {
    echo "Caught exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
