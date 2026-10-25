<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rsorder',
    properties: [
        new OA\Property(
            property: 'order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'amount_delivered',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_delivered',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'total_amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'total_delivered',
            type: 'string',
            nullable: true
        ),
    ]
)]
class RsOrderSchema {}

