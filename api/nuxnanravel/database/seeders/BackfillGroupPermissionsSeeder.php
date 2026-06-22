<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupPermission;
use App\Constants\AcademyGroupPermissions;

class BackfillGroupPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        AcademyGroup::doesntHave('permissions')->chunk(100, function ($groups) {
            foreach ($groups as $g) {
                foreach (AcademyGroupPermissions::PERMISSIONS as $key => $meta) {
                    AcademyGroupPermission::firstOrCreate(
                        ['academy_group_id' => $g->id, 'permission_key' => $key],
                        ['enabled' => $meta['default']],
                    );
                }
            }
        });
    }
}
