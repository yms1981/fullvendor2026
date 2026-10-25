<?php

namespace Tests\Unit;

use App\Models\CatalogCart;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CatalogCartTest extends OrderTest
{
    protected $model = CatalogCart::class;

}
