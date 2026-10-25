<?php

namespace Tests\Unit;

use App\Models\StatusOrderFlow;
use PHPUnit\Framework\TestCase;
use Tests\ModelBaseTestCase;

class StatusOrderFlowTest extends ModelBaseTestCase
{
    protected $model = StatusOrderFlow::class;

    /**
     * @param StatusOrderFlow $item
     * @return array[]
     */
    protected function getIdCondArray($item)
    {
        $fk = $item->getForeignKey();
        $rk = $item->getRelatedKey();
        return [[$fk,$item->$fk],[$rk,$item->$rk]];
    }

}
