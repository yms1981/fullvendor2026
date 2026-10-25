<?php

namespace Tests\Unit;

use App\Models\CacheLock;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CacheLockTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CacheLock::class;

    public function testModify()
    {
        /** @var CacheLock $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'owner');
        $this->assertModifiesNumberProperty($item,'expiration',fake()->randomDigitNot($item->expiration));
    }
}
