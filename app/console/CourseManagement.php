<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Services\CourseContentService;
use App\Services\StudentProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncCourseData extends Command
{
    protected $signature = 'course:sync-data {--course-id=} {--dry-run}';
    protected $description = 'Sync course data between MySQL and MongoDB';

    protected CourseContentService $courseContentService;

    public function __construct(CourseContentService $courseContentService)
    {
        parent::__construct();
        $this->courseContentService = $courseContentService;
    }

    public function handle()
    {
        $courseId = $this->option('course-id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        if ($courseId) {
            $this->syncSingleCourse($courseId, $dryRun);
        } else {
            $this->syncAllCourses($dryRun);
        }
    }

    private function syncSingleCourse(int $courseId, bool $dryRun): void
    {
        $course = Course::find($courseId);
        if (!$course) {
            $this->error("Course with ID {$courseId} not found");
            return;
        }

        $this->info("Syncing course: {$course->name}");

        // Get orphaned material references
        $orphanedMaterials = DB::table('section_materials')
            ->join('course_sections', 'section_materials.section_id', '=', 'course_sections.section_id')
            ->where('course_sections.course_id', $courseId)
            ->pluck('section_materials.learning_material_id')
            ->unique();

        $missingMaterials = [];
        foreach ($orphanedMaterials as $materialId) {
            $material = LearningMaterial::find($materialId);
            if (!$material) {
                $missingMaterials[] = $materialId;
            }
        }

        if (!empty($missingMaterials)) {
            $this->warn("Found " . count($missingMaterials) . " missing materials in MongoDB");
            if (!$dryRun) {
                // Remove orphaned references
                DB::table('section_materials')
                    ->whereIn('learning_material_id', $missingMaterials)
                    ->delete();
                $this->info("Removed orphaned material references");
            }
        }

        // Check for unused materials in MongoDB
        $allMaterialIds = DB::table('section_materials')
            ->join('course_sections', 'section_materials.section_id', '=', 'course_sections.section_id')
            ->where('course_sections.course_id', $courseId)
            ->pluck('section_materials.learning_material_id')
            ->unique();

        $mongoMaterials = LearningMaterial::whereNotIn('_id', $allMaterialIds)->get();
        if ($mongoMaterials->count() > 0) {
            $this->info("Found {$mongoMaterials->count()} unused materials in MongoDB");
            // Optionally archive or remove these
        }

        $this->info("Sync completed for course: {$course->name}");
    }

    private function syncAllCourses(bool $dryRun): void
    {
        $courses = Course::active()->get();
        $this->info("Syncing " . $courses->count() . " active courses");

        foreach ($courses as $course) {
            $this->syncSingleCourse($course->id, $dryRun);
        }
    }
}

// Command: GenerateAnalytics.php
class GenerateAnalytics extends Command
{
    protected $signature = 'course:generate-analytics {--course-id=} {--date=} {--days=30}';
    protected $description = 'Generate course analytics for specified period';

    protected StudentProgressService $progressService;

    public function __construct(StudentProgressService $progressService)
    {
        parent::__construct();
        $this->progressService = $progressService;
    }

    public function handle()
    {
        $courseId = $this->option('course-id');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $days = (int) $this->option('days');

        if ($courseId) {
            $this->generateForCourse($courseId, $date, $days);
        } else {
            $this->generateForAllCourses($date, $days);
        }
    }

    private function generateForCourse(int $courseId, Carbon $date, int $days): void
    {
        $course = Course::find($courseId);
        if (!$course) {
            $this->error("Course with ID {$courseId} not found");
            return;
        }

        $this->info("Generating analytics for course: {$course->name}");

        $startDate = $date->copy()->subDays($days);
        $currentDate = $startDate->copy();

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        while ($currentDate->lte($date)) {
            $this->progressService->generateDailyAnalytics($courseId, $currentDate);
            $currentDate->addDay();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Analytics generated successfully");
    }

    private function generateForAllCourses(Carbon $date, int $days): void
    {
        $courses = Course::active()->get();
        $this->info("Generating analytics for " . $courses->count() . " courses");

        foreach ($courses as $course) {
            $this->generateForCourse($course->id, $date, $days);
        }
    }
}

// Command: MigrateMaterialsToMongo.php
class MigrateMaterialsToMongo extends Command
{
    protected $signature = 'course:migrate-materials {--batch-size=100} {--dry-run}';
    protected $description = 'Migrate existing materials from MySQL to MongoDB';

