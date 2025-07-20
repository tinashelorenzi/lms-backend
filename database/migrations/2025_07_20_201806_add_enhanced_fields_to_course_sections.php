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
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropColumn([
                'section_settings',
                'completion_requirements',
                'minimum_time_required',
                'track_time',
                'prerequisites',
                'layout_type'
            ]);
        });
    }
};
