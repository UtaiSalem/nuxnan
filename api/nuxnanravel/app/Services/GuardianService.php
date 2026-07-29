<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;

class GuardianService
{
    public function forStudent(Student $student)
    {
        return StudentGuardianLink::query()
            ->where('student_id', $student->id)
            ->with(['guardian.contacts', 'student:id,first_name_th,last_name_th,student_id'])
            ->orderByDesc('is_primary_contact')
            ->orderBy('guardian_type')
            ->get();
    }

    public function listForAcademy(Academy $academy, array $filters)
    {
        $query = StudentGuardianLink::query()
            ->whereIn('guardian_id', Guardian::query()->select('id')->where('academy_id', $academy->id))
            ->with(['guardian.contacts', 'student:id,first_name_th,last_name_th,student_id']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('guardian', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('contacts', fn ($cq) => $cq->where('contact_value', 'LIKE', "%{$search}%"));
                });
            });
        }

        if (! empty($filters['type'])) {
            $query->where('guardian_type', $filters['type']);
        }

        return $query->orderByDesc('student_guardian_links.created_at')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 100));
    }

    public function statisticsForAcademy(Academy $academy): array
    {
        $base = StudentGuardianLink::query()->whereIn('guardian_id', Guardian::query()->select('id')->where('academy_id', $academy->id));
        $stats = (clone $base)->selectRaw('guardian_type, count(*) as count')
            ->groupBy('guardian_type')->pluck('count', 'guardian_type')->toArray();
        $total = array_sum($stats);
        $withContact = (clone $base)->whereHas('guardian', fn ($q) => $q->whereHas('contacts'))->count();

        return ['total' => $total, 'by_type' => $stats, 'with_contact' => $withContact, 'without_contact' => $total - $withContact];
    }

    public function contactsFor(Guardian $guardian)
    {
        return $guardian->contacts()->whereNull('superseded_by_contact_id')->get();
    }
}
