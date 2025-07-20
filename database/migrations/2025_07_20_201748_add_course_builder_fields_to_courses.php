<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('course_settings')->nullable()->after('is_active');
            $table->json('completion_criteria')->nullable()->after('course_settings');
            $table->integer('estimated_total_duration')->nullable()->after('completion_criteria');
            $table->string('difficulty_level')->nullable()->after('estimated_total_duration');
            $table->json('prerequisites')->nullable()->after('difficulty_level');
            $table->text('learning_outcomes')->nullable()->after('prerequisites');
            $table->string('course_format')->default('sections')->after('learning_outcomes'); // sections, modules, topics
            $table->boolean('show_progress')->default(true)->after('course_format');
            $table->boolean('allow_guest_access')->default(false)->after('show_progress');
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'course_settings',
                'completion_criteria',
                'estimated_total_duration',
                'difficulty_level',
                'prerequisites',
                'learning_outcomes',
                'course_format',
                'show_progress',
                'allow_guest_access'
            ]);
        });
    }
};
