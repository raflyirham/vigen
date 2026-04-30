<?php

namespace Tests\Feature;

use App\Jobs\GenerateVideoJob;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\GrokVideoService;
use App\Services\VideoAssetStorage;
use App\Services\VideoGenerationDispatcher;
use App\Services\VideoMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class VideoGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_generator(): void
    {
        $this->get(route('generator.create'))
            ->assertRedirect(route('login'));
    }

    public function test_generation_request_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('generations.store'), [
                'video_type' => '',
                'topic' => '',
                'target_audience' => '',
                'tone' => '',
                'duration' => 45,
            ])
            ->assertSessionHasErrors([
                'video_type',
                'topic',
                'target_audience',
                'tone',
                'aspect_ratio',
                'duration',
            ]);
    }

    public function test_generation_request_accepts_supported_aspect_ratios(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        foreach (['2:3', '3:2', '1:1', '9:16', '16:9'] as $ratio) {
            $this->actingAs($user)
                ->post(route('generations.store'), $this->payload(['aspect_ratio' => $ratio]))
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $this->assertDatabaseHas('video_generations', ['aspect_ratio' => $ratio]);
        }
    }

    public function test_generation_falls_back_to_script_when_grok_is_not_configured(): void
    {
        config(['grok.sso_token' => null]);
        $user = User::factory()->create();
        $generation = $this->generationFor($user, ['status' => 'pending']);

        $mock = Mockery::mock(GrokVideoService::class);
        $mock->shouldReceive('configured')->once()->andReturn(false);

        (new GenerateVideoJob($generation->id))->handle(
            $mock,
            app(VideoAssetStorage::class),
            Mockery::mock(VideoMergeService::class),
        );

        $generation->refresh();
        $this->assertSame('script_only', $generation->status);
        $this->assertNotEmpty($generation->script);
    }

    public function test_generation_request_is_queued_without_waiting_for_video(): void
    {
        $user = User::factory()->create();
        $dispatcher = Mockery::mock(VideoGenerationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn (VideoGeneration $generation): bool => $generation->topic === 'AI scheduling assistant for clinics'));
        $this->app->instance(VideoGenerationDispatcher::class, $dispatcher);

        $this->actingAs($user)
            ->post(route('generations.store'), $this->payload([
                'duration' => 60,
                'aspect_ratio' => '9:16',
            ]))
            ->assertRedirect();

        $generation = VideoGeneration::firstOrFail();

        $this->assertSame('pending', $generation->status);
        $this->assertSame(60, $generation->requested_duration_seconds);
        $this->assertSame(60, $generation->effective_video_duration_seconds);
        $this->assertSame('9:16', $generation->aspect_ratio);
        $this->assertCount(8, $generation->storyboard);
    }

    public function test_generation_job_saves_successful_merged_video(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $generation = $this->generationFor($user, [
            'status' => 'pending',
            'requested_duration_seconds' => 30,
            'effective_video_duration_seconds' => 30,
            'aspect_ratio' => '2:3',
        ]);
        $mock = Mockery::mock(GrokVideoService::class);
        $mock->shouldReceive('configured')->once()->andReturn(true);
        $mock->shouldReceive('generate')
            ->times(3)
            ->with(Mockery::type('string'), 10, '2:3')
            ->andReturnUsing(function (string $prompt, int $seconds, string $aspectRatio) {
                static $index = 0;
                $index++;

                return [
                    'video_url' => "https://assets.grok.com/generated/video-{$index}.mp4",
                    'raw_response' => ['post_id' => "post_{$index}"],
                ];
            });
        $mock->shouldReceive('download')
            ->times(3)
            ->andReturnUsing(fn (string $url): array => [
                'body' => 'video-bytes-'.$url,
                'content_type' => 'video/mp4',
            ]);
        $merger = Mockery::mock(VideoMergeService::class);
        $merger->shouldReceive('merge')
            ->once()
            ->with(Mockery::on(fn (array $paths): bool => count($paths) === 3), $generation->id)
            ->andReturnUsing(function () {
                $path = storage_path('app/test-merged.mp4');
                file_put_contents($path, 'merged-video-bytes');

                return $path;
            });

        (new GenerateVideoJob($generation->id))->handle($mock, app(VideoAssetStorage::class), $merger);

        $generation->refresh();
        $this->assertSame('completed', $generation->status);
        $this->assertSame(30, $generation->effective_video_duration_seconds);
        $this->assertSame('https://assets.grok.com/generated/video-1.mp4', $generation->video_url);
        $this->assertNotNull($generation->local_video_path);
        Storage::disk('local')->assertExists($generation->local_video_path);
        $this->assertSame('merged-video-bytes', Storage::disk('local')->get($generation->local_video_path));
        $this->assertCount(3, $generation->raw_response['segments']);
    }

    public function test_grok_payload_includes_selected_aspect_ratio(): void
    {
        config(['grok.sso_token' => 'token']);
        Http::fake([
            'https://grok.com/rest/media/post/create' => Http::response(['post' => ['id' => 'post_123']]),
            'https://grok.com/rest/app-chat/conversations/new' => Http::response(
                'data: '.json_encode(['videoUrl' => 'https://assets.grok.com/generated/video.mp4'])."\n",
                200,
                ['Content-Type' => 'text/event-stream'],
            ),
        ]);

        app(GrokVideoService::class)->generate('Prompt', 10, '9:16');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://grok.com/rest/app-chat/conversations/new') {
                return false;
            }

            return data_get($request->data(), 'responseMetadata.modelConfigOverride.modelMap.videoGenModelConfig.aspectRatio') === '9:16';
        });
    }

    public function test_generation_job_uses_six_segments_for_sixty_seconds(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $generation = $this->generationFor($user, [
            'status' => 'pending',
            'requested_duration_seconds' => 60,
            'effective_video_duration_seconds' => 60,
            'aspect_ratio' => '16:9',
            'storyboard' => array_map(fn (int $scene): array => [
                'scene' => $scene,
                'purpose' => 'Build',
                'timing' => sprintf('%02ds-%02ds', ($scene - 1) * 8, $scene * 8),
                'visual_direction' => 'Visual',
                'narration' => 'Narration',
                'on_screen_text' => 'Caption',
            ], range(1, 8)),
        ]);
        $mock = Mockery::mock(GrokVideoService::class);
        $mock->shouldReceive('configured')->once()->andReturn(true);
        $mock->shouldReceive('generate')
            ->times(6)
            ->with(Mockery::type('string'), 10, '16:9')
            ->andReturn([
                'video_url' => 'https://assets.grok.com/generated/video.mp4',
                'raw_response' => ['post_id' => 'post'],
            ]);
        $mock->shouldReceive('download')
            ->times(6)
            ->andReturn(['body' => 'video-bytes', 'content_type' => 'video/mp4']);
        $merger = Mockery::mock(VideoMergeService::class);
        $merger->shouldReceive('merge')
            ->once()
            ->with(Mockery::on(fn (array $paths): bool => count($paths) === 6), $generation->id)
            ->andReturnUsing(function () {
                $path = storage_path('app/test-merged-60.mp4');
                file_put_contents($path, 'merged-video-bytes');

                return $path;
            });

        (new GenerateVideoJob($generation->id))->handle($mock, app(VideoAssetStorage::class), $merger);

        $generation->refresh();
        $this->assertSame('completed', $generation->status);
    }

    public function test_upstream_error_is_saved_with_fallback_output(): void
    {
        $user = User::factory()->create();
        $generation = $this->generationFor($user, ['status' => 'pending']);
        $mock = Mockery::mock(GrokVideoService::class);
        $mock->shouldReceive('configured')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new RuntimeException('upstream unavailable'));

        (new GenerateVideoJob($generation->id))->handle(
            $mock,
            app(VideoAssetStorage::class),
            Mockery::mock(VideoMergeService::class),
        );

        $generation->refresh();
        $this->assertSame('failed_with_fallback', $generation->status);
        $this->assertSame('upstream unavailable', $generation->error_message);
        $this->assertNotEmpty($generation->script);
    }

    public function test_users_cannot_view_status_for_another_users_generation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $generation = $this->generationFor($owner);

        $this->actingAs($otherUser)
            ->getJson(route('generations.status', $generation))
            ->assertNotFound();
    }

    public function test_users_cannot_view_or_delete_another_users_generation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $generation = $this->generationFor($owner);

        $this->actingAs($otherUser)
            ->get(route('generations.show', $generation))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('generations.destroy', $generation))
            ->assertNotFound();

        $this->assertDatabaseHas('video_generations', ['id' => $generation->id]);
    }

    public function test_script_can_be_exported_as_text_file(): void
    {
        $user = User::factory()->create();
        $generation = $this->generationFor($user, ['script' => 'Scene 1: Hello']);

        $this->actingAs($user)
            ->get(route('generations.export-script', $generation))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Scene 1: Hello', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'video_type' => 'marketing video',
            'topic' => 'AI scheduling assistant for clinics',
            'keywords' => 'automation, patients, reminders',
            'target_audience' => 'clinic owners',
            'tone' => 'persuasive',
            'aspect_ratio' => '16:9',
            'duration' => 30,
            'template_key' => 'marketing',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function generationFor(User $user, array $overrides = []): VideoGeneration
    {
        return VideoGeneration::create([
            'user_id' => $user->id,
            'video_type' => 'marketing video',
            'topic' => 'Clinic growth',
            'target_audience' => 'clinic owners',
            'tone' => 'persuasive',
            'aspect_ratio' => '16:9',
            'requested_duration_seconds' => 30,
            'effective_video_duration_seconds' => 30,
            'status' => 'script_only',
            'composed_prompt' => 'Prompt',
            'script' => 'Script',
            'storyboard' => [
                [
                    'scene' => 1,
                    'purpose' => 'Hook',
                    'timing' => '00s-06s',
                    'visual_direction' => 'Open',
                    'narration' => 'Narration',
                    'on_screen_text' => 'Caption',
                ],
            ],
            ...$overrides,
        ]);
    }
}
