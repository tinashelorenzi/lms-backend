<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'address' => $this->address,
            'user_type' => $this->user_type->value,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'profile' => $this->when($this->relationLoaded('studentProfile') && $this->studentProfile, [
                'student_id' => $this->studentProfile?->student_id,
                'class_year' => $this->studentProfile?->class_year,
                'major' => $this->studentProfile?->major,
                'gpa' => $this->studentProfile?->gpa ? (float) $this->studentProfile->gpa : null,
                'enrollment_date' => $this->studentProfile?->enrollment_date?->format('Y-m-d'),
                'parent_name' => $this->studentProfile?->parent_name,
                'parent_phone' => $this->studentProfile?->parent_phone,
                'parent_email' => $this->studentProfile?->parent_email,
                'emergency_contact' => $this->studentProfile?->emergency_contact,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}