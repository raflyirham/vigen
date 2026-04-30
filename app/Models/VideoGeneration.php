<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'video_type',
        'topic',
        'keywords',
        'target_audience',
        'tone',
        'aspect_ratio',
        'requested_duration_seconds',
        'effective_video_duration_seconds',
        'template_key',
        'model',
        'status',
        'composed_prompt',
        'script',
        'storyboard',
        'video_url',
        'local_video_path',
        'error_message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'requested_duration_seconds' => 'integer',
            'effective_video_duration_seconds' => 'integer',
            'storyboard' => 'array',
            'raw_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
