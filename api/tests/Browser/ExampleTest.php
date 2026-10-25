<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Smoke: panel MVC alcanzable vía Selenium (contenedor selenium + fullvendor).
 */
class ExampleTest extends DuskTestCase
{
    public function test_login_page_is_reachable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertPresent('input[name="username"], input[name="email"], #username, #email')
                ->assertPresent('input[name="password"], #password');
        });
    }
}
