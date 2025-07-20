<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure section_materials table exists with proper structure
        if (!Schema::hasTable('section_materials')) {
            Schema::create('section_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->onDelete('cascade');
                $table->string('learning_material_id'); // MongoDB ObjectId as string
                $table->integer('order_number')->default(1);
                $table->boolean('is_required')->default(true);
                $table->json('completion_criteria')->nullable();
                $table->json('settings')->nullable();
                $table->timestamp('available_from')->nullable();
                $table->timestamp('available_until')->nullable();
                $table->timestamps();

                $table->index(['section_id', 'order_number']);
                $table->unique(['section_id', 'learning_material_id']);
            });
        } else {
            // Update existing table if needed
            Schema::table('section_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('section_materials', 'learning_material_id')) {
                    $table->string('learning_material_id')->after('section_id');
                }
                if (!Schema::hasColumn('section_materials', 'order_number')) {
                    $table->integer('order_number')->default(1)->after('learning_material_id');
                }
                if (!Schema::hasColumn('section_materials', 'is_required')) {
                    $table->boolean('is_required')->default(true)->after('order_number');
                }
                if (!Schema::hasColumn('section_materials', 'completion_criteria')) {
                    $table->json('completion_criteria')->nullable()->after('is_required');
                }
                if (!Schema::hasColumn('section_materials', 'settings')) {
                    $table->json('settings')->nullable()->after('completion_criteria');
                }
                if (!Schema::hasColumn('section_materials', 'available_from')) {
                    $table->timestamp('available_from')->nullable()->after('settings');
                }
                if (!Schema::hasColumn('section_materials', 'available_until')) {
                    $table->timestamp('available_until')->nullable()->after('available_from');
                }
            });
        }

        // Ensure course_sections table exists with proper structure
        if (!Schema::hasTable('course_sections')) {
            Schema::create('course_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->foreignId('section_id')->constrained()->onDelete('cascade');
                $table->integer('order_number')->default(1);
                $table->enum('status', ['draft', 'open', 'closed', 'automated'])->default('open');
                $table->json('automation_rules')->nullable();
                $table->timestamp('opens_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->index(['course_id', 'order_number']);
                $table->unique(['course_id', 'section_id']);
            });
        } else {
            // Update existing table if needed
            Schema::table('course_sections', function (Blueprint $table) {
                if (!Schema::hasColumn('course_sections', 'status')) {
                    $table->enum('status', ['draft', 'open', 'closed', 'automated'])->default('open')->after('order_number');
                }
                if (!Schema::hasColumn('course_sections', 'automation_rules')) {
                    $table->json('automation_rules')->nullable()->after('status');
                }
                if (!Schema::hasColumn('course_sections', 'opens_at')) {
                    $table->timestamp('opens_at')->nullable()->after('automation_rules');
                }
                if (!Schema::hasColumn('course_sections', 'closes_at')) {
                    $table->timestamp('closes_at')->nullable()->after('opens_at');
                }
                if (!Schema::hasColumn('course_sections', 'is_required')) {
                    $table->boolean('is_required')->default(true)->after('closes_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_materials');
        Schema::dropIfExists('course_sections');
    }
};