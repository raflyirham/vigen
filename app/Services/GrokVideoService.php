<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class GrokVideoService
{
    private const MEDIA_POST_API = 'https://grok.com/rest/media/post/create';

    private const CHAT_API = 'https://grok.com/rest/app-chat/conversations/new';

    private const MIN_VIDEO_SECONDS = 1;

    private const MAX_VIDEO_SECONDS = 10;

    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array{video_url: string, raw_response: array<string, mixed>}
     */
    public function generate(string $prompt, int $seconds, ?string $aspectRatio = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Grok credentials are not configured.');
        }

        $seconds = $this->normalizeVideoLength($seconds);
        $postId = $this->createVideoPost($prompt);
        $response = $this->request()
            ->timeout((int) config('grok.request_timeout', 360))
            ->post(self::CHAT_API, $this->chatBody($prompt, $postId, $seconds, $aspectRatio));

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage('Grok video request failed', $response->status(), $response->body()));
        }

        $rawBody = $response->body();
        $videoUrl = $this->extractVideoUrl($rawBody);

        if ($videoUrl === null) {
            throw new RuntimeException('Grok video response did not include a video URL.');
        }

        return [
            'video_url' => $videoUrl,
            'raw_response' => [
                'post_id' => $postId,
                'response_excerpt' => Str::limit($rawBody, 4000),
            ],
        ];
    }

    public function configured(): bool
    {
        return filled(config('grok.sso_token'));
    }

    /**
     * @return array{body: string, content_type: string}|null
     */
    public function download(string $url): ?array
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        $response = $this->request()
            ->timeout(120)
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type', 'video/mp4'),
        ];
    }

    private function createVideoPost(string $prompt): string
    {
        $response = $this->request()
            ->timeout(60)
            ->post(self::MEDIA_POST_API, [
                'mediaType' => 'MEDIA_POST_TYPE_VIDEO',
                'prompt' => $prompt,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage('Grok media post failed', $response->status(), $response->body()));
        }

        $postId = data_get($response->json(), 'post.id');

        if (! is_string($postId) || trim($postId) === '') {
            throw new RuntimeException('Grok media post response did not include a post ID.');
        }

        return $postId;
    }

    /**
     * @return array<string, mixed>
     */
    private function chatBody(string $prompt, string $postId, int $seconds, ?string $aspectRatio): array
    {
        return [
            'temporary' => false,
            'modelName' => 'grok-3',
            'message' => trim($prompt.' --mode='.config('grok.preset', 'normal')),
            'fileAttachments' => [],
            'imageAttachments' => [],
            'disableSearch' => false,
            'disableMemory' => false,
            'enableImageGeneration' => true,
            'returnImageBytes' => false,
            'returnRawGrokInXaiRequest' => false,
            'enableImageStreaming' => true,
            'imageGenerationCount' => 2,
            'forceConcise' => false,
            'toolOverrides' => ['videoGen' => true],
            'enableSideBySide' => true,
            'sendFinalMetadata' => true,
            'isReasoning' => false,
            'modelMode' => 'MODEL_MODE_FAST',
            'responseMetadata' => [
                'requestModelDetails' => [
                    'modelId' => 'grok-3',
                ],
                'modelConfigOverride' => [
                    'modelMap' => [
                        'videoGenModelConfig' => [
                            'aspectRatio' => $aspectRatio ?: config('grok.aspect_ratio', '16:9'),
                            'parentPostId' => $postId,
                            'resolutionName' => config('grok.resolution', '480p'),
                            'videoLength' => $seconds,
                        ],
                    ],
                ],
            ],
            'deviceEnvInfo' => [
                'darkModeEnabled' => false,
                'devicePixelRatio' => 2,
                'screenWidth' => 1440,
                'screenHeight' => 900,
                'viewportWidth' => 1440,
                'viewportHeight' => 820,
            ],
            'disableSelfHarmShortCircuit' => false,
            'disableTextFollowUps' => false,
            'forceSideBySide' => false,
            'isAsyncChat' => false,
        ];
    }

    private function normalizeVideoLength(int $seconds): int
    {
        return min(max($seconds, self::MIN_VIDEO_SECONDS), self::MAX_VIDEO_SECONDS);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Accept' => 'text/event-stream, application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
            'Cookie' => $this->cookie(),
            'Origin' => 'https://grok.com',
            'Pragma' => 'no-cache',
            'Referer' => 'https://grok.com/',
            'Sec-Ch-Ua' => '"Google Chrome";v="146", "Chromium";v="146", "Not(A:Brand";v="24"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"macOS"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => (string) config('grok.user_agent'),
            'X-Statsig-Id' => base64_encode("e:TypeError: Cannot read properties of undefined (reading 'children')"),
        ];
    }

    private function request(): PendingRequest
    {
        $request = $this->http->withHeaders($this->headers());
        $proxy = trim((string) config('grok.proxy_url'));

        return $proxy === ''
            ? $request
            : $request->withOptions(['proxy' => $proxy]);
    }

    private function errorMessage(string $message, int $status, string $body): string
    {
        $excerpt = trim(Str::limit(preg_replace('/\s+/', ' ', $body) ?? '', 500));

        return $excerpt === ''
            ? "{$message} with status {$status}."
            : "{$message} with status {$status}: {$excerpt}";
    }

    private function cookie(): string
    {
        $token = (string) config('grok.sso_token');
        $cookie = "sso={$token}; sso-rw={$token}";
        $extra = trim((string) config('grok.cf_cookies'));
        $clearance = trim((string) config('grok.cf_clearance'));

        if ($clearance !== '' && ! Str::contains($extra, 'cf_clearance=')) {
            $extra = trim($extra.'; cf_clearance='.$clearance, '; ');
        }

        return $extra === '' ? $cookie : $cookie.'; '.$extra;
    }

    private function extractVideoUrl(string $body): ?string
    {
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line === 'data: [DONE]') {
                continue;
            }

            if (Str::startsWith($line, 'data: ')) {
                $line = substr($line, 6);
            }

            $payload = json_decode($line, true);
            if (is_array($payload)) {
                $url = $this->findVideoUrl($payload);
                if ($url !== null) {
                    return $url;
                }
            }
        }

        if (preg_match('/https?:\/\/[^)\s"]+\.mp4[^)\s"]*/', $body, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function findVideoUrl(array $payload): ?string
    {
        $candidates = [
            'result.response.streamingVideoGenerationResponse.videoUrl',
            'result.response.streamingVideoGenerationResponse.videoURL',
            'streamingVideoGenerationResponse.videoUrl',
            'videoUrl',
            'videoURL',
            'mediaUrl',
        ];

        foreach ($candidates as $key) {
            $value = Arr::get($payload, $key);
            if (is_string($value) && $value !== '') {
                return $this->normalizeAssetUrl($value);
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $found = $this->findVideoUrl($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function normalizeAssetUrl(string $url): string
    {
        $url = trim($url);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return 'https://assets.grok.com/'.ltrim($url, '/');
    }
}
