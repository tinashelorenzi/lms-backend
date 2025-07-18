<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'video']); // We'll start with these two
            $table->longText('content')->nullable(); // For text content (rich text, HTML)
            $table->string('video_platform')->nullable(); // youtube, vimeo, dailymotion, etc.
            $table->string('video_id')->nullable(); // Platform-specific video ID
            $table->text('video_url')->nullable(); // Full URL if needed
            $table->json('video_metadata')->nullable(); // Duration, thumbnail, etc.
            $table->integer('estimated_duration')->nullable(); // in minutes
            $table->json('tags')->nullable(); // For categorization
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['video_platform', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};