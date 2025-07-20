<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Course Builder Header -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Course Builder</h2>
                        <p class="text-sm text-gray-600">Create and manage course structures with learning materials</p>
                    </div>
                    <div class="flex space-x-3">
                        @if($this->selectedCourseId)
                            <x-filament::button
                                wire:click="previewCourse"
                                color="gray"
                                icon="heroicon-o-eye"
                            >
                                Preview Course
                            </x-filament::button>
                            
                            <x-filament::button
                                wire:click="duplicateCourse"
                                color="warning"
                                icon="heroicon-o-document"
                            >
                                Duplicate Course
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Builder Form -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Course Structure</h3>
                <p class="text-sm text-gray-600">Select a course and build its structure with sections and materials</p>
            </div>
            
            <div class="p-6">
                <form wire:submit="save">
                    {{ $this->form }}
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <x-filament::button
                            type="button"
                            wire:click="$refresh"
                            color="gray"
                        >
                            Reset
                        </x-filament::button>
                        
                        <x-filament::button
                            type="submit"
                            color="primary"
                            icon="heroicon-o-check"
                            :disabled="!$this->selectedCourseId"
                        >
                            Save Course Structure
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Course Structure Preview -->
        @if($this->selectedCourseId && !empty($this->courseStructure))
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Current Course Structure</h3>
                    <p class="text-sm text-gray-600">Preview of the current course layout</p>
                </div>
                
                <div class="p-6">
                    @if(isset($this->courseStructure['course']))
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-900">{{ $this->courseStructure['course']->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $this->courseStructure['course']->code }} • {{ $this->courseStructure['course']->credits }} Credits</p>
                        </div>
                    @endif
                    
                    <div class="space-y-4">
                        @foreach($this->courseStructure['sections'] ?? [] as $section)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                            {{ $section->pivot->order_number ?? $loop->iteration }}
                                        </span>
                                        <h5 class="text-md font-medium text-gray-900">{{ $section->title }}</h5>
                                        @if($section->pivot->is_required ?? true)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                                Required
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $section->materials->count() }} materials
                                    </div>
                                </div>
                                
                                @if($section->description)
                                    <p class="text-sm text-gray-600 mb-3">{{ $section->description }}</p>
                                @endif
                                
                                @if($section->materials->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($section->materials as $material)
                                            <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded">
                                                <div class="flex-shrink-0">
                                                    @switch($material->mongo_content->content_type ?? 'text')
                                                        @case('video')
                                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            @break
                                                        @case('document')
                                                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                            @break
                                                        @case('quiz')
                                                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                                            </svg>
                                                            @break
                                                        @default
                                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                    @endswitch
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $material->mongo_content->title ?? 'Untitled Material' }}
                                                    </p>
                                                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                        <span class="capitalize">{{ $material->mongo_content->content_type ?? 'text' }}</span>
                                                        @if($material->pivot->is_required ?? true)
                                                            <span>•</span>
                                                            <span class="text-red-600 font-medium">Required</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0 text-xs text-gray-400">
                                                    #{{ $material->pivot->order_number ?? $loop->iteration }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-sm text-gray-500 italic">No materials added yet</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Help Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Course Builder Tips</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Select a course to start building its structure</li>
                            <li>Add sections to organize your course content</li>
                            <li>Include various material types: text, video, documents, quizzes, and assignments</li>
                            <li>Set required materials to ensure students complete essential content</li>
                            <li>Use the preview feature to see how your course will appear to students</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>