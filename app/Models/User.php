<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'is_active',
        'last_login_at',
        'phone',
        'date_of_birth',
        'gender',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByType($query, UserType $type)
    {
        return $query->where('user_type', $type);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->user_type === UserType::ADMIN;
    }

    public function getIsTeacherAttribute(): bool
    {
        return $this->user_type === UserType::TEACHER;
    }

    public function getIsStudentAttribute(): bool
    {
        return $this->user_type === UserType::STUDENT;
    }

    // Helper methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}