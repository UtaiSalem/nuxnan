<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostLinkPreview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LinkPreviewService
{
    protected int $timeout = 5;
    protected int $cacheMinutes = 60;

    /**
     * Extract URLs from content and create link preview for the first one.
     */
    public function extractAndSaveLinkPreview(Post $post, string $content): ?PostLinkPreview
    {
        $url = $this->extractFirstUrl($content);
        
        if (!$url) {
            return null;
        }

        // Check cache first
        $cacheKey = 'link_preview_' . md5($url);
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            return $this->saveLinkPreview($post, $cachedData);
        }

        // Fetch and parse the URL
        $data = $this->fetchLinkPreview($url);
        
        if ($data) {
            Cache::put($cacheKey, $data, now()->addMinutes($this->cacheMinutes));
            return $this->saveLinkPreview($post, $data);
        }

        return null;
    }

    /**
     * Extract the first URL from content.
     */
    public function extractFirstUrl(string $content): ?string
    {
        $pattern = '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/';
        
        if (preg_match($pattern, $content, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract all URLs from content.
     */
    public function extractAllUrls(string $content): array
    {
        $pattern = '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/';
        
        preg_match_all($pattern, $content, $matches);
        
        return $matches[0] ?? [];
    }

    /**
     * Fetch Open Graph and meta data from URL.
     */
    public function fetchLinkPreview(string $url): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; NuxniBot/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            return $this->parseHtml($html, $url);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch link preview', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse HTML and extract Open Graph / meta data.
     */
    protected function parseHtml(string $html, string $url): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);

        $data = [
            'url' => $url,
            'title' => $this->getMetaContent($xpath, 'og:title') 
                       ?? $this->getTitle($xpath),
            'description' => $this->getMetaContent($xpath, 'og:description') 
                             ?? $this->getMetaContent($xpath, 'description'),
            'image_url' => $this->resolveUrl(
                $this->getMetaContent($xpath, 'og:image'),
                $url
            ),
            'site_name' => $this->getMetaContent($xpath, 'og:site_name'),
            'type' => $this->getMetaContent($xpath, 'og:type') ?? 'website',
            'site_icon' => $this->resolveFavicon($xpath, $url),
        ];

        // Video data for video types (YouTube, Vimeo, etc.)
        $videoUrl = $this->getMetaContent($xpath, 'og:video:url') 
                    ?? $this->getMetaContent($xpath, 'og:video');
        
        if ($videoUrl) {
            $data['video_url'] = $videoUrl;
            $data['video_type'] = $this->getMetaContent($xpath, 'og:video:type');
            $data['video_width'] = $this->getMetaContent($xpath, 'og:video:width');
            $data['video_height'] = $this->getMetaContent($xpath, 'og:video:height');
        }

        // Author information
        $data['author_name'] = $this->getMetaContent($xpath, 'article:author') 
                               ?? $this->getMetaContent($xpath, 'author');
        
        // Provider information (for Twitter cards)
        $data['provider_name'] = $this->getMetaContent($xpath, 'twitter:site');

        return $data;
    }

    /**
     * Get meta content by property or name.
     */
    protected function getMetaContent(\DOMXPath $xpath, string $name): ?string
    {
        // Try property first (Open Graph)
        $node = $xpath->query("//meta[@property='{$name}']/@content")->item(0);
        if ($node) {
            return trim($node->nodeValue);
        }

        // Try name (standard meta)
        $node = $xpath->query("//meta[@name='{$name}']/@content")->item(0);
        if ($node) {
            return trim($node->nodeValue);
        }

        return null;
    }

    /**
     * Get page title.
     */
    protected function getTitle(\DOMXPath $xpath): ?string
    {
        $node = $xpath->query('//title')->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    /**
     * Get favicon URL.
     */
    protected function resolveFavicon(\DOMXPath $xpath, string $baseUrl): ?string
    {
        $selectors = [
            "//link[@rel='icon']/@href",
            "//link[@rel='shortcut icon']/@href",
            "//link[@rel='apple-touch-icon']/@href",
        ];

        foreach ($selectors as $selector) {
            $node = $xpath->query($selector)->item(0);
            if ($node) {
                return $this->resolveUrl($node->nodeValue, $baseUrl);
            }
        }

        // Default to /favicon.ico
        $parsed = parse_url($baseUrl);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . '/favicon.ico';
    }

    /**
     * Resolve relative URL to absolute.
     */
    protected function resolveUrl(?string $url, string $baseUrl): ?string
    {
        if (!$url) {
            return null;
        }

        // Already absolute
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        $path = $parsed['path'] ?? '/';
        $dir = dirname($path);
        
        return $scheme . '://' . $host . $dir . '/' . $url;
    }

    /**
     * Save link preview to database.
     */
    protected function saveLinkPreview(Post $post, array $data): PostLinkPreview
    {
        // Remove existing preview
        $post->postLinkPreview()->delete();

        return PostLinkPreview::create([
            'post_id' => $post->id,
            'url' => $data['url'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'site_name' => $data['site_name'] ?? null,
            'site_icon' => $data['site_icon'] ?? null,
            'type' => $data['type'] ?? 'website',
            'video_url' => $data['video_url'] ?? null,
            'video_type' => $data['video_type'] ?? null,
            'video_width' => isset($data['video_width']) ? (int) $data['video_width'] : null,
            'video_height' => isset($data['video_height']) ? (int) $data['video_height'] : null,
            'author_name' => $data['author_name'] ?? null,
            'provider_name' => $data['provider_name'] ?? null,
        ]);
    }

    /**
     * Remove link preview for a post.
     */
    public function removeLinkPreview(Post $post): bool
    {
        return $post->postLinkPreview()->delete() >= 0;
    }
}
