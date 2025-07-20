<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('total_enrollments')->default(0);
            $table->integer('active_students')->default(0);
            $table->integer('completed_students')->default(0);
            $table->decimal('average_completion_rate', 5, 2)->default(0);
            $table->integer('total_time_spent')->default(0); // in minutes
            $table->decimal('average_score', 5, 2)->nullable();
            $table->json('section_analytics')->nullable(); // per-section breakdown
            $table->json('material_analytics')->nullable(); // per-material breakdown
            $table->timestamps();

            $table->unique(['course_id', 'date']);
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_analytics');
    }
};
