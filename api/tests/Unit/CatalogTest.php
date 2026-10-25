<?php

namespace Tests\Unit;

use App\Models\Catalog;
use App\Models\Product;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;
use Tests\ModelWithRelations;

class CatalogTest extends ModelBaseTestCase implements ModelWithModificableProperties, ModelWithRelations
{
    protected $model = Catalog::class;

    public function testRelationships()
    {
        /** @var Catalog $item */
        $item = $this->createModelInstance();
        $item->products()->saveMany(Product::factory()->count(9)->make());
        $this->assertBelongsToMany($item->products(), Product::class);
    }

    public function testModify()
    {
        /** @var Catalog $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'catalog_name');

        $this->assertModifiesStringProperty($item,'cat_id');

        $this->assertModifiesStringProperty($item,'product_name');

    }
}
