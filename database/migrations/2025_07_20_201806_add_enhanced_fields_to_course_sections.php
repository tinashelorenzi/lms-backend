<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->json('section_settings')->nullable()->after('is_required');
            $table->json('completion_requirements')->nullable()->after('section_settings');
            $table->integer('minimum_time_required')->nullable()->after('completion_requirements'); // in minutes
            $table->boolean('track_time')->default(false)->after('minimum_time_required');
            $table->json('prerequisites')->nullable()->after('track_time'); // section dependencies
            $table->string('layout_type')->default('sequential')->after('prerequisites'); // sequential, grid, tabs
        });
    }

    public function down()
    {
        // Check if table exists before trying to drop columns
        if (Schema::hasTable('course_sections')) {
            Schema::table('course_sections', function (Blueprint $table) {
                // Check each column exists before dropping
                if (Schema::hasColumn('course_sections', 'section_settings')) {
                    $table->dropColumn('section_settings');
                }
                if (Schema::hasColumn('course_sections', 'completion_requirements')) {
                    $table->dropColumn('completion_requirements');
                }
                if (Schema::hasColumn('course_sections', 'minimum_time_required')) {
                    $table->dropColumn('minimum_time_required');
                }
                if (Schema::hasColumn('course_sections', 'track_time')) {
                    $table->dropColumn('track_time');
                }
                if (Schema::hasColumn('course_sections', 'prerequisites')) {
                    $table->dropColumn('prerequisites');
                }
                if (Schema::hasColumn('course_sections', 'layout_type')) {
                    $table->dropColumn('layout_type');
                }
            });
        }
    }
};