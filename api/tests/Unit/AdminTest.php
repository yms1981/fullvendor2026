<?php

namespace Tests\Unit;

use App\Models\Admin;
use Illuminate\Support\Facades\Schema;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class AdminTest extends ModelBaseTestCase implements ModelWithModificableProperties
{

    protected $model = Admin::class;

    protected function setUp(): void
    {
        parent::setUp();
        if(Schema::hasColumn('admin', 'name')) {
            \Artisan::call(
                'migrate',
                [
                    '--path' => 'database/migrations/structure',
                    '--force' => true
                ]);
        }
    }

    public function testModify()
    {
        /** @var Admin $item */
        $item = $this->createModelInstance();
        self::assertModifiesNumberProperty($item, 'status',!$item->status);

    }
}
