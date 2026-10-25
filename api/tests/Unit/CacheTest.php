<?php

namespace Tests\Unit;

use App\Models\Cache;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CacheTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Cache::class;

    public function testModify()
    {
        /** @var Cache $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'value');
        $this->assertModifiesNumberProperty($item,'expiration',fake()->randomDigitNot($item->expiration));
    }
}
