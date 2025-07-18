<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoService
{
    /**
     * Parse a video URL and extract platform and ID
     */
    public function parseVideoUrl(string $url): array
    {
        $url = trim($url);
        
        // YouTube patterns
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
            return [
                'platform' => 'youtube',
                'id' => $matches[1],
                'url' => $url,
            ];
        }
        
        // Vimeo patterns
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/i', $url, $matches)) {
            return [
                'platform' => 'vimeo',
                'id' => $matches[1],
                'url' => $url,
            ];
        }
        
        // Dailymotion patterns
        if (preg_match('/dailymotion\.com\/video\/([^_]+)/i', $url, $matches)) {
            return [
                'platform' => 'dailymotion',
                'id' => $matches[1],
                'url' => $url,
            ];
        }
        
        // Loom patterns
        if (preg_match('/loom\.com\/share\/([a-zA-Z0-9]+)/i', $url, $matches)) {
            return [
                'platform' => 'loom',
                'id' => $matches[1],
                'url' => $url,
            ];
        }
        
        // Google Drive patterns
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9-_]+)/i', $url, $matches)) {
            return [
                'platform' => 'google_drive',
                'id' => $matches[1],
                'url' => $url,
            ];
        }
        
        // Generic/Unknown platform
        return [
            'platform' => 'generic',
            'id' => null,
            'url' => $url,
        ];
    }

    /**
     * Get video metadata from various platforms
     */
    public function getVideoMetadata(string $platform, string $id): array
    {
        try {
            return match ($platform) {
                'youtube' => $this->getYouTubeMetadata($id),
                'vimeo' => $this->getVimeoMetadata($id),
                'dailymotion' => $this->getDailymotionMetadata($id),
                'loom' => $this->getLoomMetadata($id),
                'google_drive' => $this->getGoogleDriveMetadata($id),
                default => [],
            };
        } catch (Exception $e) {
            Log::warning("Failed to fetch video metadata for {$platform}:{$id}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get YouTube video metadata
     */
    protected function getYouTubeMetadata(string $id): array
    {
        // For now, return basic metadata
        // In production, you'd use YouTube API
        return [
            'thumbnail' => "https://img.youtube.com/vi/{$id}/maxresdefault.jpg",
            'embed_url' => "https://www.youtube.com/embed/{$id}",
            'platform_url' => "https://www.youtube.com/watch?v={$id}",
        ];
    }

    /**
     * Get Vimeo video metadata
     */
    protected function getVimeoMetadata(string $id): array
    {
        try {
            $response = Http::get("https://vimeo.com/api/v2/video/{$id}.json");
            
            if ($response->successful()) {
                $data = $response->json()[0] ?? [];
                return [
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'duration' => $data['duration'] ?? null,
                    'thumbnail' => $data['thumbnail_large'] ?? null,
                    'embed_url' => "https://player.vimeo.com/video/{$id}",
                    'platform_url' => "https://vimeo.com/{$id}",
                ];
            }
        } catch (Exception $e) {
            Log::warning("Failed to fetch Vimeo metadata for {$id}", ['error' => $e->getMessage()]);
        }

        return [
            'embed_url' => "https://player.vimeo.com/video/{$id}",
            'platform_url' => "https://vimeo.com/{$id}",
        ];
    }

    /**
     * Get Dailymotion video metadata
     */
    protected function getDailymotionMetadata(string $id): array
    {
        return [
            'thumbnail' => "https://www.dailymotion.com/thumbnail/video/{$id}",
            'embed_url' => "https://www.dailymotion.com/embed/video/{$id}",
            'platform_url' => "https://www.dailymotion.com/video/{$id}",
        ];
    }

    /**
     * Get Loom video metadata
     */
    protected function getLoomMetadata(string $id): array
    {
        return [
            'embed_url' => "https://www.loom.com/embed/{$id}",
            'platform_url' => "https://www.loom.com/share/{$id}",
        ];
    }

    /**
     * Get Google Drive video metadata
     */
    protected function getGoogleDriveMetadata(string $id): array
    {
        return [
            'embed_url' => "https://drive.google.com/file/d/{$id}/preview",
            'platform_url' => "https://drive.google.com/file/d/{$id}/view",
        ];
    }

    /**
     * Get embed URL for a video
     */
    public function getEmbedUrl(string $platform, string $id): string
    {
        return match ($platform) {
            'youtube' => "https://www.youtube.com/embed/{$id}",
            'vimeo' => "https://player.vimeo.com/video/{$id}",
            'dailymotion' => "https://www.dailymotion.com/embed/video/{$id}",
            'loom' => "https://www.loom.com/embed/{$id}",
            'google_drive' => "https://drive.google.com/file/d/{$id}/preview",
            default => '',
        };
    }

    /**
     * Get thumbnail URL for a video
     */
    public function getThumbnailUrl(string $platform, string $id): ?string
    {
        return match ($platform) {
            'youtube' => "https://img.youtube.com/vi/{$id}/maxresdefault.jpg",
            'dailymotion' => "https://www.dailymotion.com/thumbnail/video/{$id}",
            'vimeo' => null, // Would need API call
            'loom' => null,
            'google_drive' => null,
            default => null,
        };
    }

    /**
     * Validate if a URL is a supported video platform
     */
    public function isSupportedVideoUrl(string $url): bool
    {
        $parsed = $this->parseVideoUrl($url);
        return in_array($parsed['platform'], [
            'youtube', 'vimeo', 'dailymotion', 'loom', 'google_drive'
        ]);
    }
}