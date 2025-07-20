<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('material_id'); // MongoDB ObjectId
            $table->enum('submission_type', ['text', 'file', 'url']);
            $table->longText('content')->nullable(); // For text submissions
            $table->string('file_path')->nullable(); // For file submissions
            $table->string('original_filename')->nullable();
            $table->string('url')->nullable(); // For URL submissions
            $table->enum('status', ['submitted', 'graded', 'returned', 'late'])->default('submitted');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'material_id']);
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_submissions');
    }
};