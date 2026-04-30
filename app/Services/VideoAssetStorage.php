<?php

namespace App\Services;

use App\Models\VideoGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoAssetStorage
{
    public function disk(): string
    {
        return (string) config('grok.asset_disk', 'r2');
    }

    public function fallbackDisk(): ?string
    {
        $fallback = trim((string) config('grok.asset_fallback_disk', ''));

        return $fallback === '' ? null : $fallback;
    }

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

        return $this->storeContents($path, $download['body']) ? $path : null;
    }

    public function storeFinalVideo(string $absolutePath, int $generationId, string $topic): ?string
    {
        $path = 'generated-assets/'.now()->format('Y/m').'/'.$generationId.'-'.Str::slug($topic ?: 'generated-video').'.mp4';

        return $this->storeContents($path, file_get_contents($absolutePath)) ? $path : null;
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

    public function previewUrl(VideoGeneration $generation): ?string
    {
        if (! $generation->local_video_path) {
            return null;
        }

        $disk = $this->disk();
        $driver = (string) config("filesystems.disks.{$disk}.driver", '');

        try {
            if ($driver === 's3') {
                return Storage::disk($disk)->temporaryUrl(
                    $generation->local_video_path,
                    now()->addMinutes(30),
                );
            }

            return Storage::disk($disk)->url($generation->local_video_path);
        } catch (\Throwable $exception) {
            Log::warning('Unable to build preview URL from asset disk.', [
                'generation_id' => $generation->id,
                'disk' => $disk,
                'path' => $generation->local_video_path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function storeContents(string $path, string $contents): bool
    {
        $disk = $this->disk();

        try {
            if (Storage::disk($disk)->put($path, $contents)) {
                return true;
            }
        } catch (\Throwable $exception) {
            Log::warning('Primary asset disk write failed.', [
                'disk' => $disk,
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
        }

        $fallbackDisk = $this->fallbackDisk();

        if ($fallbackDisk === null || $fallbackDisk === $disk) {
            return false;
        }

        Log::warning('Falling back to configured asset fallback disk.', [
            'primary_disk' => $disk,
            'fallback_disk' => $fallbackDisk,
            'path' => $path,
        ]);

        return Storage::disk($fallbackDisk)->put($path, $contents);
    }
}
