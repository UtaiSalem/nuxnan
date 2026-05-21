<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixUuidReferenceCodes extends Command
{
    protected $signature = 'users:fix-uuid-reference-codes
                            {--count=5 : Number of latest users to fix}
                            {--dry-run : Preview changes without applying}';

    protected $description = 'Replace UUID-format reference_codes with Str::random(10) for recently registered users';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $dryRun = $this->option('dry-run');

        // UUID pattern: 8-4-4-4-12 hex characters with dashes (36 chars total)
        $uuidPattern = '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$';

        // Find latest users with UUID-format reference_code
        $users = User::whereRaw("reference_code REGEXP ?", [$uuidPattern])
            ->latest()
            ->limit($count)
            ->get(['id', 'name', 'email', 'reference_code', 'created_at']);

        if ($users->isEmpty()) {
            $this->info('No users found with UUID-format reference_codes.');
            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} user(s) with UUID reference_codes:");
        $this->newLine();

        $tableData = [];

        foreach ($users as $user) {
            $newCode = User::generateReferenceCode();

            $tableData[] = [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Old Code' => $user->reference_code,
                'New Code' => $newCode,
                'Created' => \Illuminate\Support\Carbon::parse($user->created_at)->format('Y-m-d H:i'),
            ];

            if (! $dryRun) {
                $user->update(['reference_code' => $newCode]);
            }
        }

        $this->table(['ID', 'Name', 'Email', 'Old Code', 'New Code', 'Created'], $tableData);
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN — no changes were made. Remove --dry-run to apply.');
        } else {
            $this->info("✓ Updated {$users->count()} user(s) reference_codes successfully.");
        }

        return self::SUCCESS;
    }
}
