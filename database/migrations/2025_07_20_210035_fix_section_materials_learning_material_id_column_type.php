<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First, we need to update the section_materials table to use string for learning_material_id
        // instead of foreignId (which creates an integer)
        
        if (Schema::hasTable('section_materials')) {
            // Check current column type
            $columnType = Schema::getColumnType('section_materials', 'learning_material_id');
            
            if ($columnType !== 'string' && $columnType !== 'varchar') {
                // Drop foreign key constraint if it exists
                try {
                    Schema::table('section_materials', function (Blueprint $table) {
                        $table->dropForeign(['learning_material_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                
                // Change column type to string for MongoDB ObjectId
                Schema::table('section_materials', function (Blueprint $table) {
                    $table->string('learning_material_id', 24)->change();
                });
            }
        }
    }

    public function down()
    {
        // Revert back to integer if needed (though this might cause data loss)
        if (Schema::hasTable('section_materials')) {
            Schema::table('section_materials', function (Blueprint $table) {
                $table->unsignedBigInteger('learning_material_id')->change();
            });
        }
    }
};