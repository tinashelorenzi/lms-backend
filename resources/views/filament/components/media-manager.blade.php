<div 
    x-data="mediaManager(@entangle($getStatePath()), {
        allowedTypes: @json($allowedTypes ?? []),
        maxFileSize: '{{ $maxFileSize ?? '10MB' }}',
        acceptedFormats: @json($acceptedFormats ?? [])
    })"
    x-init="init()"
    wire:ignore
    class="media-manager border rounded-lg overflow-hidden"
>
    <!-- Upload Area -->
    <div class="upload-area border-b p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium text-gray-800">Media Library</h3>
            <button type="button" @click="showUploadDialog = true" class="upload-btn">
                📁 Upload Files
            </button>
        </div>
        
        <!-- Drag & Drop Zone -->
        <div 
            @dragover.prevent
            @drop.prevent="handleDrop($event)"
            class="drop-zone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors"
        >
            <div class="text-gray-500">
                <div class="text-lg mb-2">📁</div>
                <div>Drag & drop files here or click to browse</div>
                <div class="text-sm mt-1">Max size: {{ $maxFileSize }}</div>
            </div>
            <input type="file" @change="handleFileSelect($event)" multiple class="hidden" x-ref="fileInput">
        </div>
    </div>
    
    <!-- Media Grid -->
    <div class="media-grid p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <template x-for="(item, index) in mediaItems" :key="item.id">
                <div class="media-item relative group border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Image Preview -->
                    <template x-if="item.type === 'image'">
                        <div class="aspect-square bg-gray-100">
                            <img :src="item.url" :alt="item.title" class="w-full h-full object-cover">
                        </div>
                    </template>
                    
                    <!-- Video Preview -->
                    <template x-if="item.type === 'video'">
                        <div class="aspect-square bg-gray-100 flex items-center justify-center">
                            <div class="text-4xl">🎥</div>
                        </div>
                    </template>
                    
                    <!-- File Preview -->
                    <template x-if="!['image', 'video'].includes(item.type)">
                        <div class="aspect-square bg-gray-100 flex items-center justify-center">
                            <div class="text-4xl">📄</div>
                        </div>
                    </template>
                    
                    <!-- Item Info -->
                    <div class="p-2">
                        <div class="text-xs font-medium truncate" x-text="item.title"></div>
                        <div class="text-xs text-gray-500" x-text="item.type"></div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button type="button" @click="insertMedia(item)" class="action-btn">
                            ➕ Insert
                        </button>
                        <button type="button" @click="deleteMedia(index)" class="action-btn text-red-400">
                            🗑 Delete
                        </button>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Empty State -->
        <template x-if="mediaItems.length === 0">
            <div class="text-center py-8 text-gray-500">
                <div class="text-lg mb-2">No media files</div>
                <div class="text-sm">Upload files to get started</div>
            </div>
        </template>
    </div>
</div>

<style>
.media-manager .upload-btn,
.media-manager .action-btn {
    @apply px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors;
}

.media-manager .drop-zone.dragover {
    @apply border-blue-400 bg-blue-50;
}
</style>

<script>
function mediaManager(content, config) {
    return {
        mediaItems: [],
        showUploadDialog: false,
        
        init() {
            this.mediaItems = Array.isArray(content) ? content : [];
        },
        
        handleDrop(event) {
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },
        
        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
        },
        
        async processFiles(files) {
            for (const file of files) {
                if (this.validateFile(file)) {
                    await this.uploadFile(file);
                }
            }
        },
        
        validateFile(file) {
            // Check file size
            const maxSize = this.parseFileSize(this.maxFileSize);
            if (file.size > maxSize) {
                alert(`File "${file.name}" is too large. Maximum size is ${this.maxFileSize}.`);
                return false;
            }
            
            // Check file type
            const extension = file.name.split('.').pop().toLowerCase();
            const allowedExtensions = Object.values(this.acceptedFormats).flat();
            
            if (!allowedExtensions.includes(extension)) {
                alert(`File type "${extension}" is not allowed.`);
                return false;
            }
            
            return true;
        },
        
        parseFileSize(sizeStr) {
            const units = { 'B': 1, 'KB': 1024, 'MB': 1024 * 1024, 'GB': 1024 * 1024 * 1024 };
            const match = sizeStr.match(/^(\d+)\s*(B|KB|MB|GB)$/i);
            return match ? parseInt(match[1]) * units[match[2].toUpperCase()] : 0;
        },
        
        async uploadFile(file) {
            try {
                // Create a preview URL
                const url = URL.createObjectURL(file);
                
                // Determine file type
                const type = this.getFileType(file);
                
                const mediaItem = {
                    id: Date.now() + Math.random(),
                    title: file.name,
                    type: type,
                    url: url,
                    size: file.size,
                    file: file // Store file for actual upload later
                };
                
                this.mediaItems.push(mediaItem);
                this.updateContent();
                
                // In a real implementation, you would upload to your server here
                // and replace the blob URL with the actual server URL
                
            } catch (error) {
                console.error('Upload failed:', error);
                alert('Upload failed. Please try again.');
            }
        },
        
        getFileType(file) {
            const mimeType = file.type;
            
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'audio';
            
            return 'file';
        },
        
        insertMedia(item) {
            // This would trigger an event that the parent editor can listen to
            this.$dispatch('media-inserted', { media: item });
        },
        
        deleteMedia(index) {
            if (confirm('Are you sure you want to delete this media file?')) {
                // Revoke the object URL to free memory
                const item = this.mediaItems[index];
                if (item.url.startsWith('blob:')) {
                    URL.revokeObjectURL(item.url);
                }
                
                this.mediaItems.splice(index, 1);
                this.updateContent();
            }
        },
        
        updateContent() {
            // Sync with Livewire
            // The entangled property will handle this automatically
        }
    }
}
</script>