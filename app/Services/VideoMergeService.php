<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class VideoMergeService
{
    /**
     * @param  array<int, string>  $segmentPaths
     */
    public function merge(array $segmentPaths, int $generationId): string
    {
        if ($segmentPaths === []) {
            throw new RuntimeException('No video segments are available to merge.');
        }

        $tempDir = storage_path('app/video-merge/'.$generationId.'-'.Str::random(8));

        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Unable to create video merge workspace.');
        }

        $concatFile = $tempDir.DIRECTORY_SEPARATOR.'segments.txt';
        $outputPath = $tempDir.DIRECTORY_SEPARATOR.'merged.mp4';
        $lines = collect($segmentPaths)
            ->map(fn (string $path) => "file '".$this->escapeConcatPath(Storage::disk('local')->path($path))."'")
            ->implode(PHP_EOL);

        file_put_contents($concatFile, $lines.PHP_EOL);

        $process = new Process([
            (string) config('grok.ffmpeg_binary', 'ffmpeg'),
            '-y',
            '-f',
            'concat',
            '-safe',
            '0',
            '-i',
            $concatFile,
            '-map',
            '0:v:0',
            '-map',
            '0:a:0?',
            '-vf',
            'fps=30,format=yuv420p',
            '-c:v',
            'libx264',
            '-preset',
            'veryfast',
            '-crf',
            '23',
            '-c:a',
            'aac',
            '-b:a',
            '192k',
            '-movflags',
            '+faststart',
            $outputPath,
        ]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($outputPath)) {
            throw new RuntimeException('FFmpeg merge failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return $outputPath;
    }

    private function escapeConcatPath(string $path): string
    {
        return str_replace("'", "'\\''", str_replace('\\', '/', $path));
    }
}
