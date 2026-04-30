<?php

return [
    'sso_token' => env('GROK_SSO_TOKEN'),
    'cf_clearance' => env('GROK_CF_CLEARANCE'),
    'cf_cookies' => env('GROK_CF_COOKIES'),
    'proxy_url' => env('GROK_PROXY_URL'),
    'user_agent' => env('GROK_USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
    'request_timeout' => env('GROK_REQUEST_TIMEOUT', 360),
    'model' => env('GROK_VIDEO_MODEL', 'grok-imagine-1.0-video'),
    'resolution' => env('GROK_VIDEO_RESOLUTION', '480p'),
    'aspect_ratio' => env('GROK_VIDEO_ASPECT_RATIO', '16:9'),
    'preset' => env('GROK_VIDEO_PRESET', 'normal'),
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'queue_connection' => env('VIDEO_GENERATION_QUEUE_CONNECTION', 'database'),
    'queue_name' => env('VIDEO_GENERATION_QUEUE_NAME', 'default'),
    'auto_start_worker' => env('VIDEO_GENERATION_AUTO_START_WORKER', true),
];
