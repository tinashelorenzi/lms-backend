<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Enums\ContentFormat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentProcessor
{
    /**
     * Compile raw content into display-ready HTML
     */
    public function compile(LearningMaterial $material): string
    {
        $content = $material->content_raw ?? $material->content ?? '';
        
        switch ($material->content_format) {
            case ContentFormat::MARKDOWN:
                $content = $this->processMarkdown($content);
                break;
                
            case ContentFormat::BLOCK_EDITOR:
                $content = $this->processBlocks($material->content_blocks ?? []);
                break;
                
            case ContentFormat::PLAIN_TEXT:
                $content = nl2br(e($content));
                break;
                
            case ContentFormat::RICH_HTML:
            default:
                $content = $this->processRichHtml($content);
                break;
        }

        // Apply LaTeX processing if enabled
        if ($material->allow_latex) {
            $content = $this->processLatex($content);
        }

        // Process embedded media
        $content = $this->processEmbeddedMedia($content, $material->embedded_media ?? []);

        // Process web embeds if allowed
        if ($material->allow_embeds) {
            $content = $this->processWebEmbeds($content);
        }

        return $content;
    }

    /**
     * Process Markdown content
     */
    protected function processMarkdown(string $content): string
    {
        // Use a Markdown parser like CommonMark
        $converter = new \League\CommonMark\CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        return $converter->convertToHtml($content);
    }

    /**
     * Process block-based content
     */
    protected function processBlocks(array $blocks): string
    {
        $html = '';
        
        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }
        
        return $html;
    }

    /**
     * Render individual content block
     */
    protected function renderBlock(array $block): string
    {
        $type = $block['type'] ?? 'paragraph';
        $content = $block['content'] ?? '';
        $attributes = $block['attributes'] ?? [];
        
        return match($type) {
            'heading' => sprintf('<h%d>%s</h%d>', 
                $attributes['level'] ?? 2, 
                e($content), 
                $attributes['level'] ?? 2
            ),
            'paragraph' => sprintf('<p>%s</p>', $content),
            'video' => $this->renderVideoBlock($attributes),
            'image' => $this->renderImageBlock($attributes),
            'code' => sprintf('<pre><code class="%s">%s</code></pre>', 
                $attributes['language'] ?? '', 
                e($content)
            ),
            'latex' => sprintf('<div class="latex-block">%s</div>', $content),
            'embed' => $this->renderEmbedBlock($attributes),
            default => sprintf('<div class="unknown-block">%s</div>', e($content)),
        };
    }

    /**
     * Process LaTeX expressions
     */
    protected function processLatex(string $content): string
    {
        // Process inline LaTeX: $expression$
        $content = preg_replace_callback(
            '/\$([^$]+)\$/',
            fn($matches) => sprintf('<span class="latex-inline" data-latex="%s">%s</span>', 
                e($matches[1]), 
                e($matches[1])
            ),
            $content
        );

        // Process block LaTeX: $$expression$$
        $content = preg_replace_callback(
            '/\$\$([^$]+)\$\$/',
            fn($matches) => sprintf('<div class="latex-block" data-latex="%s">%s</div>', 
                e($matches[1]), 
                e($matches[1])
            ),
            $content
        );

        return $content;
    }

    /**
     * Process embedded media
     */
    protected function processEmbeddedMedia(string $content, array $media): string
    {
        foreach ($media as $item) {
            $placeholder = "{{media:{$item['id']}}}";
            $embed = $this->generateMediaEmbed($item);
            $content = str_replace($placeholder, $embed, $content);
        }
        
        return $content;
    }

    /**
     * Generate media embed HTML
     */
    protected function generateMediaEmbed(array $media): string
    {
        $type = $media['type'];
        $url = $media['url'];
        $title = $media['title'] ?? 'Media';
        
        return match($type) {
            'video' => $this->generateVideoEmbed($media),
            'image' => sprintf('<img src="%s" alt="%s" class="embedded-image" />', 
                e($url), 
                e($title)
            ),
            'audio' => sprintf('<audio controls><source src="%s" /></audio>', e($url)),
            'file' => sprintf('<a href="%s" class="file-link" target="_blank">%s</a>', 
                e($url), 
                e($title)
            ),
            default => sprintf('<div class="unknown-media">%s</div>', e($title)),
        };
    }

    /**
     * Generate video embed
     */
    protected function generateVideoEmbed(array $video): string
    {
        $platform = $video['platform'] ?? 'generic';
        $videoId = $video['video_id'] ?? '';
        $url = $video['url'];
        
        return match($platform) {
            'youtube' => sprintf(
                '<div class="video-embed youtube"><iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe></div>',
                e($videoId)
            ),
            'vimeo' => sprintf(
                '<div class="video-embed vimeo"><iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen></iframe></div>',
                e($videoId)
            ),
            default => sprintf(
                '<div class="video-embed generic"><video controls><source src="%s" /></video></div>',
                e($url)
            ),
        };
    }

    /**
     * Process web embeds (YouTube, Twitter, etc.)
     */
    protected function processWebEmbeds(string $content): string
    {
        // Auto-detect and convert URLs to embeds
        $patterns = [
            // YouTube
            '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/' => 
                '<div class="video-embed youtube"><iframe src="https://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe></div>',
            
            // Vimeo
            '/https?:\/\/(?:www\.)?vimeo\.com\/(\d+)/' => 
                '<div class="video-embed vimeo"><iframe src="https://player.vimeo.com/video/$1" frameborder="0" allowfullscreen></iframe></div>',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }

    /**
     * Render video block
     */
    protected function renderVideoBlock(array $attributes): string
    {
        return $this->generateVideoEmbed($attributes);
    }

    /**
     * Render image block
     */
    protected function renderImageBlock(array $attributes): string
    {
        $src = $attributes['src'] ?? '';
        $alt = $attributes['alt'] ?? '';
        $caption = $attributes['caption'] ?? '';
        
        $html = sprintf('<img src="%s" alt="%s" class="content-image" />', e($src), e($alt));
        
        if ($caption) {
            $html = sprintf('<figure class="image-figure">%s<figcaption>%s</figcaption></figure>', 
                $html, 
                e($caption)
            );
        }
        
        return $html;
    }

    /**
     * Render embed block
     */
    protected function renderEmbedBlock(array $attributes): string
    {
        $url = $attributes['url'] ?? '';
        $type = $attributes['embed_type'] ?? 'generic';
        
        return sprintf('<div class="web-embed %s" data-url="%s">%s</div>', 
            e($type), 
            e($url), 
            $this->generateEmbedContent($url, $type)
        );
    }

    /**
     * Generate embed content
     */
    protected function generateEmbedContent(string $url, string $type): string
    {
        // This would integrate with oEmbed providers or custom embed handlers
        return sprintf('<iframe src="%s" frameborder="0"></iframe>', e($url));
    }

    /**
     * Process rich HTML content
     */
    protected function processRichHtml(string $content): string
    {
        // Sanitize HTML while preserving safe elements
        // You might want to use HTMLPurifier or similar

        return $content;
    }

    public function analyzeContent(LearningMaterial $material): array
{
    $content = $material->content_raw ?? $material->content ?? '';
    $media = $material->embedded_media ?? [];
    
    return [
        'word_count' => str_word_count(strip_tags($content)),
        'character_count' => strlen($content),
        'estimated_reading_time' => ceil(str_word_count(strip_tags($content)) / 200),
        'paragraph_count' => substr_count($content, '</p>'),
        'heading_count' => preg_match_all('/<h[1-6]/', $content),
        'image_count' => collect($media)->where('type', 'image')->count(),
        'video_count' => collect($media)->where('type', 'video')->count(),
        'latex_expressions' => preg_match_all('/\$.*?\$/', $content),
        'external_links' => preg_match_all('/<a.*?href="http/', $content),
        'internal_links' => preg_match_all('/<a.*?href="(?!http)/', $content),
    ];
}

/**
 * Convert content between formats
 */
public function convertFormat(LearningMaterial $material, ContentFormat $targetFormat): bool
{
    $currentContent = $material->content_raw ?? $material->content ?? '';
    $currentFormat = $material->content_format ?? ContentFormat::RICH_HTML;
    
    if ($currentFormat === $targetFormat) {
        return true; // Already in target format
    }
    
    try {
        $convertedContent = $this->performFormatConversion(
            $currentContent, 
            $currentFormat, 
            $targetFormat
        );
        
        $material->update([
            'content_format' => $targetFormat,
            'content_raw' => $convertedContent,
            'content_compiled' => null, // Will be regenerated
        ]);
        
        return true;
    } catch (\Exception $e) {
        Log::error('Format conversion failed', [
            'material_id' => $material->id,
            'from' => $currentFormat->value,
            'to' => $targetFormat->value,
            'error' => $e->getMessage(),
        ]);
        
        return false;
    }
}

/**
 * Perform the actual format conversion
 */
protected function performFormatConversion(
    string $content, 
    ContentFormat $from, 
    ContentFormat $to
): string {
    // HTML to Markdown
    if ($from === ContentFormat::RICH_HTML && $to === ContentFormat::MARKDOWN) {
        return $this->htmlToMarkdown($content);
    }
    
    // Markdown to HTML
    if ($from === ContentFormat::MARKDOWN && $to === ContentFormat::RICH_HTML) {
        return $this->processMarkdown($content);
    }
    
    // To Plain Text
    if ($to === ContentFormat::PLAIN_TEXT) {
        return strip_tags($content);
    }
    
    // From Plain Text
    if ($from === ContentFormat::PLAIN_TEXT) {
        return match($to) {
            ContentFormat::RICH_HTML => nl2br(e($content)),
            ContentFormat::MARKDOWN => str_replace("\n", "\n\n", $content),
            default => $content,
        };
    }
    
    return $content; // Fallback
}

/**
 * Convert HTML to Markdown
 */
protected function htmlToMarkdown(string $html): string
{
    // Use a library like league/html-to-markdown
    $converter = new \League\HTMLToMarkdown\HtmlConverter();
    return $converter->convert($html);
}
}