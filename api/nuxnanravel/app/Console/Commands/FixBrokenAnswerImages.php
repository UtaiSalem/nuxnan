<?php

namespace App\Console\Commands;

use App\Models\AssignmentAnswerImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixBrokenAnswerImages extends Command
{
    protected $signature = 'fix:broken-answer-images {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix assignment answer images with missing file extensions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $fixed = 0;
        $unfixable = 0;

        $images = AssignmentAnswerImage::all();
        $this->info("Checking {$images->count()} images...");

        foreach ($images as $image) {
            $filename = $image->filename;
            
            // Check for trailing dot (missing extension)
            if (substr($filename, -1) === '.') {
                $baseName = rtrim($filename, '.');
                $storagePath = 'images/courses/assignments/answers/';
                
                // Try to find the file on disk without the dot
                $possibleFile = Storage::disk('public')->path($storagePath . $baseName);
                $possibleFileWithDot = Storage::disk('public')->path($storagePath . $filename);
                
                $foundPath = null;
                if (file_exists($possibleFileWithDot)) {
                    $foundPath = $possibleFileWithDot;
                } elseif (file_exists($possibleFile)) {
                    $foundPath = $possibleFile;
                }
                
                // Also search with common extensions
                if (!$foundPath) {
                    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'] as $ext) {
                        $tryPath = Storage::disk('public')->path($storagePath . $baseName . '.' . $ext);
                        if (file_exists($tryPath)) {
                            $foundPath = $tryPath;
                            break;
                        }
                    }
                }

                if ($foundPath) {
                    // Detect MIME type
                    $mimeType = mime_content_type($foundPath);
                    $extension = match($mimeType) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        'image/heic' => 'heic',
                        'image/heif' => 'heif',
                        default => 'jpg',
                    };

                    $newFilename = $baseName . '.' . $extension;
                    $newPath = Storage::disk('public')->path($storagePath . $newFilename);

                    if ($dryRun) {
                        $this->info("[DRY RUN] Would rename: {$filename} → {$newFilename}");
                    } else {
                        // Rename file on disk if needed
                        if ($foundPath !== $newPath) {
                            rename($foundPath, $newPath);
                        }
                        // Update database record
                        $image->update(['filename' => $newFilename]);
                        $this->info("Fixed: {$filename} → {$newFilename}");
                    }
                    $fixed++;
                } else {
                    $this->warn("Unfixable (file not found): ID {$image->id} | {$filename}");
                    $unfixable++;
                }
            }
            
            // Check file exists on disk
            $filePath = Storage::disk('public')->path('images/courses/assignments/answers/' . $filename);
            if (substr($filename, -1) !== '.' && !file_exists($filePath)) {
                $this->warn("Missing file: ID {$image->id} | {$filename}");
                $unfixable++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Fixed: {$fixed}");
        $this->info("  Unfixable: {$unfixable}");
        
        if ($dryRun && $fixed > 0) {
            $this->info("\nRun without --dry-run to apply fixes.");
        }

        return 0;
    }
}
