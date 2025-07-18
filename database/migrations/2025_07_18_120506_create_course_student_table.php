<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year'); // e.g., "2024-2025"
            $table->string('semester')->nullable(); // e.g., "Fall", "Spring"
            $table->date('enrollment_date')->default(now());
            $table->string('status')->default('active'); // active, completed, dropped
            $table->decimal('grade', 5, 2)->nullable(); // Final grade
            $table->timestamps();
            
            $table->unique(['course_id', 'student_id', 'academic_year', 'semester'], 'course_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};