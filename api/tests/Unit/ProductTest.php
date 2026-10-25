<?php

namespace Tests\Unit;

use App\Models\Catalog;
use App\Models\Product;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;
use Tests\ModelWithRelations;

class ProductTest extends ModelBaseTestCase implements ModelWithModificableProperties, ModelWithRelations
{
    protected $model = Product::class;

    public function testRelationships()
    {
        /** @var Product $item */
        $item = $this->createModelInstance();
        $item->catalogs()->saveMany(Catalog::factory(4)->make());
        $this->assertBelongsToMany($item->catalogs(), Catalog::class);
    }


    public function testModify()
    {
        /** @var Product $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'sku');
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'category_id');
        $this->assertModifiesStringProperty($item, 'barcode');
        $this->assertModifiesStringProperty($item, 'barcode_images');
        $this->assertModifiesStringProperty($item, 'tags');
        $this->assertModifiesStringProperty($item, 'descriptions');
        $this->assertModifiesStringProperty($item, 'currency_type');
        $this->assertModifiesStringProperty($item, 'locations');
        $this->assertModifiesStringProperty($item, 'created_by');
        $this->assertModifiesStringProperty($item, 'Img');
        $this->assertModifiesStringProperty($item, 'imgURL');
        $this->assertModifiesStringProperty($item, 'simgURL');
        $this->assertModifiesStringProperty($item, 'cat_id');
        $this->assertModifiesStringProperty($item, 'cats_id');

        $this->assertModifiesNumberProperty($item, 'sem_amarillo_hasta', fake()->randomDigitNot($item->sem_amarillo_hasta));
        $this->assertModifiesNumberProperty($item, 'image_updated', fake()->randomDigitNot($item->image_updated));
        $this->assertModifiesNumberProperty($item, 'sem_amarillo_desde', fake()->randomDigitNot($item->sem_amarillo_desde));
        $this->assertModifiesNumberProperty($item, 'sem_red_hasta', fake()->randomDigitNot($item->sem_red_hasta));
        $this->assertModifiesNumberProperty($item, 'id_kor', fake()->randomDigitNot($item->id_kor));
        $this->assertModifiesNumberProperty($item, 'sem_verde_hasta', fake()->randomDigitNot($item->sem_verde_hasta));
        $this->assertModifiesNumberProperty($item, 'sell_with_inventory', fake()->randomDigitNot($item->sell_with_inventory));
        $this->assertModifiesNumberProperty($item, 'price_reduced', fake()->randomDigitNot($item->price_reduced));
        $this->assertModifiesNumberProperty($item, 'status', fake()->randomDigitNot($item->status));
        $this->assertModifiesNumberProperty($item, 'sem_verde_desde', fake()->randomDigitNot($item->sem_verde_hasta));
        $this->assertModifiesNumberProperty($item, 'notify_minimum_stock', fake()->randomDigitNot($item->notify_minimum_stock));
        $this->assertModifiesNumberProperty($item, 'product_id', fake()->randomDigitNot($item->product_id));

        $this->assertModifiesNumberProperty($item, 'sale_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'stock', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'minimum_stock', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'fob_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'purchase_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'price_reduced', fake()->randomFloat(config('app.money_decimal_precision')));
    }
}
