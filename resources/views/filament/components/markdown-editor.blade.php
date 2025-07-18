@push('scripts')
<script src="https://unpkg.com/@tiptap/core@2.0.0/dist/index.umd.js"></script>
<script src="https://unpkg.com/@tiptap/starter-kit@2.0.0/dist/index.umd.js"></script>
<script src="https://unpkg.com/@tiptap/extension-underline@2.0.0/dist/index.umd.js"></script>
@endpush
<div 
    x-data="markdownEditor(@entangle($getStatePath()), {
        height: '{{ $height ?? '500px' }}',
        allowLatex: {{ $allowLatex ? 'true' : 'false' }},
        preview: {{ $preview ? 'true' : 'false' }},
        toolbar: @json($toolbar ?? [])
    })"
    x-init="init()"
    wire:ignore
    class="markdown-editor-wrapper border rounded-lg overflow-hidden"
>
    <!-- Toolbar -->
    <div class="markdown-toolbar border-b bg-gray-50 p-2 flex flex-wrap gap-1">
        <button type="button" @click="insertMarkdown('**', '**')" class="toolbar-btn">
            <strong>B</strong>
        </button>
        <button type="button" @click="insertMarkdown('*', '*')" class="toolbar-btn">
            <em>I</em>
        </button>
        <button type="button" @click="insertMarkdown('~~', '~~')" class="toolbar-btn">
            <s>S</s>
        </button>
        
        <div class="separator"></div>
        
        <button type="button" @click="insertMarkdown('# ', '')" class="toolbar-btn">H1</button>
        <button type="button" @click="insertMarkdown('## ', '')" class="toolbar-btn">H2</button>
        <button type="button" @click="insertMarkdown('### ', '')" class="toolbar-btn">H3</button>
        
        <div class="separator"></div>
        
        <button type="button" @click="insertMarkdown('- ', '')" class="toolbar-btn">• List</button>
        <button type="button" @click="insertMarkdown('1. ', '')" class="toolbar-btn">1. List</button>
        
        <div class="separator"></div>
        
        <button type="button" @click="insertLink()" class="toolbar-btn">🔗 Link</button>
        <button type="button" @click="insertImage()" class="toolbar-btn">📷 Image</button>
        <button type="button" @click="insertTable()" class="toolbar-btn">📋 Table</button>
        
        <div class="separator"></div>
        
        <button type="button" @click="insertMarkdown('`', '`')" class="toolbar-btn">Code</button>
        <button type="button" @click="insertMarkdown('> ', '')" class="toolbar-btn">Quote</button>
        
        <template x-if="allowLatex">
            <div class="flex gap-1">
                <div class="separator"></div>
                <button type="button" @click="insertLatex()" class="toolbar-btn">∑ LaTeX</button>
            </div>
        </template>
        
        <div class="separator"></div>
        
        <button type="button" @click="togglePreview()" 
                :class="{ 'bg-blue-100': showPreview }"
                class="toolbar-btn">
            👁 Preview
        </button>
        
        <button type="button" @click="toggleFullscreen()" class="toolbar-btn">⛶ Full</button>
    </div>

    <!-- Editor Area -->
    <div class="markdown-content flex" :style="{ height: height }">
        <!-- Editor Panel -->
        <div :class="showPreview ? 'w-1/2 border-r' : 'w-full'" class="relative">
            <textarea 
                x-ref="editor"
                x-model="content"
                @input="updateContent()"
                @keydown="handleKeydown($event)"
                class="w-full h-full p-4 font-mono text-sm resize-none border-0 focus:outline-none"
                placeholder="Enter Markdown content..."
            ></textarea>
        </div>
        
        <!-- Preview Panel -->
        <div x-show="showPreview" class="w-1/2 p-4 bg-gray-50 overflow-auto">
            <div x-html="previewHtml" class="prose max-w-none"></div>
        </div>
    </div>
</div>

<style>
.markdown-editor-wrapper .toolbar-btn {
    @apply px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors;
}

.markdown-editor-wrapper .separator {
    @apply w-px h-6 bg-gray-300 mx-1;
}

.markdown-editor-wrapper.fullscreen {
    @apply fixed inset-0 z-50 bg-white;
}

.markdown-editor-wrapper.fullscreen .markdown-content {
    height: calc(100vh - 60px) !important;
}
</style>

<script>
function markdownEditor(content, config) {
    return {
        content: content || '',
        showPreview: config.preview || false,
        previewHtml: '',
        
        init() {
            this.updatePreview();
            this.$watch('content', () => this.updatePreview());
        },
        
        updateContent() {
            // Sync with Livewire
            // The x-model already handles this
        },
        
, '');
            }
        },
        
        togglePreview() {
            this.showPreview = !this.showPreview;
        },
        
        toggleFullscreen() {
            this.$el.classList.toggle('fullscreen');
        },
        
        handleKeydown(event) {
            // Handle Tab key for indentation
            if (event.key === 'Tab') {
                event.preventDefault();
                const textarea = this.$refs.editor;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                
                this.content = this.content.substring(0, start) + '    ' + this.content.substring(end);
                
                this.$nextTick(() => {
                    textarea.setSelectionRange(start + 4, start + 4);
                });
            }
        }
    }
}
</script>