    public function handle()
    {
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        // Assuming you have existing materials in a MySQL table to migrate
        $this->info('Starting material migration to MongoDB...');

        // Example migration logic - adjust based on your existing schema
        $totalMaterials = DB::table('old_learning_materials')->count();
        
        if ($totalMaterials === 0) {
            $this->info('No materials found to migrate');
            return;
        }

        $this->info("Found {$totalMaterials} materials to migrate");
        $bar = $this->output->createProgressBar($totalMaterials);
        $bar->start();

        DB::table('old_learning_materials')
            ->orderBy('id')
            ->chunk($batchSize, function ($materials) use ($dryRun, $bar) {
                foreach ($materials as $oldMaterial) {
                    if (!$dryRun) {
                        // Convert to MongoDB format
                        $mongoMaterial = [
                            'title' => $oldMaterial->title,
                            'description' => $oldMaterial->description,
                            'content_type' => $this->mapContentType($oldMaterial->type),
                            'content_data' => $this->convertContentData($oldMaterial),
                            'metadata' => [
                                'migrated_from_id' => $oldMaterial->id,
                                'migrated_at' => now()->toISOString(),
                            ],
                            'estimated_duration' => $oldMaterial->duration ?? 0,
                            'is_active' => $oldMaterial->is_active ?? true,
                            'created_at' => $oldMaterial->created_at,
                            'updated_at' => now(),
                        ];

                        $newMaterial = LearningMaterial::create($mongoMaterial);

                        // Update references in section_materials table
                        DB::table('section_materials')
                            ->where('learning_material_id', $oldMaterial->id)
                            ->update(['learning_material_id' => $newMaterial->_id]);
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        
        if (!$dryRun) {
            $this->info('Migration completed successfully');
        } else {
            $this->info('Dry run completed - use without --dry-run to perform actual migration');
        }
    }

    private function mapContentType(string $oldType): string
    {
        return match ($oldType) {
            'video_content' => 'video',
            'pdf_document' => 'document',
            'quiz_assessment' => 'quiz',
            'homework_assignment' => 'assignment',
            default => 'document'
        };
    }

    private function convertContentData($oldMaterial): array
    {
        $type = $this->mapContentType($oldMaterial->type);
        
        return match ($type) {
            'video' => [
                'url' => $oldMaterial->video_url ?? '',
                'duration' => $oldMaterial->video_duration ?? 0,
            ],
            'document' => [
                'file_url' => $oldMaterial->file_path ?? '',
                'file_type' => $oldMaterial->file_type ?? 'pdf',
                'download_allowed' => $oldMaterial->allow_download ?? true,
            ],
            'quiz' => [
                'questions' => json_decode($oldMaterial->quiz_data ?? '[]', true),
                'time_limit' => $oldMaterial->time_limit ?? null,
                'passing_score' => $oldMaterial->passing_score ?? 70,
            ],
            default => []
        };
    }
}

// Command: CleanupOrphanedData.php
class CleanupOrphanedData extends Command
{
    protected $signature = 'course:cleanup {--type=all} {--dry-run}';
    protected $description = 'Clean up orphaned data in course system';

    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        match ($type) {
            'materials' => $this->cleanupOrphanedMaterials($dryRun),
            'progress' => $this->cleanupOrphanedProgress($dryRun),
            'sections' => $this->cleanupOrphanedSections($dryRun),
            'all' => $this->cleanupAll($dryRun),
            default => $this->error('Invalid cleanup type. Use: materials, progress, sections, or all')
        };
    }

    private function cleanupOrphanedMaterials(bool $dryRun): void
    {
        $this->info('Cleaning up orphaned materials...');

        // Find materials referenced in MySQL but not in MongoDB
        $referencedMaterialIds = DB::table('section_materials')
            ->pluck('learning_material_id')
            ->unique();

        $orphanedCount = 0;
        foreach ($referencedMaterialIds as $materialId) {
            $material = LearningMaterial::find($materialId);
            if (!$material) {
                $orphanedCount++;
                if (!$dryRun) {
                    DB::table('section_materials')
                        ->where('learning_material_id', $materialId)
                        ->delete();
                    
                    DB::table('student_progress')
                        ->where('learning_material_id', $materialId)
                        ->delete();
                }
            }
        }

        $this->info("Found {$orphanedCount} orphaned material references");
        
        // Find unused materials in MongoDB
        $usedMaterialIds = DB::table('section_materials')
            ->pluck('learning_material_id')
            ->unique()
            ->toArray();

        $unusedMaterials = LearningMaterial::whereNotIn('_id', $usedMaterialIds)->get();
        $this->info("Found {$unusedMaterials->count()} unused materials in MongoDB");

        if (!$dryRun && $unusedMaterials->count() > 0) {
            if ($this->confirm('Delete unused materials from MongoDB?')) {
                foreach ($unusedMaterials as $material) {
                    $material->delete();
                }
                $this->info('Unused materials deleted');
            }
        }
    }

    private function cleanupOrphanedProgress(bool $dryRun): void
    {
        $this->info('Cleaning up orphaned progress records...');

        // Find progress records for non-existent materials
        $progressMaterialIds = DB::table('student_progress')
            ->pluck('learning_material_id')
            ->unique();

        $orphanedProgressCount = 0;
        foreach ($progressMaterialIds as $materialId) {
            $material = LearningMaterial::find($materialId);
            if (!$material) {
                $orphanedProgressCount++;
                if (!$dryRun) {
                    DB::table('student_progress')
                        ->where('learning_material_id', $materialId)
                        ->delete();
                }
            }
        }

        $this->info("Found {$orphanedProgressCount} orphaned progress records");

        // Find progress records for non-existent courses/sections
        $orphanedCourseProgress = DB::table('student_progress')
            ->leftJoin('courses', 'student_progress.course_id', '=', 'courses.id')
            ->whereNull('courses.id')
            ->count();

        $orphanedSectionProgress = DB::table('student_progress')
            ->leftJoin('sections', 'student_progress.section_id', '=', 'sections.id')
            ->whereNull('sections.id')
            ->count();

        if ($orphanedCourseProgress > 0) {
            $this->info("Found {$orphanedCourseProgress} progress records for non-existent courses");
            if (!$dryRun) {
                DB::table('student_progress')
                    ->leftJoin('courses', 'student_progress.course_id', '=', 'courses.id')
                    ->whereNull('courses.id')
                    ->delete();
            }
        }

        if ($orphanedSectionProgress > 0) {
            $this->info("Found {$orphanedSectionProgress} progress records for non-existent sections");
            if (!$dryRun) {
                DB::table('student_progress')
                    ->leftJoin('sections', 'student_progress.section_id', '=', 'sections.id')
                    ->whereNull('sections.id')
                    ->delete();
            }
        }
    }

    private function cleanupOrphanedSections(bool $dryRun): void
    {
        $this->info('Cleaning up orphaned section relationships...');

        // Find course_sections referencing non-existent courses
        $orphanedCourseSections = DB::table('course_sections')
            ->leftJoin('courses', 'course_sections.course_id', '=', 'courses.id')
            ->whereNull('courses.id')
            ->count();

        if ($orphanedCourseSections > 0) {
            $this->info("Found {$orphanedCourseSections} course_sections for non-existent courses");
            if (!$dryRun) {
                DB::table('course_sections')
                    ->leftJoin('courses', 'course_sections.course_id', '=', 'courses.id')
                    ->whereNull('courses.id')
                    ->delete();
            }
        }

        // Find section_materials referencing non-existent sections
        $orphanedSectionMaterials = DB::table('section_materials')
            ->leftJoin('sections', 'section_materials.section_id', '=', 'sections.id')
            ->whereNull('sections.id')
            ->count();

        if ($orphanedSectionMaterials > 0) {
            $this->info("Found {$orphanedSectionMaterials} section_materials for non-existent sections");
            if (!$dryRun) {
                DB::table('section_materials')
                    ->leftJoin('sections', 'section_materials.section_id', '=', 'sections.id')
                    ->whereNull('sections.id')
                    ->delete();
            }
        }
    }

    private function cleanupAll(bool $dryRun): void
    {
        $this->cleanupOrphanedMaterials($dryRun);
        $this->cleanupOrphanedProgress($dryRun);
        $this->cleanupOrphanedSections($dryRun);
        $this->info('Complete cleanup finished');
    }
}