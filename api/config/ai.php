<?php

/**
 * Proveedores de IA (ChatGPT / Gemini / Replicate).
 * Las claves viven solo en .env — nunca en el front ni en commits.
 */
return [
    'openai' => [
        'api_key' => (string) env('OPENAI_API_KEY', ''),
        'chat_enabled' => filter_var(env('OPENAI_CHAT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'chat_model' => (string) env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'video_generation_enabled' => filter_var(env('OPENAI_VIDEO_GENERATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'video_model' => (string) env('OPENAI_VIDEO_MODEL', 'sora-2'),
        'tts_voice' => (string) env('OPENAI_TTS_VOICE', 'onyx'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        'base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'image_generation_enabled' => filter_var(env('OPENAI_IMAGE_GENERATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'image_model' => (string) env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
    ],

    'product_ai_image' => [
        'enabled' => filter_var(env('PRODUCT_AI_IMAGE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'max_per_request' => max(1, (int) env('PRODUCT_AI_IMAGE_MAX_PER_REQUEST', 3)),
        'openverse_enabled' => filter_var(env('PRODUCT_AI_IMAGE_OPENVERSE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'gemini' => [
        'api_key' => (string) env('GEMINI_API_KEY', ''),
        'image_enhancement_enabled' => filter_var(env('GEMINI_IMAGE_ENHANCEMENT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'image_model' => (string) env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash-image'),
        'video_generation_enabled' => filter_var(env('GEMINI_VIDEO_GENERATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'video_model' => (string) env('GEMINI_VIDEO_MODEL', 'gemini-omni-flash-preview'),
    ],

    'replicate' => [
        'api_token' => (string) env('REPLICATE_API_TOKEN', ''),
    ],
];
