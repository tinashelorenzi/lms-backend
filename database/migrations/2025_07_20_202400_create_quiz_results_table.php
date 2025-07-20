<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('material_id'); // MongoDB ObjectId
            $table->integer('attempt_number');
            $table->decimal('score', 5, 2);
            $table->integer('total_questions');
            $table->integer('correct_answers');
            $table->integer('time_taken'); // seconds
            $table->json('answers'); // Array of student answers
            $table->json('question_results'); // Detailed results per question
            $table->boolean('passed');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['student_id', 'material_id']);
            $table->index('submitted_at');
            $table->unique(['student_id', 'material_id', 'attempt_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_results');
    }
};