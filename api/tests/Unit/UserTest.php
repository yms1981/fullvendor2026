<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class UserTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = User::class;

    public function testModify()
    {
        /** @var User $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'two_factor_secret');
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'password');
        $this->assertModifiesStringProperty($item, 'remember_token');
        $this->assertModifiesStringProperty($item, 'two_factor_recovery_codes');

        $this->assertModifiesDateProperty($item, 'two_factor_confirmed_at');
        $this->assertModifiesDateProperty($item, 'email_verified_at');
    }
}
