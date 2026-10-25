<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Smoke E2E: importación de unidades con la plantilla de prueba (6 filas).
 *
 * Requiere credenciales de empresa en .env.dusk.local:
 *   DUSK_COMPANY_EMAIL=
 *   DUSK_COMPANY_PASSWORD=
 */
class UnitsImportTest extends DuskTestCase
{
    public function test_units_csv_import_preview_and_confirm(): void
    {
        $email = trim((string) env('DUSK_COMPANY_EMAIL', ''));
        $password = trim((string) env('DUSK_COMPANY_PASSWORD', ''));
        if ($email === '' || $password === '') {
            $this->markTestSkipped('Defina DUSK_COMPANY_EMAIL y DUSK_COMPANY_PASSWORD en .env.dusk.local');
        }

        $fixture = base_path('tests/fixtures/unitscsv-import-test.xlsx');
        if (! is_readable($fixture)) {
            $this->markTestSkipped('Falta tests/fixtures/unitscsv-import-test.xlsx');
        }

        $this->browse(function (Browser $browser) use ($email, $password, $fixture) {
            $browser->visit('/login')
                ->type('username', $email)
                ->type('password', $password)
                ->press('Login')
                ->waitForLocation('/company', 15)
                ->visit('/company/units/csvImport')
                ->attach('file', $fixture)
                ->waitFor('#form_import_confirm', 20)
                ->assertSee('Pc(s)')
                ->press('Confirm import')
                ->waitForText('Import', 5);
        });
    }
}
