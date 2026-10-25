<?php

namespace Tests\Unit;

use App\Models\StatusOrder;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class StatusOrderTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = StatusOrder::class;

    public function testModify()
    {
        /** @var StatusOrder $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name_status_spanish');
        $this->assertModifiesStringProperty($item, 'color_icono');
        $this->assertModifiesStringProperty($item, 'name_status_english');
        $this->assertModifiesStringProperty($item, 'sombrilla');
        $this->assertModifiesStringProperty($item, 'tipo_d');
        $this->assertModifiesStringProperty($item, 'color');

        $this->assertModifiesNumberProperty($item, 'cod_status', !$item->cod_status);
        $this->assertModifiesNumberProperty($item, 'disabled', !$item->disabled);
        $this->assertModifiesNumberProperty($item, 'cannot_edit', !$item->cannot_edit);
        $this->assertModifiesNumberProperty($item, 'can_show_data_asigned', !$item->can_show_data_asigned);
        $this->assertModifiesNumberProperty($item, 'send_email', !$item->send_email);
    }
}
