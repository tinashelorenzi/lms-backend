<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable(); // Learning objectives
            $table->integer('estimated_duration')->nullable(); // in minutes
            $table->json('metadata')->nullable(); // Store additional flexible data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};