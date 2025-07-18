<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', array_column(UserType::cases(), 'value'))
                  ->default(UserType::STUDENT->value)
                  ->after('email');
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('phone')->nullable()->after('last_login_at');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type',
                'is_active',
                'last_login_at',
                'phone',
                'date_of_birth',
                'gender',
                'address'
            ]);
        });
    }
};