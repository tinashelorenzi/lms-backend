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
        @if($selectedCourseId && !empty($courseStructure))
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Current Course Structure</h4>
                
                <div class="space-y-4">
                    @foreach($courseStructure['sections'] ?? [] as $index => $section)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-900">{{ $section->title }}</h5>
                                    @if($section->description)
                                        <p class="text-xs text-gray-600">{{ Str::limit($section->description, 100) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($section->pivot->is_required)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Required
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $section->materials->count() }} Materials
                                </span>
                            </div>
                        </div>

                        @if($section->materials->count() > 0)
                        <div class="ml-11 space-y-2">
                            @foreach($section->materials as $material)
                            <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded">
                                <div class="flex-shrink-0">
                                    @switch($material->content_type)
                                        @case('video')
                                            <x-heroicon-o-play-circle class="w-5 h-5 text-blue-500" />
                                            @break
                                        @case('document')
                                            <x-heroicon-o-document class="w-5 h-5 text-green-500" />
                                            @break
                                        @case('quiz')
                                            <x-heroicon-o-question-mark-circle class="w-5 h-5 text-purple-500" />
                                            @break
                                        @case('assignment')
                                            <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-orange-500" />
                                            @break
                                        @default
                                            <x-heroicon-o-document-text class="w-5 h-5 text-gray-500" />
                                    @endswitch
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $material->title }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst($material->content_type) }}</p>
                                </div>
                                @if($material->estimated_duration)
                                <div class="text-xs text-gray-500">
                                    {{ $material->estimated_duration }}min
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <x-filament::button
                wire:click="save"
                color="primary"
                icon="heroicon-o-check">
                Save Course Structure
            </x-filament::button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <x-filament::loading-indicator class="w-6 h-6" />
            <span class="text-sm text-gray-900">Processing...</span>
        </div>
    </div>
</x-filament-panels::page>