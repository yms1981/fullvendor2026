<?php

namespace Tests\Unit;

use App\Models\Key;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class KeyTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Key::class;

    public function testModify()
    {
        /** @var Key $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'ip_addresses', fake()->ipv4);
        $this->assertModifiesStringProperty($item, 'key');

        $this->assertModifiesNumberProperty($item, 'level', fake()->randomDigitNot($item->level));
        $this->assertModifiesNumberProperty($item, 'ignore_limits', !$item->ignore_limits);
        $this->assertModifiesNumberProperty($item, 'is_private_key', !$item->is_private_key);
    }
}
