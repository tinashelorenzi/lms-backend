<div class="space-y-4">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
        <h4 class="text-md font-medium text-gray-900 mb-3">Course Structure Preview</h4>
        
        @if(isset($getState()['course']['name']))
            <div class="mb-4">
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h5 class="text-sm font-medium text-gray-900 truncate">
                                {{ $getState()['course']['name'] }}
                            </h5>
                            <p class="text-xs text-gray-500">
                                {{ $getState()['course']['code'] ?? 'No code' }} • {{ $getState()['course']['credits'] ?? '0' }} credits
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($getState()['sections']) && count($getState()['sections']) > 0)
            <div class="space-y-2">
                <h5 class="text-sm font-medium text-gray-700">Sections ({{ count($getState()['sections']) }})</h5>
                <div class="space-y-2">
                    @foreach($getState()['sections'] as $index => $section)
                        <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-800 text-xs font-medium">
                                        {{ $section['order_number'] ?? $index + 1 }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h6 class="text-sm font-medium text-gray-900 truncate">
                                        {{ $section['section_title'] ?? 'Untitled Section' }}
                                    </h6>
                                    <div class="flex items-center space-x-2 mt-1">
                                        @php
                                            $statusColor = match($section['status'] ?? 'draft') {
                                                'open' => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-red-100 text-red-800',
                                                'automated' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($section['status'] ?? 'draft') }}
                                        </span>
                                        @if($section['is_required'] ?? true)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                Required
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                Optional
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">No sections yet</h3>
                        <div class="mt-1 text-sm text-yellow-700">
                            <p>Add sections below to build your course structure.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(isset($getState()['sections']) && count($getState()['sections']) > 0)
        <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Total sections:</span>
                <span class="font-medium text-gray-900">{{ count($getState()['sections']) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-600">Required sections:</span>
                <span class="font-medium text-gray-900">
                    {{ collect($getState()['sections'])->where('is_required', true)->count() }}
                </span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-600">Open sections:</span>
                <span class="font-medium text-gray-900">
                    {{ collect($getState()['sections'])->where('status', 'open')->count() }}
                </span>
            </div>
        </div>
    @endif
</div>