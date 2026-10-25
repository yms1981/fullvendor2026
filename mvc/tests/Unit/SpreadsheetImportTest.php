<?php
/**
 * Pruebas unitarias del lector Excel usado en importaciones company.
 */
require_once dirname(__DIR__, 2) . '/application/libraries/Spreadsheet_import.php';

class SpreadsheetImportTest extends TestCase
{
    public function test_allowed_extensions_include_xlsx(): void
    {
        $ext = Spreadsheet_import::allowedExtensions();
        $this->assertContains('xlsx', $ext);
        $this->assertContains('xls', $ext);
    }

    public function test_is_allowed_import_filename(): void
    {
        $this->assertTrue(Spreadsheet_import::isAllowedImportFilename('productcsv.xlsx'));
        $this->assertTrue(Spreadsheet_import::isAllowedImportFilename('datos.XLS'));
        $this->assertFalse(Spreadsheet_import::isAllowedImportFilename('malware.exe'));
        $this->assertFalse(Spreadsheet_import::isAllowedImportFilename(''));
    }
}
