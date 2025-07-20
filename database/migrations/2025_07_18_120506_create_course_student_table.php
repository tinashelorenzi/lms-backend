<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('course_student')) {
            Schema::create('course_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['enrolled', 'active', 'completed', 'dropped'])->default('enrolled');
                $table->timestamp('enrolled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('final_grade', 5, 2)->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'course_id']);
                $table->index(['student_id', 'status']);
                $table->index('enrolled_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('course_student');
    }
};