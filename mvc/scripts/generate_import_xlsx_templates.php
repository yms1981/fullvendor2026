<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$sourceDir = $root . '/companyasset/import-templates';
$csvFiles = glob($sourceDir . '/*.csv') ?: [];

foreach ($csvFiles as $csvPath) {
    $base = basename($csvPath, '.csv');
    $xlsxPath = $sourceDir . '/' . $base . '.xlsx';

    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
        fwrite(STDERR, "Cannot read {$csvPath}\n");
        continue;
    }

    $headers = fgetcsv($handle, 0, ',') ?: [];
    $rows = [];
    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        if ($data === null) {
            continue;
        }
        $hasContent = false;
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                $hasContent = true;
                break;
            }
        }
        if ($hasContent) {
            $rows[] = array_map(static fn ($v) => trim((string) $v), $data);
        }
    }
    fclose($handle);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($headers)) {
        $sheet->fromArray([$headers], null, 'A1');
    }
    if (!empty($rows)) {
        $sheet->fromArray($rows, null, 'A2');
    }

    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($xlsxPath);
    echo "Created {$xlsxPath}\n";
}

echo "Done.\n";
