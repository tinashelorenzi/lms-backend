<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('learning_materials', 'content_format')) {
                $table->string('content_format')->default('rich_html')->after('description');
            }
            if (!Schema::hasColumn('learning_materials', 'content_raw')) {
                $table->longText('content_raw')->nullable()->after('content_format');
            }
            if (!Schema::hasColumn('learning_materials', 'content_compiled')) {
                $table->longText('content_compiled')->nullable()->after('content_raw');
            }
            if (!Schema::hasColumn('learning_materials', 'embedded_media')) {
                $table->json('embedded_media')->nullable()->after('content_compiled');
            }
            if (!Schema::hasColumn('learning_materials', 'editor_config')) {
                $table->json('editor_config')->nullable()->after('embedded_media');
            }
            if (!Schema::hasColumn('learning_materials', 'allow_latex')) {
                $table->boolean('allow_latex')->default(false)->after('editor_config');
            }
            if (!Schema::hasColumn('learning_materials', 'allow_embeds')) {
                $table->boolean('allow_embeds')->default(true)->after('allow_latex');
            }
            if (!Schema::hasColumn('learning_materials', 'content_blocks')) {
                $table->json('content_blocks')->nullable()->after('allow_embeds');
            }
            if (!Schema::hasColumn('learning_materials', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            // Drop new fields
            $table->dropColumn([
                'content_format',
                'content_raw',
                'content_compiled',
                'embedded_media',
                'editor_config',
                'allow_latex',
                'allow_embeds',
                'content_blocks',
                'is_featured'
            ]);
        });
    }
};
