<?php

namespace Tests\Feature;

use App\Services\VideoMergeService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoMergeServiceTest extends TestCase
{
    public function test_merge_preserves_audio_when_present(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('segments/one.mp4', 'segment-one');
        Storage::disk('local')->put('segments/two.mp4', 'segment-two');

        [$binary, $argumentsPath] = $this->fakeFfmpegBinary();
        config(['grok.ffmpeg_binary' => $binary]);

        $outputPath = app(VideoMergeService::class)->merge([
            'segments/one.mp4',
            'segments/two.mp4',
        ], 123);

        $arguments = json_decode(file_get_contents($argumentsPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFileExists($outputPath);
        $this->assertContains('0:a:0?', $arguments);
        $this->assertContains('-c:a', $arguments);
        $this->assertContains('aac', $arguments);
        $this->assertNotContains('-an', $arguments);
    }

    public function test_merge_stages_segments_from_configured_disk_before_running_ffmpeg(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('generated-assets/one.mp4', 'segment-one');
        Storage::disk('r2')->put('generated-assets/two.mp4', 'segment-two');

        [$binary, $argumentsPath] = $this->fakeFfmpegBinary();
        config(['grok.ffmpeg_binary' => $binary]);

        $outputPath = app(VideoMergeService::class)->merge([
            'generated-assets/one.mp4',
            'generated-assets/two.mp4',
        ], 124, 'r2');

        $arguments = json_decode(file_get_contents($argumentsPath), true, flags: JSON_THROW_ON_ERROR);
        $concatFile = $arguments[array_search('-i', $arguments, true) + 1];

        $this->assertFileExists($outputPath);
        $this->assertFileExists($concatFile);
        $this->assertStringContainsString('segment-1.mp4', file_get_contents($concatFile));
        $this->assertStringContainsString('segment-2.mp4', file_get_contents($concatFile));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fakeFfmpegBinary(): array
    {
        $directory = storage_path('app/testing/fake-ffmpeg');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $argumentsPath = $directory.DIRECTORY_SEPARATOR.'arguments.json';

        if (PHP_OS_FAMILY === 'Windows') {
            $binary = $directory.DIRECTORY_SEPARATOR.'ffmpeg.cmd';
            file_put_contents($binary, <<<'BAT'
@echo off
php "%~dp0ffmpeg.php" %*
BAT);
        } else {
            $binary = $directory.DIRECTORY_SEPARATOR.'ffmpeg';
            file_put_contents($binary, <<<'SH'
#!/usr/bin/env sh
php "$(dirname "$0")/ffmpeg.php" "$@"
SH);
            chmod($binary, 0755);
        }

        file_put_contents($directory.DIRECTORY_SEPARATOR.'ffmpeg.php', <<<'PHP'
<?php

$arguments = array_slice($_SERVER['argv'], 1);
file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'arguments.json', json_encode($arguments, JSON_THROW_ON_ERROR));
file_put_contents($arguments[array_key_last($arguments)], 'merged-video');

exit(0);
PHP);

        return [$binary, $argumentsPath];
    }
}
