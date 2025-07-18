<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $material->title }} - Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [[', ']],
                displayMath: [['$', '$']]
            }
        };
    </script>
    <style>
        .content-container {
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .latex-inline {
            background: #f0f8ff;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Times New Roman', serif;
        }
        
        .latex-block {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            font-family: 'Times New Roman', serif;
        }
        
        .video-embed {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            margin: 20px 0;
        }
        
        .video-embed iframe,
        .video-embed video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }
        
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        blockquote {
            border-left: 4px solid #ddd;
            margin: 15px 0;
            padding-left: 15px;
            color: #666;
            font-style: italic;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $material->title }}</h1>
                    @if($material->description)
                        <p class="text-gray-600 mt-1">{{ $material->description }}</p>
                    @endif
                </div>
                <div class="flex gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $material->content_format->label() }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        {{ $material->estimated_time }} min
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-sm p-8">
            <div class="content-container prose prose-lg max-w-none">
                {!! $compiledContent !!}
            </div>
        </div>
    </main>

    <!-- Stats Sidebar -->
    <aside class="fixed top-20 right-4 w-64 bg-white rounded-lg shadow-sm p-4 border">
        <h3 class="font-semibold text-gray-800 mb-4">Content Statistics</h3>
        
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Words:</span>
                <span class="text-sm font-medium">{{ $stats['word_count'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Reading time:</span>
                <span class="text-sm font-medium">{{ $stats['estimated_reading_time'] ?? 0 }} min</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Paragraphs:</span>
                <span class="text-sm font-medium">{{ $stats['paragraph_count'] ?? 0 }}</span>
            </div>
            
            @if(($stats['image_count'] ?? 0) > 0)
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Images:</span>
                <span class="text-sm font-medium">{{ $stats['image_count'] }}</span>
            </div>
            @endif
            
            @if(($stats['video_count'] ?? 0) > 0)
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Videos:</span>
                <span class="text-sm font-medium">{{ $stats['video_count'] }}</span>
            </div>
            @endif
            
            @if(($stats['latex_expressions'] ?? 0) > 0)
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">LaTeX:</span>
                <span class="text-sm font-medium">{{ $stats['latex_expressions'] }}</span>
            </div>
            @endif
            
            @if(($stats['external_links'] ?? 0) > 0)
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">External links:</span>
                <span class="text-sm font-medium">{{ $stats['external_links'] }}</span>
            </div>
            @endif
        </div>
        
        <div class="mt-6 pt-4 border-t">
            <div class="flex gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $material->allow_latex ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ $material->allow_latex ? '✓' : '✗' }} LaTeX
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $material->allow_embeds ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ $material->allow_embeds ? '✓' : '✗' }} Embeds
                </span>
            </div>
        </div>
    </aside>

    <!-- Footer -->
    <footer class="mt-16 py-8 border-t bg-white">
        <div class="max-w-4xl mx-auto px-4 text-center text-sm text-gray-500">
            <p>Learning Material Preview • Created {{ $material->created_at->format('M j, Y') }}</p>
        </div>
    </footer>

    <script>
        // Initialize MathJax for LaTeX rendering
        if (window.MathJax) {
            MathJax.typesetPromise().then(() => {
                console.log('LaTeX expressions rendered');
            }).catch(err => {
                console.error('LaTeX rendering failed:', err);
            });
        }
        
        // Add click handlers for embedded media
        document.addEventListener('DOMContentLoaded', function() {
            // Handle video play/pause
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                video.addEventListener('loadedmetadata', function() {
                    console.log(`Video loaded: ${this.duration}s`);
                });
            });
            
            // Handle image lazy loading
            const images = document.querySelectorAll('img[data-src]');
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                images.forEach(img => imageObserver.observe(img));
            }
        });
    </script>
</body>
</html>