<div 
    x-data="blockEditor(@entangle($getStatePath()), {
        allowedBlocks: @json($allowedBlocks ?? []),
        allowLatex: {{ $allowLatex ? 'true' : 'false' }},
        allowEmbeds: {{ $allowEmbeds ? 'true' : 'false' }}
    })"
    x-init="init()"
    wire:ignore
    class="block-editor-wrapper border rounded-lg overflow-hidden"
>
    <!-- Toolbar -->
    <div class="block-toolbar border-b bg-gray-50 p-3 flex flex-wrap gap-2">
        <span class="text-sm font-medium text-gray-600">Add Block:</span>
        
        <button type="button" @click="addBlock('paragraph')" class="block-btn">
            📝 Paragraph
        </button>
        <button type="button" @click="addBlock('heading')" class="block-btn">
            📰 Heading
        </button>
        <button type="button" @click="addBlock('image')" class="block-btn">
            📷 Image
        </button>
        <button type="button" @click="addBlock('video')" class="block-btn">
            🎥 Video
        </button>
        <button type="button" @click="addBlock('code')" class="block-btn">
            💻 Code
        </button>
        <button type="button" @click="addBlock('quote')" class="block-btn">
            💬 Quote
        </button>
        <button type="button" @click="addBlock('list')" class="block-btn">
            📋 List
        </button>
        
        <template x-if="allowLatex">
            <button type="button" @click="addBlock('latex')" class="block-btn">
                ∑ LaTeX
            </button>
        </template>
        
        <template x-if="allowEmbeds">
            <button type="button" @click="addBlock('embed')" class="block-btn">
                🔗 Embed
            </button>
        </template>
    </div>

    <!-- Blocks Container -->
    <div class="blocks-container p-4 min-h-96" x-ref="container">
        <template x-for="(block, index) in blocks" :key="block.id">
            <div class="block-wrapper mb-4 group relative" :data-block-id="block.id">
                <!-- Block Controls -->
                <div class="block-controls absolute -left-12 top-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="flex flex-col gap-1">
                        <button type="button" @click="moveBlockUp(index)" 
                                :disabled="index === 0"
                                class="control-btn text-xs" title="Move Up">
                            ↑
                        </button>
                        <button type="button" @click="moveBlockDown(index)" 
                                :disabled="index === blocks.length - 1"
                                class="control-btn text-xs" title="Move Down">
                            ↓
                        </button>
                        <button type="button" @click="deleteBlock(index)" 
                                class="control-btn text-xs text-red-600" title="Delete">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Block Content -->
                <div class="block-content border rounded-lg p-4 hover:border-blue-300 transition-colors">
                    <!-- Paragraph Block -->
                    <template x-if="block.type === 'paragraph'">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Paragraph</label>
                            <textarea 
                                x-model="block.content"
                                @input="updateBlock(index)"
                                rows="4"
                                class="w-full border rounded-md p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter paragraph content..."
                            ></textarea>
                        </div>
                    </template>

                    <!-- Heading Block -->
                    <template x-if="block.type === 'heading'">
                        <div>
                            <div class="flex items-center gap-4 mb-2">
                                <label class="text-sm font-medium text-gray-600">Heading</label>
                                <select x-model="block.attributes.level" @change="updateBlock(index)"
                                        class="text-sm border rounded px-2 py-1">
                                    <option value="1">H1</option>
                                    <option value="2">H2</option>
                                    <option value="3">H3</option>
                                    <option value="4">H4</option>
                                </select>
                            </div>
                            <input 
                                x-model="block.content"
                                @input="updateBlock(index)"
                                type="text"
                                class="w-full border rounded-md p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter heading text..."
                            />
                        </div>
                    </template>

                    <!-- Image Block -->
                    <template x-if="block.type === 'image'">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Image</label>
                            <div class="space-y-3">
                                <input 
                                    x-model="block.attributes.src"
                                    @input="updateBlock(index)"
                                    type="url"
                                    class="w-full border rounded-md p-3"
                                    placeholder="Image URL..."
                                />
                                <input 
                                    x-model="block.attributes.alt"
                                    @input="updateBlock(index)"
                                    type="text"
                                    class="w-full border rounded-md p-3"
                                    placeholder="Alt text..."
                                />
                                <input 
                                    x-model="block.attributes.caption"
                                    @input="updateBlock(index)"
                                    type="text"
                                    class="w-full border rounded-md p-3"
                                    placeholder="Caption (optional)..."
                                />
                                <template x-if="block.attributes.src">
                                    <div class="mt-3">
                                        <img :src="block.attributes.src" :alt="block.attributes.alt" 
                                             class="max-w-full h-auto rounded-lg border" />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Video Block -->
                    <template x-if="block.type === 'video'">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Video</label>
                            <div class="space-y-3">
                                <input 
                                    x-model="block.attributes.url"
                                    @input="updateVideoBlock(index)"
                                    type="url"
                                    class="w-full border rounded-md p-3"
                                    placeholder="Video URL (YouTube, Vimeo, etc.)..."
                                />
                                <input 
                                    x-model="block.attributes.title"
                                    @input="updateBlock(index)"
                                    type="text"
                                    class="w-full border rounded-md p-3"
                                    placeholder="Video title..."
                                />
                                <template x-if="block.attributes.embed_html">
                                    <div class="mt-3 bg-gray-100 p-4 rounded-lg">
                                        <div x-html="block.attributes.embed_html"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Code Block -->
                    <template x-if="block.type === 'code'">
                        <div>
                            <div class="flex items-center gap-4 mb-2">
                                <label class="text-sm font-medium text-gray-600">Code</label>
                                <select x-model="block.attributes.language" @change="updateBlock(index)"
                                        class="text-sm border rounded px-2 py-1">
                                    <option value="">Select language...</option>
                                    <option value="javascript">JavaScript</option>
                                    <option value="php">PHP</option>
                                    <option value="python">Python</option>
                                    <option value="html">HTML</option>
                                    <option value="css">CSS</option>
                                    <option value="sql">SQL</option>
                                    <option value="json">JSON</option>
                                </select>
                            </div>
                            <textarea 
                                x-model="block.content"
                                @input="updateBlock(index)"
                                rows="8"
                                class="w-full border rounded-md p-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter code..."
                            ></textarea>
                        </div>
                    </template>

                    <!-- LaTeX Block -->
                    <template x-if="block.type === 'latex'">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">LaTeX Expression</label>
                            <textarea 
                                x-model="block.content"
                                @input="updateBlock(index)"
                                rows="4"
                                class="w-full border rounded-md p-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter LaTeX expression..."
                            ></textarea>
                            <div class="mt-2 p-3 bg-blue-50 rounded-lg border">
                                <div class="text-sm text-gray-600 mb-1">Preview:</div>
                                <div class="latex-preview font-serif" x-text="block.content"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Embed Block -->
                    <template x-if="block.type === 'embed'">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Web Embed</label>
                            <div class="space-y-3">
                                <input 
                                    x-model="block.attributes.url"
                                    @input="updateEmbedBlock(index)"
                                    type="url"
                                    class="w-full border rounded-md p-3"
                                    placeholder="URL to embed..."
                                />
                                <select x-model="block.attributes.embed_type" @change="updateBlock(index)"
                                        class="border rounded px-3 py-2">
                                    <option value="auto">Auto-detect</option>
                                    <option value="iframe">Generic iframe</option>
                                    <option value="oembed">oEmbed</option>
                                </select>
                                <template x-if="block.attributes.preview_html">
                                    <div class="mt-3 bg-gray-100 p-4 rounded-lg">
                                        <div x-html="block.attributes.preview_html"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="blocks.length === 0">
            <div class="text-center py-12 text-gray-500">
                <div class="text-lg mb-2">No blocks yet</div>
                <div class="text-sm">Click "Add Block" above to start creating content</div>
            </div>
        </template>
    </div>
