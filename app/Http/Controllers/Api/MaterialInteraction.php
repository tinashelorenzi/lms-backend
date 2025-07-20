<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompleteStudentProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MaterialInteractionController extends Controller
{
    protected CompleteStudentProgressService $progressService;

    public function __construct(CompleteStudentProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Enhanced quiz submission with detailed tracking
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
            $result = $this->progressService->submitQuiz(
                Auth::id(),
                $materialId,
                $request->input('answers'),
                $request->input('time_taken')
            );

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 400);
            }

            // Store detailed quiz result
            DB::table('quiz_results')->insert([
                'student_id' => Auth::id(),
                'material_id' => $materialId,
                'attempt_number' => DB::table('quiz_results')
                    ->where('student_id', Auth::id())
                    ->where('material_id', $materialId)
                    ->count() + 1,
                'score' => $result['score'],
                'total_questions' => $result['total_questions'],
                'correct_answers' => $result['correct_answers'],
                'time_taken' => $result['time_taken'],
                'answers' => json_encode($request->input('answers')),
                'question_results' => json_encode($result['results']),
                'passed' => $result['passed'],
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit quiz'], 500);
        }
    }

    /**
     * Enhanced assignment submission
     */
    public function submitAssignment(Request $request, string $materialId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_type' => 'required|in:text,file,url',
            'content' => 'required_if:submission_type,text',
            'file' => 'required_if:submission_type,file|file|max:10240', // 10MB
            'url' => 'required_if:submission_type,url|url',
            'course_id' => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $submissionData = [
                'type' => $request->input('submission_type'),
            ];

            // Handle different submission types
            switch ($request->input('submission_type')) {
                case 'text':
                    $submissionData['content'] = $request->input('content');
                    break;
                    
                case 'file':
                    $file = $request->file('file');
                    $path = $file->store('assignments', 'public');
                    $submissionData['file_path'] = $path;
                    $submissionData['original_filename'] = $file->getClientOriginalName();
                    break;
                    
                case 'url':
                    $submissionData['url'] = $request->input('url');
                    break;
            }

            $result = $this->progressService->submitAssignment(
                Auth::id(),
                $materialId,
                $submissionData
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit assignment'], 500);
        }
    }
}