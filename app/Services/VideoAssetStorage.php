<?php

namespace App\Services;

use App\Models\VideoGeneration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoAssetStorage
{
    public function storeFromUrl(
        GrokVideoService $grok,
        string $assetUrl,
        int $generationId,
        string $topic,
        ?string $prefix = null,
    ): ?string {
        $download = $grok->download($assetUrl);

        if ($download === null) {
            return null;
        }

        return $this->storeDownloadedAsset($download, $generationId, $topic, $prefix);
    }

    /**
     * @param  array{body: string, content_type: string}  $download
     */
    public function storeDownloadedAsset(array $download, int $generationId, string $topic, ?string $prefix = null): ?string
    {
        $extension = $this->extensionFor($download['content_type']);
        $path = 'generated-assets/'.now()->format('Y/m').'/'.$generationId.'-'
            .($prefix ? Str::slug($prefix).'-' : '')
            .Str::slug($topic ?: 'generated-video').'.'.$extension;

        return Storage::disk('local')->put($path, $download['body']) ? $path : null;
    }

    public function storeFinalVideo(string $absolutePath, int $generationId, string $topic): ?string
    {
        $path = 'generated-assets/'.now()->format('Y/m').'/'.$generationId.'-'.Str::slug($topic ?: 'generated-video').'.mp4';

        return Storage::disk('local')->put($path, file_get_contents($absolutePath)) ? $path : null;
    }

    public function extensionFor(string $contentType): string
    {
        $contentType = Str::of($contentType)->before(';')->lower()->trim()->value();

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'mp4',
        };
    }

    public function assetFileName(VideoGeneration $generation): string
    {
        $extension = pathinfo((string) $generation->local_video_path, PATHINFO_EXTENSION) ?: 'mp4';

        return Str::slug($generation->topic ?: 'generated-video').'.'.$extension;
    }
}
