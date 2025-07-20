<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\StudentProgressService;
use App\Services\CourseContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MaterialInteractionController extends Controller
{
    protected StudentProgressService $progressService;
    protected CourseContentService $contentService;

    public function __construct(
        StudentProgressService $progressService,
        CourseContentService $contentService
    ) {
        $this->progressService = $progressService;
        $this->contentService = $contentService;
    }

    /**
     * Get material content for student
     */
    public function getMaterial(Request $request, string $materialId): JsonResponse
    {
        try {
            $material = LearningMaterial::find($materialId);
            
            if (!$material || !$material->is_active) {
                return response()->json(['error' => 'Material not found'], 404);
            }

            // Track material access
            $this->progressService->trackMaterialInteraction(
                Auth::id(),
                $request->input('course_id'),
                $request->input('section_id'),
                $materialId,
                [
                    'action' => 'viewed',
                    'timestamp' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'material' => [
                    'id' => $material->_id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'content_type' => $material->content_type,
                    'content' => $material->formatted_content,
                    'estimated_duration' => $material->estimated_duration,
                    'learning_objectives' => $material->learning_objectives,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load material'], 500);
        }
    }

    /**
     * Update material progress
     */
    public function updateProgress(Request $request, string $materialId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'time_spent' => 'required|integer|min:0',
            'interaction_data' => 'array',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->progressService->updateMaterialProgress(
                Auth::id(),
                $materialId,
                $request->input('progress_percentage'),
                $request->input('time_spent'),
                $request->input('score')
            );

            // Track interaction
            $this->progressService->trackMaterialInteraction(
                Auth::id(),
                $request->input('course_id'),
                $request->input('section_id'),
                $materialId,
                array_merge(
                    $request->input('interaction_data', []),
                    [
                        'action' => 'progress_updated',
                        'progress' => $request->input('progress_percentage'),
                        'timestamp' => now()->toISOString(),
                    ]
                )
            );

            return response()->json(['success' => true, 'message' => 'Progress updated successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update progress'], 500);
        }
    }

    /**
     * Submit quiz/assessment answers
     */
    public function submitQuiz(Request $request, string $materialId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'time_taken' => 'required|integer|min:0',
            'course_id' => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $material = LearningMaterial::find($materialId);
            
            if (!$material || $material->content_type !== 'quiz') {
                return response()->json(['error' => 'Invalid quiz material'], 400);
            }

            $quizData = $material->content_data;
            $userAnswers = $request->input('answers');
            $score = $this->calculateQuizScore($quizData['questions'], $userAnswers);
            $passed = $score >= ($quizData['passing_score'] ?? 70);

            // Update progress
            $this->progressService->updateMaterialProgress(
                Auth::id(),
                $materialId,
                $passed ? 100 : 0,
                $request->input('time_taken'),
                $score
            );

            // Track submission
            $this->progressService->trackMaterialInteraction(
                Auth::id(),
                $request->input('course_id'),
                $request->input('section_id'),
                $materialId,
                [
                    'action' => 'quiz_submitted',
                    'score' => $score,
                    'passed' => $passed,
                    'time_taken' => $request->input('time_taken'),
                    'answers' => $userAnswers,
                    'timestamp' => now()->toISOString(),
                ]
            );

            return response()->json([
                'success' => true,
                'score' => $score,
                'passed' => $passed,
                'feedback' => $this->generateQuizFeedback($quizData['questions'], $userAnswers),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit quiz'], 500);
        }
    }

    /**
     * Submit assignment
     */
    public function submitAssignment(Request $request, string $materialId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_type' => 'required|in:file,text,url',
            'content' => 'required',
            'course_id' => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $material = LearningMaterial::find($materialId);
            
            if (!$material || $material->content_type !== 'assignment') {
                return response()->json(['error' => 'Invalid assignment material'], 400);
            }

            // Store submission (you might want a separate submissions table)
            $submissionData = [
                'student_id' => Auth::id(),
                'material_id' => $materialId,
                'submission_type' => $request->input('submission_type'),
                'content' => $request->input('content'),
                'submitted_at' => now()->toISOString(),
                'status' => 'submitted',
            ];

            // Update progress to show submission
            $this->progressService->updateMaterialProgress(
                Auth::id(),
                $materialId,
                100, // Mark as complete upon submission
                0 // No additional time tracking for submission
            );

            // Track submission
            $this->progressService->trackMaterialInteraction(
                Auth::id(),
                $request->input('course_id'),
                $request->input('section_id'),
                $materialId,
                [
                    'action' => 'assignment_submitted',
                    'submission_type' => $request->input('submission_type'),
                    'timestamp' => now()->toISOString(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully',
                'submission_id' => uniqid(), // You'd return actual submission ID
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit assignment'], 500);
        }
    }

    /**
     * Get student progress for course
     */
    public function getCourseProgress(Request $request, int $courseId): JsonResponse
    {
        try {
            $progress = $this->progressService->getStudentCourseProgress(Auth::id(), $courseId);
            return response()->json($progress);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load progress'], 500);
        }
    }

    /**
     * Calculate quiz score
     */
    private function calculateQuizScore(array $questions, array $userAnswers): float
    {
        if (empty($questions)) return 0;

        $totalQuestions = count($questions);
        $correctAnswers = 0;

        foreach ($questions as $index => $question) {
            $userAnswer = $userAnswers[$index] ?? null;
            
            if ($question['type'] === 'multiple_choice') {
                foreach ($question['options'] as $optionIndex => $option) {
                    if ($option['is_correct'] && $userAnswer == $optionIndex) {
                        $correctAnswers++;
                        break;
                    }
                }
            } elseif ($question['type'] === 'true_false') {
                $correctOption = null;
                foreach ($question['options'] as $option) {
                    if ($option['is_correct']) {
                        $correctOption = $option['text'];
                        break;
                    }
                }
                if ($userAnswer === $correctOption) {
                    $correctAnswers++;
                }
            }
            // Add more question types as needed
        }

        return ($correctAnswers / $totalQuestions) * 100;
    }

    /**
     * Generate quiz feedback
     */
    private function generateQuizFeedback(array $questions, array $userAnswers): array
    {
        $feedback = [];

        foreach ($questions as $index => $question) {
            $userAnswer = $userAnswers[$index] ?? null;
            $isCorrect = false;
            $correctAnswer = null;

            if ($question['type'] === 'multiple_choice') {
                foreach ($question['options'] as $optionIndex => $option) {
                    if ($option['is_correct']) {
                        $correctAnswer = $option['text'];
                        if ($userAnswer == $optionIndex) {
                            $isCorrect = true;
                        }
                        break;
                    }
                }
            }

            $feedback[] = [
                'question' => $question['question'],
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        return $feedback;
    }
}