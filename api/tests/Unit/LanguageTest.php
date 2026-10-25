<?php

namespace Tests\Unit;

use App\Models\Language;
use Illuminate\Support\Str;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class LanguageTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Language::class;

    public function testModify()
    {
        /** @var Language $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name',Str::substr(fake()->name,0,49));
        $this->assertModifiesStringProperty($item, 'iso_639_1',fake()->languageCode());
        $this->assertModifiesStringProperty($item, 'type',Str::substr(fake()->name,0,50));

        $this->assertModifiesNumberProperty($item, 'status', fake()->randomDigitNot($item->status));
        $this->assertModifiesNumberProperty($item, 'default', fake()->randomDigitNot($item->default));
    }
}
