<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Appupdate',
    properties: [
        new OA\Property(
            property: 'fecha',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'updatenumber',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'ID',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class AppUpdateSchema {}

