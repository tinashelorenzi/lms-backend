<div class="content-stats grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-blue-600">{{ $stats['word_count'] ?? 0 }}</div>
        <div class="stat-label text-sm text-gray-600">Words</div>
    </div>
    
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-green-600">{{ $stats['estimated_reading_time'] ?? 0 }}</div>
        <div class="stat-label text-sm text-gray-600">Minutes to Read</div>
    </div>
    
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-purple-600">{{ $stats['image_count'] ?? 0 }}</div>
        <div class="stat-label text-sm text-gray-600">Images</div>
    </div>
    
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-red-600">{{ $stats['video_count'] ?? 0 }}</div>
        <div class="stat-label text-sm text-gray-600">Videos</div>
    </div>
    
    @if(($stats['latex_expressions'] ?? 0) > 0)
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-orange-600">{{ $stats['latex_expressions'] }}</div>
        <div class="stat-label text-sm text-gray-600">LaTeX Expressions</div>
    </div>
    @endif
    
    @if(($stats['external_links'] ?? 0) > 0)
    <div class="stat-item text-center">
        <div class="stat-value text-2xl font-bold text-teal-600">{{ $stats['external_links'] }}</div>
        <div class="stat-label text-sm text-gray-600">External Links</div>
    </div>
    @endif
</div>