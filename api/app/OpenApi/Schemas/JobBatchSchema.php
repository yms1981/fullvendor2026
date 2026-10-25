<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Jobbatch',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'total_jobs',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'pending_jobs',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'failed_jobs',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'failed_job_ids',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'options',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cancelled_at',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'finished_at',
            type: 'string',
            nullable: true
        ),
    ]
)]
class JobBatchSchema {}

