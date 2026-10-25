<?php

namespace Tests\Unit;

use App\Models\Setting;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class SettingTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Setting::class;

    public function testModify()
    {
        /** @var Setting $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'logo');
        $this->assertModifiesStringProperty($item, 'favicon');
        $this->assertModifiesStringProperty($item, 'title');
        $this->assertModifiesStringProperty($item, 'address');
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'phone');
        $this->assertModifiesStringProperty($item, 'website');
        $this->assertModifiesStringProperty($item, 'facebook');
        $this->assertModifiesStringProperty($item, 'twitter');
        $this->assertModifiesStringProperty($item, 'linkedin');
        $this->assertModifiesStringProperty($item, 'instagram');
    }
}
