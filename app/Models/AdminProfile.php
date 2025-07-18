<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'position',
        'department',
        'salary',
        'hire_date',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}