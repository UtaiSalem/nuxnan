<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAvatarsToShardedStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nuxnan:migrate-avatars-sharded {--dry-run : Simulate the migration without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate user avatars to a hashed sharded directory structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting avatar migration to sharded structure...');

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be saved.');
        }

        $users = User::whereNotNull('profile_photo_path')->get();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $processed = 0;
        $migrated = 0;
        $missing = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $currentPath = $user->profile_photo_path;

            // Skip social login URLs
            if (filter_var($currentPath, FILTER_VALIDATE_URL)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            // Determine Hashed Path
            // md5(1) = c4ca... -> avatars/c4/ca/filename.jpg
            $hash = md5($user->id);
            $folder1 = substr($hash, 0, 2);
            $folder2 = substr($hash, 2, 2);
            $shardedFolder = "avatars/{$folder1}/{$folder2}";

            // Check if ALREADY in correct sharded format
            // Regex to match avatars/xx/xx/userid_...
            $regex = "#^avatars/{$folder1}/{$folder2}/\d+_\d+_[a-z0-9]+\.[a-z]+$#i";
            if (preg_match($regex, $currentPath)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            // Check if file exists in public disk
            if (! Storage::disk('public')->exists($currentPath)) {
                // Try checking without potential leading slashes or storage prefix just in case
                $cleanPath = preg_replace('#^/?(storage/)?#', '', $currentPath);
                if (! Storage::disk('public')->exists($cleanPath)) {
                    $missing++;
                    $bar->advance();

                    continue;
                }
                $currentPath = $cleanPath;
            }

            // Determine extension
            $extension = pathinfo($currentPath, PATHINFO_EXTENSION);
            if (! $extension) {
                $extension = 'jpg';
            } // Default fallback

            // Generate new filename and path
            $newFilename = $user->id.'_'.time().'_'.uniqid().'.'.$extension;
            $newPath = "{$shardedFolder}/{$newFilename}";

            if ($isDryRun) {
                // $this->line("Would move: {$currentPath} -> {$newPath}");
            } else {
                try {
                    // Create directory if not exists (Storage facade handles this recursively usually on put, but not move)
                    // Ensure directories exist
                    // Note: move() usually creates parent dirs in local driver, but let's be safe.

                    // Move file
                    Storage::disk('public')->move($currentPath, $newPath);

                    // Update Database
                    $user->profile_photo_path = $newPath;
                    $user->saveQuietly(); // Don't trigger events

                    $migrated++;
                } catch (\Exception $e) {
                    $this->error("Error moving file for user {$user->id}: ".$e->getMessage());
                    $bar->advance();

                    continue;
                }
            }

            $migrated++; // Count as migrated for dry run logic too
            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Migration completed.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users with Avatars', $users->count()],
                ['Processed (Ready to Move)', $migrated],
                ['Skipped (Already correct/URL)', $skipped],
                ['Missing Files', $missing],
            ]
        );

        if ($isDryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }
    }
}
