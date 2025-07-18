<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'class_year',
        'major',
        'gpa',
        'enrollment_date',
        'parent_name',
        'parent_phone',
        'parent_email',
        'medical_conditions',
        'emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'gpa' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}