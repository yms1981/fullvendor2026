<?php

namespace App\Http\Controllers;

use App\Models\CompanyConversation;
use App\Models\CompanyConversationAttachment;
use App\Models\CompanyConversationMessage;
use App\Models\CompanyConversationParticipant;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Chat empresarial (Sellers / Warehouse / Managers / Customers): texto, fotos y audio.
 */
#[OA\Tag(name: 'CompanyChat', description: 'Chat empresarial entre Sellers, Warehouse, Managers y Customers')]
class CompanyChatController extends Controller
{
    private const CHAT_ROLES = ['seller', 'warehouse', 'manager', 'customer'];

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const AUDIO_MIMES = [
        'audio/webm',
        'audio/ogg',
        'audio/mpeg',
        'audio/mp4',
        'audio/wav',
        'audio/x-wav',
        'audio/aac',
        'video/webm',
    ];

    #[OA\Get(
        path: 'api/v2/company-chat/contacts',
        summary: 'Listar contactos de chat de la compañía (seller, warehouse, manager, customer)',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Contactos disponibles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CompanyChatContact')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin rol de chat en la compañía'),
        ]
    )]
    public function contacts(Request $request): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $rows = CompanyUser::query()
            ->where('company_id', $companyId)
            ->whereIn('type', self::CHAT_ROLES)
            ->where('user_id', '!=', $user->id)
            ->with(['user:id,name,email,image'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                /** @var CompanyUser $first */
                $first = $group->first();
                $u = $first->user;
                if (! $u) {
                    return null;
                }
                $roles = $group->pluck('type')->unique()->values()->all();

                return [
                    'user_id' => (int) $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'image' => $u->image,
                    'avatar_url' => $this->resolveAvatarUrl($u->image),
                    'roles' => $roles,
                ];
            })
            ->filter()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['data' => $rows]);
    }

    #[OA\Get(
        path: 'api/v2/company-chat/conversations',
        summary: 'Listar conversaciones del usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Conversaciones',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CompanyChatConversation')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin rol de chat en la compañía'),
        ]
    )]
    public function conversations(Request $request): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $conversations = CompanyConversation::query()
            ->where('company_id', $companyId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'participants.user:id,name,email,image',
                'messages' => fn ($q) => $q->latest('id')->limit(1)->with('attachments'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CompanyConversation $c) => $this->serializeConversation($c, $user));

        return response()->json(['data' => $conversations]);
    }

    #[OA\Post(
        path: 'api/v2/company-chat/conversations',
        summary: 'Crear conversación (o reutilizar DM 1:1 existente)',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CompanyChatStoreConversationRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Conversación creada o existente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/CompanyChatConversation'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación fallida'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin permiso'),
        ]
    )]
    public function storeConversation(Request $request): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'participant_user_ids' => 'required|array|min:1',
            'participant_user_ids.*' => 'integer|distinct',
            'subject' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $participantIds = collect($request->input('participant_user_ids'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $user->id)
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return response()->json(['message' => 'At least one other participant is required'], 422);
        }

        $roleByUser = $this->chatRolesForUsers($companyId, $participantIds->all());
        foreach ($participantIds as $pid) {
            if (! isset($roleByUser[$pid])) {
                return response()->json(['message' => 'Participant is not eligible for company chat', 'user_id' => $pid], 422);
            }
        }

        $myRole = $this->primaryChatRole($user, $companyId);
        if (! $myRole) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Reutilizar DM 1:1 existente (mismo conjunto de participantes).
        if ($participantIds->count() === 1) {
            $otherId = (int) $participantIds->first();
            $candidates = CompanyConversation::query()
                ->where('company_id', $companyId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $otherId))
                ->withCount('participants')
                ->with(['participants.user:id,name,email,image', 'messages' => fn ($q) => $q->latest('id')->limit(1)->with('attachments')])
                ->get();
            $existing = $candidates->first(fn (CompanyConversation $c) => (int) $c->participants_count === 2);
            if ($existing) {
                return response()->json(['data' => $this->serializeConversation($existing, $user)]);
            }
        }

        $conversation = DB::transaction(function () use ($companyId, $user, $myRole, $participantIds, $roleByUser, $request) {
            $conversation = CompanyConversation::query()->create([
                'company_id' => $companyId,
                'subject' => $request->input('subject'),
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            CompanyConversationParticipant::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role_type' => $myRole,
                'last_read_at' => now(),
            ]);

            foreach ($participantIds as $pid) {
                CompanyConversationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $pid,
                    'role_type' => $roleByUser[$pid],
                ]);
            }

            return $conversation;
        });

        $conversation->load([
            'participants.user:id,name,email,image',
            'messages' => fn ($q) => $q->latest('id')->limit(1)->with('attachments'),
        ]);

        return response()->json(['data' => $this->serializeConversation($conversation, $user)], 201);
    }

    #[OA\Get(
        path: 'api/v2/company-chat/conversations/{conversationId}/messages',
        summary: 'Listar mensajes de una conversación',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'after_id', in: 'query', required: false, description: 'Solo mensajes con id mayor (polling)', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Mensajes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CompanyChatMessage')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Conversación no encontrada'),
        ]
    )]
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $conversation = $this->findAccessibleConversation($conversationId, $companyId, $user->id);
        if (! $conversation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $afterId = (int) $request->input('after_id', 0);
        $limit = min(100, max(1, (int) $request->input('limit', 50)));

        $query = CompanyConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['author:id,name,email,image', 'attachments'])
            ->orderBy('id');

        $otherParticipants = CompanyConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->get(['user_id', 'last_read_at']);

        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        } else {
            // Últimos N, luego ordenar ASC para el cliente.
            $ids = (clone $query)->orderByDesc('id')->limit($limit)->pluck('id');
            $messages = CompanyConversationMessage::query()
                ->whereIn('id', $ids)
                ->with(['author:id,name,email,image', 'attachments'])
                ->orderBy('id')
                ->get();

            return response()->json([
                'data' => $messages->map(fn ($m) => $this->serializeMessage($m, $user, $otherParticipants))->values(),
            ]);
        }

        $messages = $query->limit($limit)->get();

        return response()->json([
            'data' => $messages->map(fn ($m) => $this->serializeMessage($m, $user, $otherParticipants))->values(),
        ]);
    }

    #[OA\Post(
        path: 'api/v2/company-chat/conversations/{conversationId}/messages',
        summary: 'Enviar mensaje de texto, foto o audio',
        description: 'JSON (texto) o multipart/form-data con campo file/media para imagen o audio.',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/CompanyChatStoreMessageRequest')
                ),
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(ref: '#/components/schemas/CompanyChatStoreMessageRequest')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Mensaje creado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/CompanyChatMessage'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación o tipo de media no soportado'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Conversación no encontrada'),
        ]
    )]
    public function storeMessage(Request $request, int $conversationId): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $conversation = $this->findAccessibleConversation($conversationId, $companyId, $user->id);
        if (! $conversation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $hasFile = $request->hasFile('file') || $request->hasFile('media');
        $file = $request->file('file') ?: $request->file('media');

        $validator = Validator::make($request->all(), [
            'body' => ($hasFile ? 'nullable' : 'required') . '|string|max:10000',
            'message_type' => 'nullable|in:text,image,audio',
            'duration_seconds' => 'nullable|numeric|min:0|max:3600',
            'file' => 'nullable|file|max:20480',
            'media' => 'nullable|file|max:20480',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $messageType = $request->input('message_type', 'text');
        if ($hasFile && $file) {
            $mime = (string) $file->getMimeType();
            if (in_array($mime, self::IMAGE_MIMES, true) || str_starts_with($mime, 'image/')) {
                $messageType = 'image';
            } elseif (in_array($mime, self::AUDIO_MIMES, true) || str_starts_with($mime, 'audio/')) {
                $messageType = 'audio';
            } else {
                return response()->json(['message' => 'Unsupported media type', 'mime' => $mime], 422);
            }
        }

        if ($messageType === 'text' && trim((string) $request->input('body')) === '') {
            return response()->json(['message' => 'Body required for text messages'], 422);
        }

        $chatNormalized = null;
        if ($hasFile && $file && $messageType === 'image') {
            $rawPreview = (string) file_get_contents($file->getRealPath());
            $chatNormalized = \App\Models\Image::normalizeBinary($rawPreview, 'chat');
            if ($chatNormalized === null && config('images.require_normalization', true)) {
                return response()->json(['message' => 'No se pudo normalizar la imagen adjunta'], 422);
            }
        }

        $message = DB::transaction(function () use ($conversation, $user, $request, $messageType, $hasFile, $file, $companyId, $chatNormalized) {
            $message = CompanyConversationMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'message_type' => $messageType,
                'body' => $request->input('body'),
            ]);

            if ($hasFile && $file) {
                $rawBytes = (string) file_get_contents($file->getRealPath());
                $mime = (string) $file->getMimeType();
                $ext = $file->getClientOriginalExtension() ?: ($messageType === 'image' ? 'jpg' : 'webm');
                $ext = Str::lower(preg_replace('/[^a-z0-9]/i', '', $ext) ?: 'bin');
                $storeBytes = $rawBytes;
                $storeMime = $mime;

                if ($messageType === 'image' && is_array($chatNormalized)) {
                    $storeBytes = $chatNormalized['bytes'];
                    $ext = $chatNormalized['extension'];
                    $storeMime = $chatNormalized['mime'];
                }

                $path = sprintf(
                    'chat/%d/%d/%s.%s',
                    $companyId,
                    $conversation->id,
                    (string) Str::uuid(),
                    $ext
                );
                $disk = \App\Models\Image::defaultDisk();
                $diskAdapter = Storage::disk($disk);
                if (\App\Models\Image::usesDirectoryLayout($disk)) {
                    $diskAdapter->makeDirectory(dirname($path));
                }
                $diskAdapter->put($path, $storeBytes, [
                    'visibility' => 'private',
                    'ContentType' => $storeMime,
                ]);

                CompanyConversationAttachment::query()->create([
                    'message_id' => $message->id,
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $storeMime,
                    'original_name' => $file->getClientOriginalName(),
                    'size_bytes' => strlen($storeBytes),
                    'duration_seconds' => $request->input('duration_seconds'),
                ]);
            }

            $conversation->last_message_at = now();
            $conversation->save();

            CompanyConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);

            return $message;
        });

        $message->load(['author:id,name,email,image', 'attachments']);

        $otherParticipants = CompanyConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->get(['user_id', 'last_read_at']);

        return response()->json(['data' => $this->serializeMessage($message, $user, $otherParticipants)], 201);
    }

    #[OA\Post(
        path: 'api/v2/company-chat/conversations/{conversationId}/read',
        summary: 'Marcar conversación como leída',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [new OA\Property(property: 'ok', type: 'boolean', example: true)]
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Conversación no encontrada'),
        ]
    )]
    public function markRead(Request $request, int $conversationId): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $conversation = $this->findAccessibleConversation($conversationId, $companyId, $user->id);
        if (! $conversation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        CompanyConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return response()->json(['data' => ['ok' => true]]);
    }

    #[OA\Get(
        path: 'api/v2/company-chat/unread-count',
        summary: 'Contadores de no leídos',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Contadores',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'unread_conversations', type: 'integer'),
                                new OA\Property(property: 'unread_messages', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        // Conteo por conversaciones con mensajes no leídos.
        $participants = CompanyConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereHas('conversation', fn ($q) => $q->where('company_id', $companyId))
            ->get(['conversation_id', 'last_read_at']);

        $unreadConversations = 0;
        $unreadMessages = 0;
        foreach ($participants as $p) {
            $q = CompanyConversationMessage::query()
                ->where('conversation_id', $p->conversation_id)
                ->where('user_id', '!=', $user->id);
            if ($p->last_read_at) {
                $q->where('created_at', '>', $p->last_read_at);
            }
            $n = $q->count();
            if ($n > 0) {
                $unreadConversations++;
                $unreadMessages += $n;
            }
        }

        return response()->json([
            'data' => [
                'unread_conversations' => $unreadConversations,
                'unread_messages' => $unreadMessages,
            ],
        ]);
    }

    #[OA\Get(
        path: 'api/v2/company-chat/attachments/{attachmentId}',
        summary: 'Descargar/stream de adjunto (foto o audio)',
        security: [['bearerAuth' => []]],
        tags: ['CompanyChat'],
        parameters: [
            new OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Contenido binario del archivo',
                content: [
                    new OA\MediaType(mediaType: 'image/*'),
                    new OA\MediaType(mediaType: 'audio/*'),
                    new OA\MediaType(mediaType: 'application/octet-stream'),
                ]
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Adjunto no encontrado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso'),
        ]
    )]
    public function attachment(Request $request, int $attachmentId): StreamedResponse|JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveChatUser($request);
        if ($error) {
            return $error;
        }

        $attachment = CompanyConversationAttachment::query()
            ->with('message.conversation')
            ->find($attachmentId);

        if (! $attachment || ! $attachment->message || ! $attachment->message->conversation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $conversation = $attachment->message->conversation;
        if ((int) $conversation->company_id !== $companyId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (! $this->findAccessibleConversation((int) $conversation->id, $companyId, $user->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $disk = $attachment->storage();
        if (! $disk->exists($attachment->path)) {
            return response()->json(['message' => 'File missing'], 404);
        }

        $stream = $disk->readStream($attachment->path);
        if ($stream === false) {
            return response()->json(['message' => 'Unable to read file'], 500);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . addslashes((string) ($attachment->original_name ?: 'media')) . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * @return array{0:?User,1:int,2:?JsonResponse}
     */
    private function resolveChatUser(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [null, 0, response()->json(['message' => 'Unauthenticated'], 401)];
        }

        $companyId = (int) ($request->input('company_id') ?? $request->header('X-Company-Id') ?? 0);
        if ($companyId <= 0) {
            return [null, 0, response()->json(['message' => 'company_id required'], 422)];
        }

        if (! $this->primaryChatRole($user, $companyId)) {
            return [null, 0, response()->json(['message' => 'Forbidden'], 403)];
        }

        return [$user, $companyId, null];
    }

    private function primaryChatRole(User $user, int $companyId): ?string
    {
        foreach (['manager', 'seller', 'warehouse', 'customer'] as $role) {
            $ok = match ($role) {
                'manager' => $user->isManager($companyId),
                'seller' => $user->isSeller($companyId),
                'warehouse' => $user->isWarehouse($companyId),
                'customer' => $user->isCustomer($companyId),
                default => false,
            };
            if ($ok) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @param list<int> $userIds
     * @return array<int,string>
     */
    private function chatRolesForUsers(int $companyId, array $userIds): array
    {
        $rows = CompanyUser::query()
            ->where('company_id', $companyId)
            ->whereIn('user_id', $userIds)
            ->whereIn('type', self::CHAT_ROLES)
            ->get(['user_id', 'type']);

        $map = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            // Preferir manager si tiene varios roles.
            if (! isset($map[$uid]) || $row->type === 'manager') {
                $map[$uid] = $row->type;
            }
        }

        return $map;
    }

    private function findAccessibleConversation(int $conversationId, int $companyId, int $userId): ?CompanyConversation
    {
        return CompanyConversation::query()
            ->where('id', $conversationId)
            ->where('company_id', $companyId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->first();
    }

    private function serializeConversation(CompanyConversation $c, User $viewer): array
    {
        $last = $c->messages->first();
        $my = $c->participants->firstWhere('user_id', $viewer->id);
        $others = $c->participants->filter(fn ($p) => (int) $p->user_id !== (int) $viewer->id)->values();

        $unread = 0;
        if ($last) {
            $q = CompanyConversationMessage::query()
                ->where('conversation_id', $c->id)
                ->where('user_id', '!=', $viewer->id);
            if ($my && $my->last_read_at) {
                $q->where('created_at', '>', $my->last_read_at);
            }
            $unread = $q->count();
        }

        return [
            'id' => (int) $c->id,
            'company_id' => (int) $c->company_id,
            'subject' => $c->subject,
            'last_message_at' => optional($c->last_message_at)?->toIso8601String(),
            'unread_count' => $unread,
            'participants' => $c->participants->map(fn ($p) => $this->serializeParticipant($p))->values(),
            'others' => $others->map(fn ($p) => $this->serializeParticipant($p))->values(),
            'last_message' => $last
                ? $this->serializeMessage(
                    $last,
                    $viewer,
                    $others->map(fn ($p) => (object) [
                        'user_id' => $p->user_id,
                        'last_read_at' => $p->last_read_at,
                    ])
                )
                : null,
        ];
    }

    private function serializeParticipant(CompanyConversationParticipant $p): array
    {
        return [
            'user_id' => (int) $p->user_id,
            'role_type' => $p->role_type,
            'name' => $p->user?->name,
            'email' => $p->user?->email,
            'image' => $p->user?->image,
            'avatar_url' => $this->resolveAvatarUrl($p->user?->image),
            'last_read_at' => optional($p->last_read_at)?->toIso8601String(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|null  $otherParticipants
     */
    private function serializeMessage(
        CompanyConversationMessage $m,
        ?User $viewer = null,
        $otherParticipants = null
    ): array {
        $readStatus = null;
        if ($viewer && (int) $m->user_id === (int) $viewer->id) {
            $others = $otherParticipants ?: collect();
            if ($others->isEmpty()) {
                $readStatus = 'sent';
            } else {
                $created = $m->created_at;
                $allRead = $others->every(function ($p) use ($created) {
                    $lastRead = $p->last_read_at ?? null;
                    if (! $lastRead) {
                        return false;
                    }
                    if ($lastRead instanceof \DateTimeInterface) {
                        return $created && $lastRead >= $created;
                    }

                    return $created && strtotime((string) $lastRead) >= $created->getTimestamp();
                });
                $readStatus = $allRead ? 'read' : 'sent';
            }
        }

        return [
            'id' => (int) $m->id,
            'conversation_id' => (int) $m->conversation_id,
            'user_id' => (int) $m->user_id,
            'message_type' => $m->message_type,
            'body' => $m->body,
            'created_at' => optional($m->created_at)?->toIso8601String(),
            'read_status' => $readStatus,
            'author' => [
                'id' => (int) ($m->author?->id ?? $m->user_id),
                'name' => $m->author?->name,
                'email' => $m->author?->email,
                'image' => $m->author?->image,
                'avatar_url' => $this->resolveAvatarUrl($m->author?->image),
            ],
            'attachments' => $m->attachments->map(fn (CompanyConversationAttachment $a) => [
                'id' => (int) $a->id,
                'mime_type' => $a->mime_type,
                'original_name' => $a->original_name,
                'size_bytes' => (int) $a->size_bytes,
                'duration_seconds' => $a->duration_seconds,
            ])->values(),
        ];
    }

    private function resolveAvatarUrl(?string $image): ?string
    {
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        // UUID o UUID.ext → endpoint público de imágenes (disco local / images_local).
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.[a-z0-9]+)?$/i', $image, $m)) {
            return '/api/v2/images/' . $m[1];
        }

        // Legacy: archivo plano en uploads/users/
        $file = ltrim($image, '/');
        if (str_starts_with($file, 'users/') || str_starts_with($file, 'user/')) {
            return '/uploads/' . $file;
        }

        return '/uploads/users/' . $file;
    }
}
