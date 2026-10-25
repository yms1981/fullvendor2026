<?php

namespace Tests\Unit\View;

use App\Models\CartImage;
use App\Models\Product;
use App\Models\ProductImage;
use Tests\ViewModelTestCase;

class CartImageTest extends ViewModelTestCase
{
    protected $model = CartImage::class;

    protected function addNeededModelItems()
    {
        Product::factory()->count(5)->create();
        Product::all()->each(function (Product $product) {
            ProductImage::factory()->count(rand(1, 10))->create(['product_id'=>$product->pro_id]);
        });

    }
}
