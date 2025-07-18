<div class="content-preview border rounded-lg overflow-hidden">
    <div class="preview-header bg-gray-50 border-b p-3 flex items-center justify-between">
        <h3 class="font-medium text-gray-800">Content Preview</h3>
        <div class="flex gap-2">
            <button type="button" class="preview-btn" onclick="window.open('{{ route('learning-materials.preview', $material ?? 0) }}', '_blank')">
                🔗 Open in New Tab
            </button>
            <button type="button" class="preview-btn" onclick="this.closest('.content-preview').querySelector('.preview-content').requestFullscreen()">
                ⛶ Fullscreen
            </button>
        </div>
    </div>
    
    <div class="preview-content p-6 bg-white min-h-96 max-h-96 overflow-y-auto">
        @if($material && $processor)
            <div class="prose max-w-none">
                {!! $processor->compile($material) !!}
            </div>
        @else
            <div class="text-center text-gray-500 py-12">
                <div class="text-lg mb-2">No content to preview</div>
                <div class="text-sm">Save the material to see the preview</div>
            </div>
        @endif
    </div>
</div>

<style>
.content-preview .preview-btn {
    @apply px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors;
}

.content-preview .prose {
    /* Custom styling for the preview content */
}

.content-preview .latex-inline {
    background: #f0f8ff;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Times New Roman', serif;
}

.content-preview .latex-block {
    background: #f8f9fa;
    padding: 15px;
    margin: 15px 0;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    font-family: 'Times New Roman', serif;
}

.content-preview .video-embed {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    margin: 20px 0;
}

.content-preview .video-embed iframe,
.content-preview .video-embed video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 8px;
}
</style>