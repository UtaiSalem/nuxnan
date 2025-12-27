<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class PostMediaService
{
    protected string $disk = 'public';
    protected string $basePath = 'images/posts';
    protected int $maxWidth = 2048;
    protected int $maxHeight = 2048;
    protected int $thumbnailWidth = 400;
    protected int $thumbnailHeight = 400;
    protected int $quality = 85;

    /**
     * Upload multiple images for a post.
     */
    public function uploadImages(Post $post, array $images, ?array $captions = null): array
    {
        $savedImages = [];
        $order = $post->postImages()->max('order') ?? 0;

        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $order++;
                $caption = $captions[$index] ?? null;
                $savedImage = $this->uploadSingleImage($post, $image, $order, $caption);
                
                if ($savedImage) {
                    $savedImages[] = $savedImage;
                }
            }
        }

        return $savedImages;
    }

    /**
     * Upload a single image for a post.
     */
    public function uploadSingleImage(
        Post $post, 
        UploadedFile $image, 
        int $order = 0, 
        ?string $caption = null
    ): ?PostImage {
        try {
            $extension = $image->getClientOriginalExtension();
            $filename = $this->generateFilename($post->id, $extension);
            
            // Store original image
            $path = Storage::disk($this->disk)->putFileAs(
                $this->basePath,
                $image,
                $filename
            );

            if (!$path) {
                throw new \Exception('Failed to store image');
            }

            // Try to create optimized version (requires intervention/image package)
            $this->optimizeImage($path);
            
            // Create thumbnail
            $thumbnailFilename = $this->createThumbnail($path, $filename);

            return PostImage::create([
                'post_id' => $post->id,
                'filename' => $filename,
                'thumbnail' => $thumbnailFilename,
                'caption' => $caption,
                'order' => $order,
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload post image', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate a unique filename.
     */
    protected function generateFilename(int $postId, string $extension): string
    {
        return $postId . '_' . uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Optimize image (resize if too large, compress).
     */
    protected function optimizeImage(string $path): void
    {
        try {
            // This requires intervention/image package
            // Install with: composer require intervention/image
            
            /*
            $fullPath = Storage::disk($this->disk)->path($path);
            $image = Image::make($fullPath);
            
            // Resize if larger than max dimensions
            if ($image->width() > $this->maxWidth || $image->height() > $this->maxHeight) {
                $image->resize($this->maxWidth, $this->maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            $image->save($fullPath, $this->quality);
            */
        } catch (\Exception $e) {
            Log::warning('Failed to optimize image', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create thumbnail for image.
     */
    protected function createThumbnail(string $path, string $filename): ?string
    {
        try {
            // This requires intervention/image package
            
            /*
            $fullPath = Storage::disk($this->disk)->path($path);
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = $this->basePath . '/thumbnails/' . $thumbnailFilename;
            
            $image = Image::make($fullPath);
            $image->fit($this->thumbnailWidth, $this->thumbnailHeight);
            
            Storage::disk($this->disk)->put($thumbnailPath, $image->encode());
            
            return $thumbnailFilename;
            */
            
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to create thumbnail', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete an image.
     */
    public function deleteImage(PostImage $image): bool
    {
        try {
            // Delete files from storage
            Storage::disk($this->disk)->delete($this->basePath . '/' . $image->filename);
            
            if ($image->thumbnail) {
                Storage::disk($this->disk)->delete(
                    $this->basePath . '/thumbnails/' . $image->thumbnail
                );
            }

            // Delete database record
            return $image->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete post image', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete all images for a post.
     */
    public function deleteAllImages(Post $post): bool
    {
        $images = $post->postImages;
        
        foreach ($images as $image) {
            $this->deleteImage($image);
        }

        return true;
    }

    /**
     * Reorder images for a post.
     */
    public function reorderImages(Post $post, array $orderedImageIds): bool
    {
        try {
            foreach ($orderedImageIds as $order => $imageId) {
                PostImage::where('post_id', $post->id)
                    ->where('id', $imageId)
                    ->update(['order' => $order + 1]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reorder images', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update image caption.
     */
    public function updateCaption(PostImage $image, string $caption): bool
    {
        return $image->update(['caption' => $caption]);
    }

    /**
     * Get image URL.
     */
    public function getImageUrl(PostImage $image): string
    {
        return Storage::disk($this->disk)->url($this->basePath . '/' . $image->filename);
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrl(PostImage $image): ?string
    {
        if (!$image->thumbnail) {
            return null;
        }
        
        return Storage::disk($this->disk)->url(
            $this->basePath . '/thumbnails/' . $image->thumbnail
        );
    }
}
