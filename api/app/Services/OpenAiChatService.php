<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente de chat completions de OpenAI (ChatGPT) para el asistente company.
 */
class OpenAiChatService
{
    public function isConfigured(): bool
    {
        if (! filter_var(config('ai.openai.chat_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return trim((string) config('ai.openai.api_key', '')) !== '';
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array{lang?: string, screen?: string, controller?: string, method?: string}  $context
     */
    public function chat(array $messages, array $context = []): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('openai_not_configured');
        }

        $apiKey = trim((string) config('ai.openai.api_key'));
        $model = (string) config('ai.openai.chat_model', 'gpt-4o-mini');
        $timeout = max(10, (int) config('ai.openai.timeout', 60));
        $baseUrl = rtrim((string) config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');

        $payload = [
            'model' => $model,
            'temperature' => 0.4,
            'messages' => array_values(array_merge(
                [['role' => 'system', 'content' => $this->systemPrompt($context)]],
                $this->sanitizeMessages($messages)
            )),
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($timeout)
            ->post($baseUrl . '/chat/completions', $payload);

        if (! $response->successful()) {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorType = (string) data_get($response->json(), 'error.type', '');
            Log::warning('OpenAI chat failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->status() === 429 || $errorCode === 'credit_balance_exhausted' || $errorType === 'insufficient_quota') {
                throw new RuntimeException('openai_quota_exceeded');
            }

            throw new RuntimeException('openai_request_failed');
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        $content = is_string($content) ? trim($content) : '';

        if ($content === '') {
            throw new RuntimeException('openai_empty_response');
        }

        return $content;
    }

    /**
     * @param  array{lang?: string, screen?: string, controller?: string, method?: string}  $context
     */
    private function systemPrompt(array $context): string
    {
        $lang = strtolower((string) ($context['lang'] ?? 'en'));
        $screen = trim((string) ($context['screen'] ?? ''));
        $controller = trim((string) ($context['controller'] ?? ''));
        $method = trim((string) ($context['method'] ?? ''));

        if (str_starts_with($lang, 'es')) {
            $prompt = 'Eres el asistente de FullVendor, un ERP/B2B de catálogo, pedidos, clientes e inventario. '
                . 'Responde en español, de forma clara y breve. Ayuda con la interfaz y flujos del panel company. '
                . 'No inventes datos de negocio ni ejecutes acciones destructivas. '
                . 'Si no sabes algo concreto de la empresa, dilo y sugiere dónde mirar en el menú.';
        } else {
            $prompt = 'You are the FullVendor assistant for a B2B catalog/orders/customers/inventory ERP. '
                . 'Answer in English, clearly and briefly. Help with the company panel UI and workflows. '
                . 'Do not invent business data or suggest destructive actions. '
                . 'If you lack company-specific facts, say so and point to the relevant menu.';
        }

        if ($screen !== '' || $controller !== '') {
            $prompt .= ' Current screen context: screen=' . ($screen !== '' ? $screen : 'n/a')
                . '; controller=' . ($controller !== '' ? $controller : 'n/a')
                . '; method=' . ($method !== '' ? $method : 'n/a') . '.';
        }

        if ($screen === 'catalog/studio/featured') {
            if (str_starts_with($lang, 'es')) {
                $prompt .= ' Tarea especial: genera SOLO un mensaje destacado corto para el banner del catálogo (máximo 200 caracteres). '
                    . 'Sin comillas, sin markdown, sin explicaciones, sin saludo. Un solo párrafo comercial atractivo.';
            } else {
                $prompt .= ' Special task: return ONLY a short featured catalog banner message (max 200 characters). '
                    . 'No quotes, no markdown, no explanations, no greeting. One attractive commercial paragraph.';
            }
        }

        if ($screen === 'products/ai-image') {
            $prompt = 'You extract concise product photo search metadata. '
                . 'Reply with ONLY a JSON object: {"short_name":"...","search_query":"..."}. '
                . 'short_name is a short product title (max 8 words). '
                . 'search_query is English keywords (2-6 words) to find an exact or very similar product photo. '
                . 'No markdown fences, no extra text.';
        }

        return $prompt;
    }

    /**
     * @param  list<array{role?: mixed, content?: mixed}>  $messages
     * @return list<array{role: string, content: string}>
     */
    private function sanitizeMessages(array $messages): array
    {
        $out = [];
        foreach ($messages as $msg) {
            if (! is_array($msg)) {
                continue;
            }
            $role = strtolower(trim((string) ($msg['role'] ?? '')));
            $content = trim((string) ($msg['content'] ?? ''));
            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            if (mb_strlen($content) > 4000) {
                $content = mb_substr($content, 0, 4000);
            }
            $out[] = ['role' => $role, 'content' => $content];
            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }
}
