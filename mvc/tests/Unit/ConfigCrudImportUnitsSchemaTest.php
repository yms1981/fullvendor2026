<?php

require_once dirname(__DIR__, 2) . '/application/core/ConfigCrudImportSchemas.php';

/**
 * Validación del esquema de importación de unidades (misma plantilla que unitscsv).
 */
class ConfigCrudImportUnitsSchemaTest extends TestCase
{
    /** @var array<string, mixed> */
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = ConfigCrudImportSchemas::units();
    }

    public function test_validate_accepts_spreadsheet_sample_rows(): void
    {
        $validate = $this->schema['validate'];
        $this->assertIsCallable($validate);

        $rows = [
            ['Pc(s)', 'Pc(s)', 'entero', '0', '1'],
            ['Case', 'Case', 'entero', '0', '0'],
            ['LB', 'LB', 'decimal', '2', '0'],
        ];

        foreach ($rows as $cells) {
            $result = $validate($cells);
            $this->assertTrue($result['valid'], 'Row should be valid: ' . json_encode($cells));
            $this->assertSame([], $result['errors']);
        }
    }

    public function test_validate_rejects_missing_unit_and_description(): void
    {
        $validate = $this->schema['validate'];
        $result = $validate(['', '', 'entero', '0', '0']);

        $this->assertFalse($result['valid']);
        $this->assertContains('import_col_unit', $result['errors']);
        $this->assertContains('import_col_description', $result['errors']);
    }

    public function test_parse_boolean_for_default_column(): void
    {
        $this->assertTrue(ConfigCrudImportSchemas::parseBoolean('1'));
        $this->assertFalse(ConfigCrudImportSchemas::parseBoolean('0'));
    }

    public function test_fixture_xlsx_has_six_data_rows(): void
    {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $path = dirname(__DIR__) . '/fixtures/unitscsv-import-test.xlsx';
        $this->assertFileExists($path);

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);
        $this->assertGreaterThanOrEqual(7, count($rawRows));

        $dataRows = array_slice($rawRows, 1);
        $this->assertCount(6, $dataRows);
        $this->assertSame('Pc(s)', trim((string) $dataRows[0][0]));
        $this->assertSame('LB', trim((string) $dataRows[5][0]));
        $this->assertSame('decimal', trim((string) $dataRows[5][2]));
    }
}
