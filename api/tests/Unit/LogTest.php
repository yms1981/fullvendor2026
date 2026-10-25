<?php

namespace Tests\Unit;

use App\Models\Log;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class LogTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Log::class;

    public function testModify()
    {
        /** @var Log $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'log_type');
        $this->assertModifiesStringProperty($item, 'notes');
    }
}
