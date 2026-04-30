<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $table->string('aspect_ratio', 8)->default('16:9')->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $table->dropColumn('aspect_ratio');
        });
    }
};
