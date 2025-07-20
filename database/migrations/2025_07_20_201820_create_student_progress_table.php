<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->string('learning_material_id'); // MongoDB ObjectId
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed'])->default('not_started');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('time_spent')->default(0); // in seconds
            $table->json('interaction_data')->nullable(); // track clicks, plays, etc.
            $table->decimal('score', 5, 2)->nullable(); // for assessments
            $table->integer('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id']);
            $table->index(['student_id', 'section_id']);
            $table->index('learning_material_id');
            $table->unique(['student_id', 'learning_material_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_progress');
    }
};
