<?php

namespace App\Services;

use App\Jobs\GenerateVideoJob;
use App\Models\VideoGeneration;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;

class VideoGenerationDispatcher
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function dispatch(VideoGeneration $generation): void
    {
        $connection = (string) config('grok.queue_connection', 'database');
        $queue = (string) config('grok.queue_name', 'default');
        $job = (new GenerateVideoJob($generation->id))
            ->onConnection($connection)
            ->onQueue($queue);

        Log::info('Dispatching video generation job.', [
            'generation_id' => $generation->id,
            'connection' => $connection,
            'queue' => $queue,
        ]);

        $this->dispatcher->dispatch($job);

        $this->startWorkerIfNeeded($connection, $queue, $generation->id);
    }

    private function startWorkerIfNeeded(string $connection, string $queue, int $generationId): void
    {
        if (! config('grok.auto_start_worker', true) || app()->runningUnitTests()) {
            return;
        }

        if ($connection !== 'database') {
            Log::info('Skipping auto-start worker because the connection is not database.', [
                'generation_id' => $generationId,
                'connection' => $connection,
            ]);

            return;
        }

        $command = $this->workerCommand($connection, $queue);
        $started = $this->startDetachedProcess($command);

        Log::info('Started one-off queue worker for video generation.', [
            'generation_id' => $generationId,
            'started' => $started,
            'command' => $command,
            'log_path' => storage_path('logs/video-generation-worker.log'),
        ]);
    }

    private function workerCommand(string $connection, string $queue): string
    {
        $parts = [
            escapeshellarg(PHP_BINARY),
            'artisan',
            'queue:work',
            escapeshellarg($connection),
            '--once',
            '--queue='.escapeshellarg($queue),
            '--tries=1',
            '--timeout=1800',
            '--sleep=1',
            '--verbose',
        ];

        return implode(' ', $parts);
    }

    private function startDetachedProcess(string $command): bool
    {
        $logPath = storage_path('logs/video-generation-worker.log');
        $fullCommand = $command.' >> '.escapeshellarg($logPath).' 2>&1';

        if (PHP_OS_FAMILY === 'Windows') {
            $windowsCommand = 'start /B "" cmd /C "cd /D '.str_replace('"', '\"', base_path()).' && '.$fullCommand.'"';
            pclose(popen($windowsCommand, 'r'));

            return true;
        }

        pclose(popen('cd '.escapeshellarg(base_path()).' && '.$fullCommand.' &', 'r'));

        return true;
    }
}
