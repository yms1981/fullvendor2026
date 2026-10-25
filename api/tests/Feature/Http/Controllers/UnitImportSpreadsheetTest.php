<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Unit;
use Tests\Feature\Http\ResourceFeaturesTestCase;

/**
 * Payloads equivalentes a tests/fixtures/unitscsv-import-test.xlsx.
 */
class UnitImportSpreadsheetTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'unit';

    public function test_spreadsheet_row_payloads_can_be_stored(): void
    {
        $suffix = '-import-' . uniqid('', false);
        $rows = [
            ['unit' => 'Pc(s)' . $suffix, 'description' => 'Pc(s)', 'formato' => 'entero', 'decimales' => 0, 'default' => true],
            ['unit' => 'Case' . $suffix, 'description' => 'Case', 'formato' => 'entero', 'decimales' => 0, 'default' => false],
            ['unit' => 'LB' . $suffix, 'description' => 'LB', 'formato' => 'decimal', 'decimales' => 2, 'default' => false],
        ];

        /** @var Unit $first */
        $first = $this->createResourceModelItems(1, $rows[0]);
        $this->assertDatabaseHas('units', ['unit_id' => $first->unit_id, 'unit' => $rows[0]['unit']]);

        $companyId = (int) $first->company_id;
        foreach (array_slice($rows, 1) as $row) {
            $created = $this->createResourceModelItems(1, array_merge($row, ['company_id' => $companyId]));
            $this->assertInstanceOf(Unit::class, $created);
        }
    }

    public function test_duplicate_unit_name_is_rejected_on_store(): void
    {
        $name = 'DupUnit' . uniqid('', false);
        /** @var Unit $existing */
        $existing = $this->createResourceModelItems(1, [
            'unit' => $name,
            'description' => $name,
            'formato' => 'entero',
            'decimales' => 0,
            'default' => false,
        ]);

        $response = $this->postJson($this->getResourceUri(), [
            'company_id' => $existing->company_id,
            'unit' => $name,
            'description' => $name,
            'formato' => 'entero',
            'decimales' => 0,
            'default' => false,
        ]);

        $response->assertStatus(422);
    }
}
