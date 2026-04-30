<?php

namespace Tests\Feature;

use App\Services\VideoAssetStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoAssetStorageTest extends TestCase
{
    public function test_store_downloaded_asset_falls_back_to_local_when_primary_disk_is_unavailable(): void
    {
        Storage::fake('local');
        config([
            'grok.asset_disk' => 'r2',
            'grok.asset_fallback_disk' => 'local',
        ]);

        $path = app(VideoAssetStorage::class)->storeDownloadedAsset([
            'body' => 'video-bytes',
            'content_type' => 'video/mp4',
        ], 999, 'Fallback Scenario', 'segment-1');

        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('video-bytes', Storage::disk('local')->get($path));
    }
}
