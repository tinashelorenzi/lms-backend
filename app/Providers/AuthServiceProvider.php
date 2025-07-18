<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Enums\UserType;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for user types
        Gate::define('viewAdmin', function (User $user) {
            return $user->user_type === UserType::ADMIN;
        });

        Gate::define('viewTeacher', function (User $user) {
            return in_array($user->user_type, [UserType::ADMIN, UserType::TEACHER]);
        });

        Gate::define('viewStudent', function (User $user) {
            return in_array($user->user_type, [UserType::ADMIN, UserType::TEACHER, UserType::STUDENT]);
        });
    }
}