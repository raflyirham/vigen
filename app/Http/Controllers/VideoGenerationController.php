<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoGenerationRequest;
use App\Models\VideoGeneration;
use App\Services\GrokVideoService;
use App\Services\StoryboardComposer;
use App\Services\VideoAssetStorage;
use App\Services\VideoGenerationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VideoGenerationController extends Controller
{
    public function create(Request $request, GrokVideoService $grok): Response
    {
        return Inertia::render('Generator/Index', [
            'templates' => config('video_templates'),
            'recentGenerations' => $request->user()
                ->videoGenerations()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (VideoGeneration $generation) => $this->summary($generation)),
            'grokConfigured' => $grok->configured(),
        ]);
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->videoGenerations()->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('topic', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('target_audience', 'like', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->query('status'))) {
            $query->where('status', $status);
        }

        if ($type = trim((string) $request->query('video_type'))) {
            $query->where('video_type', $type);
        }

        return Inertia::render('History/Index', [
            'generations' => $query
                ->paginate(9)
                ->withQueryString()
                ->through(fn (VideoGeneration $generation) => $this->summary($generation)),
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
                'video_type' => $request->query('video_type', ''),
            ],
            'videoTypes' => $request->user()
                ->videoGenerations()
                ->select('video_type')
                ->distinct()
                ->orderBy('video_type')
                ->pluck('video_type'),
        ]);
    }

    public function store(
        StoreVideoGenerationRequest $request,
        StoryboardComposer $composer,
        VideoGenerationDispatcher $dispatcher,
    ): RedirectResponse {
        $input = $request->validated();
        $requestedDuration = (int) $input['duration'];
        $content = $composer->compose($input);

        $generation = $request->user()->videoGenerations()->create([
            'video_type' => $input['video_type'],
            'topic' => $input['topic'],
            'keywords' => $input['keywords'] ?? null,
            'target_audience' => $input['target_audience'],
            'tone' => $input['tone'],
            'aspect_ratio' => $input['aspect_ratio'],
            'requested_duration_seconds' => $requestedDuration,
            'effective_video_duration_seconds' => $requestedDuration,
            'template_key' => $input['template_key'] ?? null,
            'model' => config('grok.model', 'grok-imagine-1.0-video'),
            'status' => 'pending',
            'composed_prompt' => $content['prompt'],
            'script' => $content['script'],
            'storyboard' => $content['storyboard'],
        ]);

        $dispatcher->dispatch($generation);

        return redirect()
            ->route('generations.show', $generation)
            ->with('success', 'Generation queued.');
    }

    public function show(Request $request, VideoGeneration $generation): Response
    {
        $this->authorizeGeneration($request, $generation);

        return Inertia::render('Generations/Show', [
            'generation' => $this->detail($generation),
        ]);
    }

    public function status(Request $request, VideoGeneration $generation): JsonResponse
    {
        $this->authorizeGeneration($request, $generation);

        return response()->json([
            'generation' => $this->detail($generation->fresh()),
        ]);
    }

    public function destroy(Request $request, VideoGeneration $generation): RedirectResponse
    {
        $this->authorizeGeneration($request, $generation);
        $assetDisk = app(VideoAssetStorage::class)->disk();

        if ($generation->local_video_path) {
            Storage::disk($assetDisk)->delete($generation->local_video_path);
        }

        $generation->delete();

        return redirect()
            ->route('generations.index')
            ->with('success', 'Generation deleted.');
    }

    public function exportScript(Request $request, VideoGeneration $generation): HttpResponse
    {
        $this->authorizeGeneration($request, $generation);
        $fileName = Str::slug($generation->topic ?: 'video-script').'-script.txt';

        return response($generation->script, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function video(Request $request, VideoGeneration $generation, VideoAssetStorage $assets): HttpResponse
    {
        $this->authorizeGeneration($request, $generation);
        $assetDisk = $assets->disk();

        if (! $generation->local_video_path) {
            abort(404, 'No stored video is available for this generation.');
        }

        try {
            return Storage::disk($assetDisk)->response(
                $generation->local_video_path,
                $assets->assetFileName($generation),
            );
        } catch (\Throwable $exception) {
            Log::warning('Unable to stream stored video from asset disk.', [
                'generation_id' => $generation->id,
                'disk' => $assetDisk,
                'path' => $generation->local_video_path,
                'message' => $exception->getMessage(),
            ]);

            abort(404, 'No stored video is available for this generation.');
        }
    }

    public function downloadVideo(
        Request $request,
        VideoGeneration $generation,
        GrokVideoService $grok,
        VideoAssetStorage $assets,
    ): HttpResponse|RedirectResponse {
        $this->authorizeGeneration($request, $generation);
        $assetDisk = $assets->disk();

        if ($generation->local_video_path) {
            try {
                return Storage::disk($assetDisk)->download(
                    $generation->local_video_path,
                    $assets->assetFileName($generation),
                );
            } catch (\Throwable $exception) {
                Log::warning('Unable to download stored video from asset disk; falling back to source URL.', [
                    'generation_id' => $generation->id,
                    'disk' => $assetDisk,
                    'path' => $generation->local_video_path,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (! $generation->video_url) {
            abort(404, 'No video is available for this generation.');
        }

        $download = $grok->download($generation->video_url);

        if ($download === null) {
            return redirect()->away($generation->video_url);
        }

        $path = $assets->storeDownloadedAsset(
            $download,
            $generation->id,
            $generation->topic,
        );

        if ($path !== null) {
            $generation->update(['local_video_path' => $path]);

            return Storage::disk($assetDisk)->download(
                $path,
                $assets->assetFileName($generation->fresh()),
            );
        }

        return response($download['body'], 200, [
            'Content-Type' => $download['content_type'],
            'Content-Disposition' => 'attachment; filename="'
                .Str::slug($generation->topic ?: 'generated-video').'.'.$assets->extensionFor($download['content_type'])
                .'"',
        ]);
    }

    private function authorizeGeneration(Request $request, VideoGeneration $generation): void
    {
        abort_unless($generation->user_id === $request->user()->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(VideoGeneration $generation): array
    {
        return [
            'id' => $generation->id,
            'video_type' => $generation->video_type,
            'topic' => $generation->topic,
            'target_audience' => $generation->target_audience,
            'tone' => $generation->tone,
            'aspect_ratio' => $generation->aspect_ratio,
            'status' => $generation->status,
            'requested_duration_seconds' => $generation->requested_duration_seconds,
            'effective_video_duration_seconds' => $generation->effective_video_duration_seconds,
            'has_video' => filled($generation->video_url) || filled($generation->local_video_path),
            'created_at' => $generation->created_at?->toDayDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(VideoGeneration $generation): array
    {
        $assets = app(VideoAssetStorage::class);
        $previewUrl = $assets->previewUrl($generation);

        return [
            ...$this->summary($generation),
            'keywords' => $generation->keywords,
            'template_key' => $generation->template_key,
            'model' => $generation->model,
            'composed_prompt' => $generation->composed_prompt,
            'script' => $generation->script,
            'storyboard' => $generation->storyboard,
            'video_url' => $generation->video_url,
            'preview_url' => $previewUrl ?? ($generation->local_video_path
                ? route('generations.video', $generation)
                : $generation->video_url),
            'error_message' => $generation->error_message,
        ];
    }
}
