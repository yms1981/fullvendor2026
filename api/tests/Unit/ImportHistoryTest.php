<?php

namespace Tests\Unit;

use App\Models\ImportHistory;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class ImportHistoryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = ImportHistory::class;

    public function testModify()
    {
        /** @var ImportHistory $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'importedfile');
        $this->assertModifiesDateProperty($item,'import_date');
        $this->assertModifiesNumberProperty($item,'records_imported', fake()->randomDigitNot($item->records_imported));

    }
}
