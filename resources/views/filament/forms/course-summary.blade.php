<div class="space-y-6">
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Course Summary</h3>
        
        @if(isset($getState()['course']))
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Course Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $getState()['course']['name'] ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Course Code</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $getState()['course']['code'] ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Credits</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $getState()['course']['credits'] ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $getState()['course']['department'] ?? 'Not specified' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $getState()['course']['description'] ?? 'No description provided' }}</dd>
                    </div>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500">No course information available</p>
        @endif
    </div>

    @if(isset($getState()['sections']) && count($getState()['sections']) > 0)
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-md font-medium text-gray-900 mb-3">Course Sections ({{ count($getState()['sections']) }})</h4>
            <div class="space-y-3">
                @foreach($getState()['sections'] as $index => $section)
                    <div class="bg-white rounded border p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 text-primary-800 text-xs font-medium">
                                    {{ $section['order_number'] ?? $index + 1 }}
                                </span>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-900">
                                        {{ $section['section_title'] ?? 'Untitled Section' }}
                                    </h5>
                                    <p class="text-xs text-gray-500">
                                        Status: {{ ucfirst($section['status'] ?? 'draft') }}
                                        @if($section['is_required'] ?? true)
                                            • Required
                                        @else
                                            • Optional
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-yellow-50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">No sections added</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>You haven't added any sections to this course yet. You can add sections in the previous step or create them later.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-blue-50 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Ready to create</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Review the information above. If everything looks correct, click "Create Course" to save your course.</p>
                </div>
            </div>
        </div>
    </div>
</div> 