<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            // Remove the restrictive type enum and video-specific fields
            $table->dropColumn(['type', 'video_platform', 'video_id', 'video_url', 'video_metadata']);
            
            // Add flexible content fields
            $table->string('content_format')->default('rich_html'); // rich_html, markdown, plain_text
            $table->longText('content_raw')->nullable(); // Source content in selected format
            $table->longText('content_compiled')->nullable(); // Processed HTML for display
            $table->json('embedded_media')->nullable(); // Videos, images, files, etc.
            $table->json('editor_config')->nullable(); // Editor-specific settings
            $table->boolean('allow_latex')->default(false); // Enable LaTeX rendering
            $table->boolean('allow_embeds')->default(true); // Enable web embeds
            $table->json('content_blocks')->nullable(); // For block-based content structure
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropColumn([
                'content_format', 'content_raw', 'content_compiled', 
                'embedded_media', 'editor_config', 'allow_latex', 
                'allow_embeds', 'content_blocks'
            ]);
            
            // Restore old structure
            $table->enum('type', ['text', 'video']);
            $table->string('video_platform')->nullable();
            $table->string('video_id')->nullable();
            $table->text('video_url')->nullable();
            $table->json('video_metadata')->nullable();
        });
    }
};