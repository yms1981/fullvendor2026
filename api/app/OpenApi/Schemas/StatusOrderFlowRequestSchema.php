<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StatusorderflowRequest',
    properties: [
        new OA\Property(
            property: 'status_order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'order_flow_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class StatusOrderFlowRequestSchema {}

