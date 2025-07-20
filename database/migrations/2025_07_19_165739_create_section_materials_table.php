<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Only create if it doesn't already exist
        if (!Schema::hasTable('section_materials')) {
            Schema::create('section_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->onDelete('cascade');
                $table->string('learning_material_id', 24); // MongoDB ObjectId as string - FIXED
                $table->integer('order_number')->default(1);
                $table->boolean('is_required')->default(true);
                $table->json('completion_criteria')->nullable();
                $table->timestamps();
                
                $table->unique(['section_id', 'learning_material_id']);
                $table->index(['section_id', 'order_number']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('section_materials');
    }
};