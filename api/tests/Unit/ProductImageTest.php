<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductImage;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;
use Tests\ModelWithRelations;

class ProductImageTest extends ModelBaseTestCase implements ModelWithModificableProperties, ModelWithRelations
{
    protected $model = ProductImage::class;

    public function testModify()
    {
        /** @var ProductImage $item */
        $item = $this->createModelInstance();

        $this->assertModifiesNumberProperty($item, 'img_order', fake()->randomDigitNot($item->img_order));
    }

    public function testRelationships()
    {
        /** @var ProductImage $item */
        $item = $this->createModelInstance();
        $this->assertBelongsTo($item->product(), Product::class);

    }
}


