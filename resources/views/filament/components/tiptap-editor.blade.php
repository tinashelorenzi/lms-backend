@push('scripts')
<script src="https://unpkg.com/@tiptap/core@2.0.0/dist/index.umd.js"></script>
<script src="https://unpkg.com/@tiptap/starter-kit@2.0.0/dist/index.umd.js"></script>
<script src="https://unpkg.com/@tiptap/extension-underline@2.0.0/dist/index.umd.js"></script>
@endpush
<div 
    x-data="tiptapEditor(@entangle($getStatePath()), {
        toolbar: @json($toolbar ?? []),
        height: '{{ $height ?? '500px' }}',
        allowLatex: {{ $allowLatex ? 'true' : 'false' }},
        allowEmbeds: {{ $allowEmbeds ? 'true' : 'false' }},
    })"
    x-init="init()"
    wire:ignore
    class="tiptap-editor-wrapper"
>
    <!-- Toolbar -->
    <div class="tiptap-toolbar border-b border-gray-200 p-2 flex flex-wrap gap-1 bg-gray-50">
        <!-- Basic formatting -->
        <div class="flex gap-1">
            <button type="button" @click="editor.chain().focus().toggleBold().run()" 
                    :class="{ 'bg-blue-100': editor.isActive('bold') }"
                    class="p-2 rounded hover:bg-gray-100">
                <strong>B</strong>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" 
                    :class="{ 'bg-blue-100': editor.isActive('italic') }"
                    class="p-2 rounded hover:bg-gray-100">
                <em>I</em>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" 
                    :class="{ 'bg-blue-100': editor.isActive('underline') }"
                    class="p-2 rounded hover:bg-gray-100">
                <u>U</u>
            </button>
        </div>

        <div class="w-px h-6 bg-gray-300 mx-2"></div>

        <!-- Headings -->
        <div class="flex gap-1">
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" 
                    :class="{ 'bg-blue-100': editor.isActive('heading', { level: 1 }) }"
                    class="p-2 rounded hover:bg-gray-100">
                H1
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" 
                    :class="{ 'bg-blue-100': editor.isActive('heading', { level: 2 }) }"
                    class="p-2 rounded hover:bg-gray-100">
                H2
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" 
                    :class="{ 'bg-blue-100': editor.isActive('heading', { level: 3 }) }"
                    class="p-2 rounded hover:bg-gray-100">
                H3
            </button>
        </div>

        <div class="w-px h-6 bg-gray-300 mx-2"></div>

        <!-- Lists -->
        <div class="flex gap-1">
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" 
                    :class="{ 'bg-blue-100': editor.isActive('bulletList') }"
                    class="p-2 rounded hover:bg-gray-100">
                • List
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" 
                    :class="{ 'bg-blue-100': editor.isActive('orderedList') }"
                    class="p-2 rounded hover:bg-gray-100">
                1. List
            </button>
        </div>

        <div class="w-px h-6 bg-gray-300 mx-2"></div>

        <!-- Media -->
        <div class="flex gap-1">
            <button type="button" @click="addImage()" class="p-2 rounded hover:bg-gray-100">
                📷 Image
            </button>
            <button type="button" @click="addVideo()" class="p-2 rounded hover:bg-gray-100">
                🎥 Video
            </button>
        </div>

        <template x-if="allowLatex">
            <div class="flex gap-1">
                <div class="w-px h-6 bg-gray-300 mx-2"></div>
                <button type="button" @click="addLatex()" class="p-2 rounded hover:bg-gray-100">
                    ∑ LaTeX
                </button>
            </div>
        </template>

        <div class="w-px h-6 bg-gray-300 mx-2"></div>

        <!-- Source mode -->
        <div class="flex gap-1">
            <button type="button" @click="toggleSourceMode()" 
                    :class="{ 'bg-blue-100': sourceMode }"
                    class="p-2 rounded hover:bg-gray-100">
                &lt;/&gt; Source
            </button>
            <button type="button" @click="toggleFullscreen()" class="p-2 rounded hover:bg-gray-100">
                ⛶ Full
            </button>
        </div>
    </div>

    <!-- Editor Content -->
    <div class="relative">
        <div x-show="!sourceMode" x-ref="editor" class="tiptap-content prose max-w-none p-4 min-h-96 border-0 focus:outline-none"></div>
        <textarea 
            x-show="sourceMode" 
            x-model="sourceContent"
            @input="updateFromSource()"
            class="w-full p-4 font-mono text-sm border-0 focus:outline-none resize-none"
            :style="{ height: height }"
            placeholder="Enter HTML source code..."
        ></textarea>
    </div>
