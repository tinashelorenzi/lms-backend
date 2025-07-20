<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SectionStatus;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->integer('order_number')->default(1);
            $table->enum('status', array_column(SectionStatus::cases(), 'value'))->default('draft');
            $table->json('automation_rules')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->unique(['course_id', 'section_id']);
            $table->index(['course_id', 'order_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_sections');
    }
};