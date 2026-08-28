<?php

namespace App\Console\Commands;

use App\Models\AcademyPermission;
use App\Models\AcademyRole;
use Illuminate\Console\Command;

class AcademyRolesDoctor extends Command
{
    protected $signature = 'academy:roles-doctor';

    protected $description = 'ตรวจว่าแถว system role ในฐานตรงกับ AcademyRole::SYSTEM_ROLES หรือไม่';

    public function handle(): int
    {
        $hasError = false;
        $tableData = [];
        $detailedErrors = [];
        $invalidKeys = [];

        $validPermissions = array_column(AcademyPermission::getAllPermissions(), 'name');

        foreach (AcademyRole::SYSTEM_ROLES as $name => $roleData) {
            $codePermissions = $roleData['permissions'] ?? [];

            foreach ($codePermissions as $perm) {
                if ($perm !== '*' && ! in_array($perm, $validPermissions, true)) {
                    $invalidKeys[] = "Role '{$name}' มีคีย์ผีในโค้ด: {$perm}";
                    $hasError = true;
                }
            }

            $dbRole = AcademyRole::whereNull('academy_id')->where('name', $name)->first();

            if (! $dbRole) {
                $tableData[] = [
                    $name,
                    'ไม่มีแถวในฐาน',
                    count($codePermissions),
                    '-',
                    '-',
                ];
                $detailedErrors[] = "Role '{$name}' ไม่มีแถวในฐาน";
                $hasError = true;

                continue;
            }

            $dbPermissions = is_array($dbRole->permissions) ? $dbRole->permissions : [];

            if (in_array('*', $codePermissions, true) || in_array('*', $dbPermissions, true)) {
                $tableData[] = [
                    $name,
                    in_array('*', $dbPermissions, true) ? '*' : count($dbPermissions),
                    in_array('*', $codePermissions, true) ? '*' : count($codePermissions),
                    '-',
                    '-',
                ];

                continue;
            }

            $missingInDb = array_diff($codePermissions, $dbPermissions);
            $extraInDb = array_diff($dbPermissions, $codePermissions);

            $missingCount = count($missingInDb);
            $extraCount = count($extraInDb);

            if ($missingCount > 0 || $extraCount > 0) {
                $hasError = true;
                $details = "Role '{$name}' ไม่ตรงกัน:";
                if ($missingCount > 0) {
                    $details .= "\n  - ขาดในฐาน: ".implode(', ', $missingInDb);
                }
                if ($extraCount > 0) {
                    $details .= "\n  - เกินในฐาน: ".implode(', ', $extraInDb);
                }
                $detailedErrors[] = $details;
            }

            $tableData[] = [
                $name,
                count($dbPermissions),
                count($codePermissions),
                $missingCount,
                $extraCount,
            ];
        }

        $this->table(['role', 'ในฐาน', 'ในโค้ด', 'ขาดในฐาน', 'เกินในฐาน'], $tableData);

        if (! empty($invalidKeys)) {
            $this->error('พบสิทธิ์ที่ไม่มีอยู่จริง (คีย์ผี):');
            foreach ($invalidKeys as $error) {
                $this->line($error);
            }
            $this->line('');
        }

        if (! empty($detailedErrors)) {
            $this->error('พบความไม่ตรงกัน:');
            foreach ($detailedErrors as $error) {
                $this->line($error);
            }
        }

        return $hasError ? Command::FAILURE : Command::SUCCESS;
    }
}
