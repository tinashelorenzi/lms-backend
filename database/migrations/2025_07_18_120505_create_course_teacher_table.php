<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year'); // e.g., "2024-2025"
            $table->string('semester')->nullable(); // e.g., "Fall", "Spring"
            $table->boolean('is_primary')->default(false); // Primary teacher for the course
            $table->timestamps();
            
            $table->unique(['course_id', 'teacher_id', 'academic_year', 'semester'], 'course_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_teacher');
    }
};