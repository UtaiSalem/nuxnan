<?php

namespace Database\Seeders;

use App\Constants\AcademyGroupPermissions;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupPermission;
use Illuminate\Database\Seeder;

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
