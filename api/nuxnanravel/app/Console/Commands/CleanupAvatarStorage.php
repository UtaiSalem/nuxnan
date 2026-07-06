<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupAvatarStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nuxnan:cleanup-avatars {--dry-run : Simulate without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete orphaned profile-photos directory and empty legacy directories in avatars';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting storage cleanup...');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('--- DRY RUN MODE ---');
        }

        // 1. Delete profile-photos directory
        if (Storage::disk('public')->exists('profile-photos')) {
            $files = Storage::disk('public')->allFiles('profile-photos');
            $count = count($files);

            if ($isDryRun) {
                $this->info("Would delete 'profile-photos' directory containing {$count} files.");
            } else {
                Storage::disk('public')->deleteDirectory('profile-photos');
                $this->info("Deleted 'profile-photos' directory.");
            }
        } else {
            $this->info("'profile-photos' directory does not exist.");
        }

        // 2. Clean up empty directories in avatars/
        // We need to be careful. We only want to delete directories that are EMPTY.
        // PHP's rmdir only works on empty dirs, but Storage::deleteDirectory deletes everything.
        // So we will use a custom recursive generic approach to find empty dirs.

        $avatarsPath = Storage::disk('public')->path('avatars');

        if (is_dir($avatarsPath)) {
            $this->info("Scanning 'avatars' for empty directories and orphaned root files...");

            // 1. Remove empty subdirectories
            $cleanedDirs = $this->removeEmptySubFolders($avatarsPath, $isDryRun);
            $this->info("Removed {$cleanedDirs} empty directories.");

            // 2. Remove orphaned files in root of avatars/ (files that are NOT directories)
            // Valid avatars are now deep in subfolders. Any file in root is an orphan.
            $files = scandir($avatarsPath);
            $orphansRemoved = 0;
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $fullPath = $avatarsPath.'/'.$file;

                if (is_file($fullPath)) {
                    if ($isDryRun) {
                        $this->line('Would delete orphan file: '.$file);
                    } else {
                        unlink($fullPath);
                        $this->line('Deleted orphan file: '.$file);
                    }
                    $orphansRemoved++;
                }
            }
            $this->info("Removed {$orphansRemoved} orphaned files from root.");
        }

        $this->info('Cleanup completed.');
    }

    private function removeEmptySubFolders($path, $isDryRun)
    {
        $emptyDirsFound = 0;

        // Scan all contents
        $dirs = glob($path.'/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            // Recursive call for subdirectories
            $emptyDirsFound += $this->removeEmptySubFolders($dir, $isDryRun);

            // Re-check if this directory is now empty
            if ($this->isDirEmpty($dir)) {
                if ($isDryRun) {
                    $this->line('Would remove empty dir: '.basename($dir));
                } else {
                    rmdir($dir);
                    $this->line('Removed empty dir: '.basename($dir));
                }
                $emptyDirsFound++;
            }
        }

        return $emptyDirsFound;
    }

    private function isDirEmpty($dir)
    {
        if (! is_readable($dir)) {
            return false;
        }

        return count(scandir($dir)) == 2; // . and ..
    }
}
