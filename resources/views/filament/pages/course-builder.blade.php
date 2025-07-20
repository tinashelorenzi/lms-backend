<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Enhanced Course Builder
                        </h3>
                        <p class="text-sm text-gray-600">
                            Create and manage course content with sections and interactive materials, similar to Moodle's course structure.
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        @if($selectedCourseId)
                            <x-filament::button
                                wire:click="previewCourse"
                                color="info"
                                icon="heroicon-o-eye"
                                size="sm">
                                Preview Course
                            </x-filament::button>
                            <x-filament::button
                                wire:click="duplicateCourse"
                                color="gray"
                                icon="heroicon-o-document-duplicate"
                                size="sm">
                                Duplicate Course
                            </x-filament::button>
                        @endif
                    </div>
                </div>
                
                {{ $this->form }}
            </div>
        </div>

        <!-- Course Structure Visualization -->
        @if($selectedCourseId && !empty($courseStructure) && isset($courseStructure['sections']))
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Current Course Structure</h4>
                
                <div class="space-y-4">
                    @forelse($courseStructure['sections'] as $section)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="text-sm font-medium text-gray-900">
                                {{ $section->title ?? 'Untitled Section' }}
                            </h5>
                            <div class="flex items-center space-x-2 text-xs text-gray-500">
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                    {{ (isset($section->materials) ? $section->materials->count() : 0) }} materials
                                </span>
                                @if(isset($section->pivot) && $section->pivot && isset($section->pivot->is_required) && $section->pivot->is_required)
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800">
                                    Required
                                </span>
                                @endif
                            </div>
                        </div>
                        
                        @if(isset($section->description) && $section->description)
                        <p class="text-sm text-gray-600 mb-3">{{ $section->description }}</p>
                        @endif
                        
                        @if(isset($section->materials) && $section->materials && $section->materials->count() > 0)
                        <div class="space-y-2">
                            <h6 class="text-xs font-medium text-gray-700 uppercase tracking-wide">Materials:</h6>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($section->materials as $material)
                                <div class="flex items-center space-x-2 text-xs">
                                    @php
                                        $contentType = 'text';
                                        $title = 'Untitled Material';
                                        
                                        if (isset($material->mongo_content) && $material->mongo_content) {
                                            $contentType = $material->mongo_content->content_type ?? 'text';
                                            $title = $material->mongo_content->title ?? $title;
                                        } elseif (isset($material->content_type)) {
                                            $contentType = $material->content_type;
                                        }
                                        
                                        if (isset($material->title)) {
                                            $title = $material->title;
                                        }
                                        
                                        $colorClass = match($contentType) {
                                            'video' => 'bg-green-400',
                                            'quiz' => 'bg-yellow-400',
                                            'document' => 'bg-blue-400',
                                            'assignment' => 'bg-purple-400',
                                            default => 'bg-gray-400'
                                        };
                                    @endphp
                                    
                                    <div class="w-2 h-2 rounded-full {{ $colorClass }}"></div>
                                    <span class="text-gray-700">{{ $title }}</span>
                                    <span class="text-gray-500">({{ ucfirst($contentType) }})</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>No sections found. Add sections using the form above.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        <!-- Save Actions -->
        @if($selectedCourseId)
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-md font-medium text-gray-900">Save Changes</h4>
                        <p class="text-sm text-gray-600">Save your course structure modifications.</p>
                    </div>
                    <div class="flex space-x-3">
                        <x-filament::button
                            wire:click="save"
                            color="primary"
                            icon="heroicon-o-check"
                            size="sm">
                            Save Course Structure
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Help Section -->
        <div class="bg-blue-50 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h4 class="text-md font-medium text-blue-900 mb-2">How to Use the Course Builder</h4>
                <div class="text-sm text-blue-800 space-y-2">
                    <p><strong>1. Select a Course:</strong> Choose the course you want to edit from the dropdown above.</p>
                    <p><strong>2. Add Sections:</strong> Create logical groupings of your content (like weeks or topics).</p>
                    <p><strong>3. Add Materials:</strong> For each section, add different types of learning materials:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li><span class="inline-block w-2 h-2 bg-gray-400 rounded-full mr-2"></span>Text/HTML for written content</li>
                        <li><span class="inline-block w-2 h-2 bg-green-400 rounded-full mr-2"></span>Videos for multimedia content</li>
                        <li><span class="inline-block w-2 h-2 bg-blue-400 rounded-full mr-2"></span>Documents for downloadable files</li>
                        <li><span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>Quizzes for assessments</li>
                        <li><span class="inline-block w-2 h-2 bg-purple-400 rounded-full mr-2"></span>Assignments for graded work</li>
                    </ul>
                    <p><strong>4. Configure Settings:</strong> Set requirements, due dates, and access controls for each section and material.</p>
                    <p><strong>5. Save:</strong> Click "Save Course Structure" to apply your changes.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>