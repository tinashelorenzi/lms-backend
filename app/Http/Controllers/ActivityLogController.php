<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query();

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        // Filter by action if provided
        if ($request->has('action')) {
            $query->forAction($request->action);
        }

        // Filter by resource type if provided
        if ($request->has('resource_type')) {
            $query->forResourceType($request->resource_type);
        }

        // Get recent logs by default (last 7 days)
        if ($request->has('recent')) {
            $query->recent($request->get('recent', 7));
        }

        $logs = $query->orderBy('created_at', 'desc')
                     ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Store a new activity log entry
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'action' => 'required|string|max:255',
            'resource_type' => 'nullable|string|max:255',
            'resource_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $log = ActivityLog::log(
            $request->user_id,
            $request->action,
            $request->resource_type,
            $request->resource_id,
            $request->description,
            $request->metadata ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity log created successfully',
            'data' => $log,
        ], 201);
    }

    /**
     * Display the specified activity log
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $activityLog,
        ]);
    }

    /**
     * Get activity statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        $stats = ActivityLog::recent($days)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        $userStats = ActivityLog::recent($days)
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'action_stats' => $stats,
                'user_stats' => $userStats,
                'total_logs' => ActivityLog::recent($days)->count(),
            ],
        ]);
    }
} 