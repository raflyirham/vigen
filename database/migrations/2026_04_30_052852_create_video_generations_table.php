<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('video_type');
            $table->string('topic');
            $table->text('keywords')->nullable();
            $table->string('target_audience');
            $table->string('tone');
            $table->unsignedSmallInteger('requested_duration_seconds');
            $table->unsignedSmallInteger('effective_video_duration_seconds');
            $table->string('template_key')->nullable();
            $table->string('model')->default('grok-imagine-1.0-video');
            $table->string('status')->default('pending');
            $table->longText('composed_prompt');
            $table->longText('script');
            $table->json('storyboard');
            $table->text('video_url')->nullable();
            $table->string('local_video_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'video_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_generations');
    }
};
