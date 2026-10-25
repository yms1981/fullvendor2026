<?php

namespace Tests\Unit;

use App\Models\Session;
use Illuminate\Support\Carbon;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class SessionTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Session::class;

    public function testModify()
    {
        /** @var Session $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'ip_address',fake()->ipv4);
        $this->assertModifiesStringProperty($item, 'user_agent');
        $this->assertModifiesStringProperty($item, 'payload');

        $this->assertModifiesNumberProperty($item, 'last_activity',Carbon::now()->getTimestamp());
    }
}
