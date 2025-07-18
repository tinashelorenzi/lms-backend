<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->integer('order_number')->default(1); // Order within the course
            $table->enum('status', ['closed', 'open', 'automated'])->default('closed');
            $table->json('automation_rules')->nullable(); // Rules for when to open
            $table->timestamp('opens_at')->nullable(); // Manual or automated opening
            $table->timestamp('closes_at')->nullable(); // Optional closing time
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->unique(['course_id', 'section_id']);
            $table->index(['course_id', 'order_number']);
            $table->index(['status', 'opens_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};