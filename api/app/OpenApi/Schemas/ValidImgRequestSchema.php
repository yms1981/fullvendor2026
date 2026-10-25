<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidimgRequest',
    properties: [
        new OA\Property(
            property: 'pro_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'img_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'product_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'language_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'images',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'updated',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'Img',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sImgURL',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'ImgURL',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'id_kor',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'Description',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'sku',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'img_default',
            type: 'string',
            nullable: true
        ),
    ]
)]
class ValidImgRequestSchema {}

