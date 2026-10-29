<?php

return [

    // Fitur nonaktif sampai kebijakan pengiriman data ke provider AI disetujui.
    'enabled' => env('AI_ASSISTANT_ENABLED', false),

    'api_key' => env('AI_API_KEY'),

    'base_url' => env('AI_BASE_URL', 'https://api.openai.com'),

    'model' => env('AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('AI_TIMEOUT', 20),

    'max_prompt_length' => 1000,

    'max_output_tokens' => 600,

    'rate_limit' => env('AI_RATE_LIMIT', '20,1'),

];
