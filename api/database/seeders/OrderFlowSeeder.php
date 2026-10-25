<?php

namespace Database\Seeders;

use App\Enums\OrderStatusCodes;
use App\Models\OrderFlow;
use App\Models\OrderFlowTransition;
use App\Models\StatusOrder;
use App\Models\StatusOrderFlow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(OrderFlow::query()->count() > 0){
            return;
        }

        $defaultOrderFlow = new OrderFlow();
        $defaultOrderFlow->name = 'System Default';
        $defaultOrderFlow->save();

        //Add every status listed in OrderStatusCodes to the default order flow
        $statusCodes =OrderStatusCodes::all();
        collect($statusCodes)->each(function ($statusCode) use ($defaultOrderFlow) {
            $status = StatusOrder::where('cod_status', $statusCode)->sole();
            StatusOrderFlow::query()->create(
                ['status_order_id'=>$status->id_status,'order_flow_id'=>$defaultOrderFlow->id]
            );
        });

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::SHOPPING, OrderStatusCodes::PLACED);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::SHOPPING, OrderStatusCodes::APPROVED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::PLACED, OrderStatusCodes::PENDING);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::PLACED, OrderStatusCodes::REJECTED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::PENDING, OrderStatusCodes::APPROVED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::APPROVED, OrderStatusCodes::WAREHOUSE_ASSIGNED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::WAREHOUSE_ASSIGNED, OrderStatusCodes::APPROVED);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::WAREHOUSE_ASSIGNED, OrderStatusCodes::IN_PROCESS);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::WAREHOUSE_ASSIGNED, OrderStatusCodes::WAREHOUSE_RETURNED);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::WAREHOUSE_ASSIGNED, OrderStatusCodes::WAREHOUSE_COMPLETED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::WAREHOUSE_COMPLETED, OrderStatusCodes::COMPLETED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::COMPLETED, OrderStatusCodes::REJECTED);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::COMPLETED, OrderStatusCodes::DISPATCHED);

        $this->addTransition($defaultOrderFlow, OrderStatusCodes::DISPATCHED, OrderStatusCodes::DELIVERED);
        $this->addTransition($defaultOrderFlow, OrderStatusCodes::DISPATCHED, OrderStatusCodes::RETURNED);


    }

    /**
     * @param OrderFlow $defaultOrderFlow
     * @param OrderStatusCodes $originStatusCode
     * @param OrderStatusCodes $destinationStatusCode
     * @return void
     */
    protected function addTransition(OrderFlow $defaultOrderFlow, $originStatusCode, $destinationStatusCode): void
    {
        $transition = new OrderFlowTransition();
        $transition->order_flow_id = $defaultOrderFlow->id;
        $transition->name = 'Transition from ' . $originStatusCode->name . ' to ' . $destinationStatusCode->name;
        $transition->origin_status_order_id = StatusOrder::where('cod_status', $originStatusCode->value)->sole()->id_status;
        $transition->destination_status_order_id = StatusOrder::where('cod_status', $destinationStatusCode->value)->sole()->id_status;
        $transition->save();
    }
}
