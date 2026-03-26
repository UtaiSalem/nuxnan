<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FixAvatarPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:avatar-paths {--dry-run : Simulate the migration without making changes} {--clear-missing : Set profile_photo_path to null for missing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move user avatars to avatars/{user_id}/ structure and optionally clear missing file references';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting avatar path fix...');

        $isDryRun = $this->option('dry-run');
        $clearMissing = $this->option('clear-missing');
        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be saved.');
        }

        $users = User::whereNotNull('profile_photo_path')->get();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'migrated' => 0,
            'already_correct' => 0,
            'missing' => 0,
            'cleared' => 0,
            'errors' => 0,
        ];

        foreach ($users as $user) {
            $stats['processed']++;
            $currentPath = $user->profile_photo_path;

            // Skip URLs (Social Login)
            if (filter_var($currentPath, FILTER_VALIDATE_URL)) {
                $stats['already_correct']++; // Technically not "correct" structure, but valid external URL
                $bar->advance();
                continue;
            }

            // Cleanup path (remove storage prefix if present)
            $cleanCurrentPath = preg_replace('#^/?(storage/)?#', '', $currentPath);

            // Check if file exists
            if (!Storage::disk('public')->exists($cleanCurrentPath)) {
                // File missing at old path - check if already migrated to avatars/{user_id}/
                $targetFolder = "avatars/{$user->id}";
                $existingFiles = Storage::disk('public')->files($targetFolder);
                $found = false;
                foreach ($existingFiles as $file) {
                    if (Str::startsWith(basename($file), $user->id . '_')) {
                        // Found migrated file, update DB to point to it
                        if (!$isDryRun) {
                            $user->profile_photo_path = $file;
                            $user->saveQuietly();
                        }
                        $stats['migrated']++;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    if ($clearMissing && !$isDryRun) {
                        $user->profile_photo_path = null;
                        $user->saveQuietly();
                        $stats['cleared']++;
                    } else {
                        $stats['missing']++;
                    }
                }
                $bar->advance();
                continue;
            }

            // Define Target Structure: avatars/{user_id}/filename.ext
            $filename = basename($cleanCurrentPath);
            $targetFolder = "avatars/{$user->id}";
            $targetPath = "{$targetFolder}/{$filename}";

            // Check if already in correct folder
            // strict check: directory matches
            if (Str::startsWith($cleanCurrentPath, $targetFolder . '/')) {
                $stats['already_correct']++;
                $bar->advance();
                continue;
            }

            if ($isDryRun) {
                // $this->line("Would move: $cleanCurrentPath -> $targetPath");
            } else {
                try {
                    // Ensure target directory exists (technically move/put handles this often, but good to be sure)
                    // Storage::disk('public')->makeDirectory($targetFolder); // Optional usually

                    // Check if target file already exists (collision?)
                    if (Storage::disk('public')->exists($targetPath)) {
                        // If file content is same, just update DB? 
                        // Or rename new file? Let's rename to be safe.
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $name = pathinfo($filename, PATHINFO_FILENAME);
                        $newFilename = $name . '_' . time() . '.' . $ext;
                        $targetPath = "{$targetFolder}/{$newFilename}";
                    }

                    // Move file
                    Storage::disk('public')->move($cleanCurrentPath, $targetPath);

                    // Update DB
                    $user->profile_photo_path = $targetPath;
                    $user->saveQuietly();

                    $stats['migrated']++;
                } catch (\Exception $e) {
                    $this->error("Failed to move {$cleanCurrentPath} for user {$user->id}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Total Users Checked', $stats['processed']],
            ['Migrated/Moved', $stats['migrated']],
            ['Already Correct/External', $stats['already_correct']],
            ['Missing Files', $stats['missing']],
            ['Cleared (set null)', $stats['cleared']],
            ['Errors', $stats['errors']],
        ]);
        
        $this->info('Done.');
    }
}
