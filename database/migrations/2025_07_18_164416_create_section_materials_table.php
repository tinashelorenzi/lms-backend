<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->integer('order_number')->default(1); // Order within the section
            $table->boolean('is_required')->default(true);
            $table->json('completion_criteria')->nullable(); // Custom completion rules
            $table->timestamps();
            
            $table->unique(['section_id', 'learning_material_id']);
            $table->index(['section_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_materials');
    }
};