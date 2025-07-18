<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Grade 10A", "Class A"
            $table->string('code')->unique(); // e.g., "10A"
            $table->text('description')->nullable();
            $table->string('grade_level')->nullable(); // e.g., "Grade 10", "Year 1"
            $table->integer('max_students')->default(30);
            $table->string('academic_year'); // e.g., "2024-2025"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};