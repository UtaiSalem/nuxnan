<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Services\SchoolDepartmentSetupService;
use Illuminate\Database\Seeder;

class SchoolDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $academy = Academy::first();

        if (! $academy) {
            $this->command->warn('No academy found. Skipping department setup.');

            return;
        }

        $service = new SchoolDepartmentSetupService;
        $result = $service->setup($academy);

        $this->command->info("Academy: {$academy->name}");
        $this->command->info("Created: {$result['created']}, Skipped: {$result['skipped']}, Total: {$result['total']}");
    }
}
