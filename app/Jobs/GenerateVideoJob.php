<?php

namespace App\Jobs;

use App\Models\VideoGeneration;
use App\Services\GrokVideoService;
use App\Services\VideoAssetStorage;
use App\Services\VideoMergeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly int $generationId) {}

    public function handle(
        GrokVideoService $grok,
        VideoAssetStorage $assets,
        VideoMergeService $merger,
    ): void {
        $generation = VideoGeneration::find($this->generationId);

        if (! $generation) {
            Log::warning('Video generation job skipped because the generation record no longer exists.', [
                'generation_id' => $this->generationId,
            ]);

            return;
        }

        Log::info('Video generation job started.', [
            'generation_id' => $generation->id,
            'status' => $generation->status,
            'requested_duration_seconds' => $generation->requested_duration_seconds,
            'aspect_ratio' => $generation->aspect_ratio,
        ]);

        if (! $grok->configured()) {
            $generation->update([
                'status' => 'script_only',
                'error_message' => 'Grok credentials are not configured.',
            ]);

            Log::warning('Video generation switched to script-only because Grok is not configured.', [
                'generation_id' => $generation->id,
            ]);

            return;
        }

        $generation->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $segmentPaths = [];
        $segmentResponses = [];
        $segmentUrls = [];
        $mergedPath = null;

        try {
            foreach ($this->segmentPrompts($generation) as $index => $prompt) {
                Log::info('Generating video segment.', [
                    'generation_id' => $generation->id,
                    'segment' => $index + 1,
                    'segment_count' => $this->segmentCount($generation->requested_duration_seconds),
                ]);

                $result = $grok->generate($prompt, 10, $generation->aspect_ratio);
                $segmentUrls[] = $result['video_url'];
                $segmentResponses[] = $result['raw_response'];
                $path = $assets->storeFromUrl(
                    $grok,
                    $result['video_url'],
                    $generation->id,
                    $generation->topic,
                    'segment-'.($index + 1),
                );

                if ($path === null) {
                    throw new RuntimeException('Unable to download generated video segment '.($index + 1).'.');
                }

                $segmentPaths[] = $path;
            }

            $mergedPath = $merger->merge($segmentPaths, $generation->id, $assets->disk());
            $localVideoPath = $assets->storeFinalVideo($mergedPath, $generation->id, $generation->topic);

            if ($localVideoPath === null) {
                throw new RuntimeException('Unable to store merged video output.');
            }

            Storage::disk($assets->disk())->delete($segmentPaths);

            $generation->update([
                'status' => 'completed',
                'effective_video_duration_seconds' => $generation->requested_duration_seconds,
                'video_url' => $segmentUrls[0] ?? null,
                'local_video_path' => $localVideoPath,
                'raw_response' => [
                    'segments' => $segmentResponses,
                    'segment_video_urls' => $segmentUrls,
                ],
                'error_message' => null,
            ]);

            Log::info('Video generation completed.', [
                'generation_id' => $generation->id,
                'local_video_path' => $localVideoPath,
            ]);
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed_with_fallback',
                'error_message' => $exception->getMessage(),
                'raw_response' => [
                    'segments' => $segmentResponses,
                    'segment_video_urls' => $segmentUrls,
                ],
            ]);

            Log::error('Video generation failed.', [
                'generation_id' => $generation->id,
                'message' => $exception->getMessage(),
            ]);
        } finally {
            if (is_string($mergedPath) && $mergedPath !== '') {
                $workspace = dirname($mergedPath);
                $mergeRoot = storage_path('app/video-merge');

                if (str_starts_with(str_replace('\\', '/', $workspace), str_replace('\\', '/', $mergeRoot).'/')) {
                    File::deleteDirectory($workspace);
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function segmentPrompts(VideoGeneration $generation): array
    {
        $segmentCount = $this->segmentCount($generation->requested_duration_seconds);
        $storyboard = collect($generation->storyboard ?? []);

        return collect(range(1, $segmentCount))
            ->map(function (int $segment) use ($generation, $storyboard, $segmentCount): string {
                $start = ($segment - 1) * 10;
                $end = min($generation->requested_duration_seconds, $segment * 10);
                $scenes = $storyboard
                    ->filter(fn (array $scene): bool => $this->sceneOverlapsSegment((string) ($scene['timing'] ?? ''), $start, $end))
                    ->values();
                $sceneText = $scenes->isEmpty()
                    ? 'Use the overall storyboard direction.'
                    : $scenes->map(fn (array $scene): string => sprintf(
                        'Scene %s: %s %s %s',
                        $scene['scene'] ?? '?',
                        $scene['visual_direction'] ?? '',
                        $scene['narration'] ?? '',
                        $scene['on_screen_text'] ?? '',
                    ))->implode("\n");

                return trim(implode("\n", [
                    $generation->composed_prompt,
                    '',
                    "Generate segment {$segment} of {$segmentCount} only.",
                    "This clip covers seconds {$start}-{$end} of the final {$generation->requested_duration_seconds}-second video.",
                    "Aspect ratio: {$generation->aspect_ratio}.",
                    'Make this segment visually continuous with the full storyboard, with no title card unless the storyboard calls for one.',
                    $sceneText,
                ]));
            })
            ->all();
    }

    public function segmentCount(int $duration): int
    {
        return (int) ceil($duration / 10);
    }

    private function sceneOverlapsSegment(string $timing, int $segmentStart, int $segmentEnd): bool
    {
        if (! preg_match('/(\d+)s-(\d+)s/', $timing, $matches)) {
            return false;
        }

        $sceneStart = (int) $matches[1];
        $sceneEnd = (int) $matches[2];

        return $sceneStart < $segmentEnd && $sceneEnd > $segmentStart;
    }
}