</div>

<style>
.tiptap-content {
    min-height: 400px;
}

.tiptap-content:focus {
    outline: none;
}

.tiptap-content .latex-inline {
    background: #f0f8ff;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Times New Roman', serif;
}

.tiptap-content .latex-block {
    background: #f8f9fa;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
    border-left: 4px solid #007bff;
    font-family: 'Times New Roman', serif;
}

.tiptap-content .video-embed {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    margin: 15px 0;
}

.tiptap-content .video-embed iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 8px;
}
</style>

<script>
function tiptapEditor(content, config) {
    return {
        editor: null,
        sourceMode: false,
        sourceContent: '',
        content: content,
        
        init() {
            // Initialize TipTap editor
            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit,
                    Image,
                    Link,
                    Underline,
                    Table.configure({
                        resizable: true,
                    }),
                    TableRow,
                    TableHeader,
                    TableCell,
                    // Custom LaTeX extension would go here
                    // Custom video embed extension would go here
                ],
                content: this.content || '',
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                    this.sourceContent = editor.getHTML();
                }
            });
            
            this.sourceContent = this.content || '';
        },
        
        toggleSourceMode() {
            this.sourceMode = !this.sourceMode;
            if (this.sourceMode) {
                this.sourceContent = this.editor.getHTML();
            } else {
                this.editor.commands.setContent(this.sourceContent);
            }
        },
        
        updateFromSource() {
            if (this.sourceMode) {
                this.content = this.sourceContent;
            }
        },
        
        addImage() {
            const url = prompt('Enter image URL:');
            if (url) {
                this.editor.chain().focus().setImage({ src: url }).run();
            }
        },
        
        addVideo() {
            const url = prompt('Enter video URL (YouTube, Vimeo, etc.):');
            if (url) {
                // Process video URL and create embed
                this.insertVideoEmbed(url);
            }
        },
        
        addLatex() {
            const latex = prompt('Enter LaTeX expression:');
            if (latex) {
                const latexHtml = `<span class="latex-inline" data-latex="${latex}">${latex}</span>`;
                this.editor.chain().focus().insertContent(latexHtml).run();
            }
        },
        
        insertVideoEmbed(url) {
            let embedHtml = '';
            
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                const videoId = this.extractYouTubeId(url);
                embedHtml = `<div class="video-embed youtube"><iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe></div>`;
            } else if (url.includes('vimeo.com')) {
                const videoId = this.extractVimeoId(url);
                embedHtml = `<div class="video-embed vimeo"><iframe src="https://player.vimeo.com/video/${videoId}" frameborder="0" allowfullscreen></iframe></div>`;
            } else {
                embedHtml = `<div class="video-embed generic"><video controls><source src="${url}" /></video></div>`;
            }
            
            this.editor.chain().focus().insertContent(embedHtml).run();
        },
        
        extractYouTubeId(url) {
            const regex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/;
            const match = url.match(regex);
            return match ? match[1] : '';
        },
        
        extractVimeoId(url) {
            const regex = /vimeo\.com\/(\d+)/;
            const match = url.match(regex);
            return match ? match[1] : '';
        },
        
        toggleFullscreen() {
            const wrapper = this.$el.closest('.tiptap-editor-wrapper');
            wrapper.classList.toggle('tiptap-fullscreen');
        }
    }
}
</script>