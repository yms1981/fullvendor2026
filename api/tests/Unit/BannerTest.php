<?php

namespace Tests\Unit;

use App\Models\Banner;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class BannerTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Banner::class;

    public function testModify()
    {
        /** @var Banner $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'bannerHeading');
        $this->assertModifiesStringProperty($item, 'bannerDescription');
        $this->assertModifiesStringProperty($item, 'bannerImage');
    }
}
