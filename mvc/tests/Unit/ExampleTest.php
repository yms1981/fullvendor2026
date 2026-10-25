<?php
/**
 * Test de ejemplo para verificar que PHPUnit está activo en el MVC.
 */
class ExampleTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_constants_defined(): void
    {
        $this->assertTrue(defined('FCPATH'));
        $this->assertTrue(defined('BASEPATH'));
    }
}