</div>

<style>
.block-editor-wrapper .block-btn {
    @apply px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors;
}

.block-editor-wrapper .control-btn {
    @apply w-6 h-6 bg-white border border-gray-300 rounded text-center leading-none hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

.block-editor-wrapper .latex-preview {
    font-family: 'Times New Roman', serif;
    font-size: 16px;
}
</style>

<script>
function blockEditor(content, config) {
    return {
        blocks: [],
        
        init() {
            this.blocks = Array.isArray(content) ? content : [];
            if (this.blocks.length === 0) {
                this.addBlock('paragraph'); // Start with a paragraph
            }
        },
        
        addBlock(type) {
            const block = {
                id: Date.now() + Math.random(),
                type: type,
                content: '',
                attributes: this.getDefaultAttributes(type)
            };
            
            this.blocks.push(block);
            this.updateContent();
        },
        
        getDefaultAttributes(type) {
            const defaults = {
                heading: { level: 2 },
                image: { src: '', alt: '', caption: '' },
                video: { url: '', title: '', platform: '', video_id: '', embed_html: '' },
                code: { language: '' },
                latex: {},
                embed: { url: '', embed_type: 'auto', preview_html: '' },
                list: { style: 'bullet' },
                quote: {}
            };
            
            return defaults[type] || {};
        },
        
        updateBlock(index) {
            this.updateContent();
        },
        
        updateVideoBlock(index) {
            const block = this.blocks[index];
            const url = block.attributes.url;
            
            if (url) {
                // Detect platform and generate embed
                if (url.includes('youtube.com') || url.includes('youtu.be')) {
                    const videoId = this.extractYouTubeId(url);
                    block.attributes.platform = 'youtube';
                    block.attributes.video_id = videoId;
                    block.attributes.embed_html = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen style="width:100%;height:315px;"></iframe>`;
                } else if (url.includes('vimeo.com')) {
                    const videoId = this.extractVimeoId(url);
                    block.attributes.platform = 'vimeo';
                    block.attributes.video_id = videoId;
                    block.attributes.embed_html = `<iframe src="https://player.vimeo.com/video/${videoId}" frameborder="0" allowfullscreen style="width:100%;height:315px;"></iframe>`;
                } else {
                    block.attributes.platform = 'generic';
                    block.attributes.embed_html = `<video controls style="width:100%;max-height:315px;"><source src="${url}"></video>`;
                }
            }
            
            this.updateContent();
        },
        
        updateEmbedBlock(index) {
            const block = this.blocks[index];
            const url = block.attributes.url;
            
            if (url) {
                // Generate preview based on URL
                block.attributes.preview_html = `<iframe src="${url}" style="width:100%;height:200px;border:1px solid #ddd;"></iframe>`;
            }
            
            this.updateContent();
        },
        
        deleteBlock(index) {
            if (confirm('Are you sure you want to delete this block?')) {
                this.blocks.splice(index, 1);
                this.updateContent();
            }
        },
        
        moveBlockUp(index) {
            if (index > 0) {
                const block = this.blocks.splice(index, 1)[0];
                this.blocks.splice(index - 1, 0, block);
                this.updateContent();
            }
        },
        
        moveBlockDown(index) {
            if (index < this.blocks.length - 1) {
                const block = this.blocks.splice(index, 1)[0];
                this.blocks.splice(index + 1, 0, block);
                this.updateContent();
            }
        },
        
        updateContent() {
            // Sync with Livewire - the entangled property will handle this
            // Just make sure the blocks array is updated
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
        }
    }
}
</script>