<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderflowtransitionRequest',
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_flow_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'origin_status_order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'destination_status_order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class OrderFlowTransitionRequestSchema {}

