<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompanyChatContact',
    type: 'object',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1323),
        new OA\Property(property: 'name', type: 'string', example: 'Seller Demo'),
        new OA\Property(property: 'email', type: 'string', example: 'seller@fullvendor.com'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['seller', 'warehouse', 'manager', 'customer'])
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatParticipant',
    type: 'object',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'role_type', type: 'string', enum: ['seller', 'warehouse', 'manager', 'customer']),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
        new OA\Property(property: 'last_read_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatAttachment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'image/png'),
        new OA\Property(property: 'original_name', type: 'string', nullable: true),
        new OA\Property(property: 'size_bytes', type: 'integer'),
        new OA\Property(property: 'duration_seconds', type: 'number', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatMessage',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'conversation_id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'message_type', type: 'string', enum: ['text', 'image', 'audio']),
        new OA\Property(property: 'body', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'read_status', type: 'string', enum: ['sent', 'read'], nullable: true, description: 'Solo en mensajes propios'),
        new OA\Property(
            property: 'author',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
                new OA\Property(property: 'email', type: 'string', nullable: true),
                new OA\Property(property: 'image', type: 'string', nullable: true),
                new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
            ]
        ),
        new OA\Property(
            property: 'attachments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CompanyChatAttachment')
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatConversation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'company_id', type: 'integer'),
        new OA\Property(property: 'subject', type: 'string', nullable: true),
        new OA\Property(property: 'last_message_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'unread_count', type: 'integer'),
        new OA\Property(
            property: 'participants',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CompanyChatParticipant')
        ),
        new OA\Property(
            property: 'others',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CompanyChatParticipant')
        ),
        new OA\Property(property: 'last_message', ref: '#/components/schemas/CompanyChatMessage', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatStoreConversationRequest',
    required: ['participant_user_ids'],
    properties: [
        new OA\Property(
            property: 'participant_user_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1323]
        ),
        new OA\Property(property: 'subject', type: 'string', nullable: true),
        new OA\Property(property: 'company_id', type: 'integer', example: 3),
    ]
)]
#[OA\Schema(
    schema: 'CompanyChatStoreMessageRequest',
    properties: [
        new OA\Property(property: 'body', type: 'string', nullable: true, description: 'Texto o caption. Obligatorio si no hay archivo.'),
        new OA\Property(property: 'message_type', type: 'string', enum: ['text', 'image', 'audio'], example: 'text'),
        new OA\Property(property: 'duration_seconds', type: 'number', nullable: true, description: 'Duración del audio en segundos'),
        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Foto o audio (multipart)'),
        new OA\Property(property: 'media', type: 'string', format: 'binary', description: 'Alias de file'),
        new OA\Property(property: 'company_id', type: 'integer'),
    ]
)]
class CompanyChatSchemas
{
}
