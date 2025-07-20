<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($this->getCourses() as $course)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <!-- Course Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $course->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $course->code }} • {{ $course->credits }} Credits</p>
                            @if($course->description)
                                <p class="mt-2 text-sm text-gray-700">{{ $course->description }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $course->sections->count() }} Sections
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Course Sections -->
                <div class="divide-y divide-gray-200">
                    @forelse($course->sections as $section)
                        @php $progress = $this->getSectionProgress($section); @endphp
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                            {{ $section->pivot->order_number }}
                                        </span>
                                        <h4 class="text-md font-medium text-gray-900">{{ $section->title }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($section->pivot->status === 'open') bg-green-100 text-green-800
                                            @elseif($section->pivot->status === 'closed') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($section->pivot->status) }}
                                        </span>
                                        @if($section->pivot->is_required)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                                Required
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($section->description)
                                        <p class="text-sm text-gray-600 mb-3 ml-11">{{ $section->description }}</p>
                                    @endif

                                    @if($section->objectives)
                                        <div class="ml-11 mb-4">
                                            <h5 class="text-sm font-medium text-gray-700 mb-1">Learning Objectives:</h5>
                                            <p class="text-sm text-gray-600">{{ $section->objectives }}</p>
                                        </div>
                                    @endif

                                    <!-- Progress Bar -->
                                    <div class="ml-11 mb-4">
                                        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span>{{ $progress['completed'] }}/{{ $progress['total'] }} materials completed</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                                 style="width: {{ $progress['percentage'] }}%"></div>
                                        </div>
                                        <div class="text-right text-xs text-gray-500 mt-1">{{ $progress['percentage'] }}%</div>
                                    </div>

                                    <!-- Learning Materials -->
                                    @if($section->materials->count() > 0)
                                        <div class="ml-11">
                                            <h5 class="text-sm font-medium text-gray-700 mb-2">Learning Materials:</h5>
                                            <div class="space-y-2">
                                                @foreach($section->materials as $material)
                                                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                                        <div class="flex-shrink-0">
                                                            @switch($material->type)
                                                                @case('text')
                                                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                    @break
                                                                @case('video')
                                                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    @break
                                                                @case('quiz')
                                                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                                                    </svg>
                                                                    @break
                                                                @case('file')
                                                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                    @break
                                                                @case('link')
                                                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                                    </svg>
                                                                    @break
                                                                @default
                                                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                            @endswitch
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $material->title }}</p>
                                                            <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                                <span class="capitalize">{{ $material->type }}</span>
                                                                @if($material->estimated_duration)
                                                                    <span>•</span>
                                                                    <span>{{ $material->estimated_duration }} min</span>
                                                                @endif
                                                                @if($material->pivot->is_required)
                                                                    <span>•</span>
                                                                    <span class="text-red-600 font-medium">Required</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            @php $isCompleted = rand(0, 1); @endphp
                                                            @if($isCompleted)
                                                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                                </svg>
                                                            @else
                                                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="ml-11 text-sm text-gray-500 italic">No materials added yet</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No sections available</h3>
                            <p class="mt-1 text-sm text-gray-500">This course doesn't have any sections yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        @if($this->getCourses()->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No courses available</h3>
                <p class="mt-1 text-sm text-gray-500">You are not enrolled in any courses yet.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>