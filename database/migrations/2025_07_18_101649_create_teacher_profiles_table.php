<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('employee_id')->unique();
            $table->string('department')->nullable();
            $table->string('qualification')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('hire_date');
            $table->string('specialization')->nullable();
            $table->text('certifications')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};