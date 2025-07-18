<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login student using email or student ID
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'identifier' => 'required|string', // This can be email or student ID
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('identifier', 'password');

            // Find user by email or student ID
            $user = User::findByEmailOrStudentId($credentials['identifier']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is a student
            if ($user->user_type !== UserType::STUDENT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Students only.'
                ], 403);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive. Please contact administration.'
                ], 403);
            }

            // Verify password
            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Update last login
            $user->updateLastLogin();

            // Create token
            $token = $user->createToken('student-app', ['student'])->plainTextToken;

            // Load student profile
            $user->load('studentProfile');

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                        'gender' => $user->gender,
                        'address' => $user->address,
                        'user_type' => $user->user_type->value,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                        'profile' => $user->studentProfile ? [
                            'student_id' => $user->studentProfile->student_id,
                            'class_year' => $user->studentProfile->class_year,
                            'major' => $user->studentProfile->major,
                            'gpa' => (float) $user->studentProfile->gpa,
                            'enrollment_date' => $user->studentProfile->enrollment_date?->format('Y-m-d'),
                            'parent_name' => $user->studentProfile->parent_name,
                            'parent_phone' => $user->studentProfile->parent_phone,
                            'parent_email' => $user->studentProfile->parent_email,
                            'emergency_contact' => $user->studentProfile->emergency_contact,
                        ] : null
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load('studentProfile');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                        'gender' => $user->gender,
                        'address' => $user->address,
                        'user_type' => $user->user_type->value,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                        'profile' => $user->studentProfile ? [
                            'student_id' => $user->studentProfile->student_id,
                            'class_year' => $user->studentProfile->class_year,
                            'major' => $user->studentProfile->major,
                            'gpa' => (float) $user->studentProfile->gpa,
                            'enrollment_date' => $user->studentProfile->enrollment_date?->format('Y-m-d'),
                            'parent_name' => $user->studentProfile->parent_name,
                            'parent_phone' => $user->studentProfile->parent_phone,
                            'parent_email' => $user->studentProfile->parent_email,
                            'emergency_contact' => $user->studentProfile->emergency_contact,
                        ] : null
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Delete current access token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Refresh token (revoke current and create new)
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Delete current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('student-app', ['student'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during token refresh',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}