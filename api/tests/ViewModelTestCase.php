<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection;

abstract class ViewModelTestCase extends TestCase
{
    /**
     * Should add model that allows to test fetching the view to test its logic
     * @return mixed
     */
    abstract protected function addNeededModelItems();

    public function testFetch()
    {
        $this->addNeededModelItems();
        $items = $this->model::all();
        $this->assertNotNull($items);
        $this->assertInstanceOf(Collection::class,$items);
        $items->each(function ($item) {
            $this->assertNotNull($item);
            $this->assertInstanceOf($this->model, $item);
        });

    }

    public function test_is_view() {
        $this->assertTrue(true);
    }
}
