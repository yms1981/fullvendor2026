<?php

namespace App\Http\Controllers;

use App\Services\OpenAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

#[OA\Tag(name: 'Assistant', description: 'Asistente ChatGPT del panel company')]
class AssistantChatController extends Controller
{
    public function __construct(
        private readonly OpenAiChatService $openAiChat
    ) {
    }

    #[OA\Get(
        path: '/api/v2/assistant/status',
        summary: 'Estado del asistente ChatGPT',
        tags: ['Assistant'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'OK')]
    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => $this->openAiChat->isConfigured(),
            'provider' => 'openai',
            'model' => config('ai.openai.chat_model'),
            'tts_voice' => config('ai.openai.tts_voice'),
            'features' => [
                'chat' => $this->openAiChat->isConfigured(),
                'openai_video' => (bool) config('ai.openai.video_generation_enabled')
                    && trim((string) config('ai.openai.api_key')) !== '',
                'gemini_image' => (bool) config('ai.gemini.image_enhancement_enabled')
                    && trim((string) config('ai.gemini.api_key')) !== '',
                'gemini_video' => (bool) config('ai.gemini.video_generation_enabled')
                    && trim((string) config('ai.gemini.api_key')) !== '',
                'replicate' => trim((string) config('ai.replicate.api_token')) !== '',
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v2/assistant/chat',
        summary: 'Enviar mensaje al asistente ChatGPT',
        tags: ['Assistant'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(
                    property: 'history',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'role', type: 'string'),
                            new OA\Property(property: 'content', type: 'string'),
                        ],
                        type: 'object'
                    )
                ),
                new OA\Property(
                    property: 'context',
                    properties: [
                        new OA\Property(property: 'lang', type: 'string'),
                        new OA\Property(property: 'screen', type: 'string'),
                        new OA\Property(property: 'controller', type: 'string'),
                        new OA\Property(property: 'method', type: 'string'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Respuesta del asistente')]
    #[OA\Response(response: 422, description: 'Validación')]
    #[OA\Response(response: 503, description: 'IA no configurada')]
    public function chat(Request $request): JsonResponse
    {
        if (! $this->openAiChat->isConfigured()) {
            return response()->json([
                'ok' => false,
                'enabled' => false,
                'code' => 'openai_not_configured',
                'message' => 'ChatGPT is not configured',
            ], 503);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['sometimes', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'context' => ['sometimes', 'array'],
            'context.lang' => ['sometimes', 'nullable', 'string', 'max:16'],
            'context.screen' => ['sometimes', 'nullable', 'string', 'max:255'],
            'context.controller' => ['sometimes', 'nullable', 'string', 'max:120'],
            'context.method' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $history = is_array($data['history'] ?? null) ? $data['history'] : [];
        $messages = array_values(array_merge($history, [
            ['role' => 'user', 'content' => trim((string) $data['message'])],
        ]));
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];

        try {
            $reply = $this->openAiChat->chat($messages, $context);
        } catch (RuntimeException $e) {
            $code = $e->getMessage();
            $status = $code === 'openai_not_configured' ? 503 : 502;

            return response()->json([
                'ok' => false,
                'enabled' => $code !== 'openai_not_configured',
                'code' => $code,
                'message' => 'Assistant request failed',
            ], $status);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'enabled' => true,
                'code' => 'openai_unexpected_error',
                'message' => 'Assistant request failed',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'enabled' => true,
            'reply' => $reply,
            'provider' => 'openai',
            'model' => config('ai.openai.chat_model'),
        ]);
    }
}
