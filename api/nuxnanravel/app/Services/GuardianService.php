<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use Illuminate\Support\Facades\DB;

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
        $query = Guardian::query()
            ->where('academy_id', $academy->id)
            ->with(['contacts', 'students:id,first_name_th,last_name_th,student_id']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('contacts', fn ($cq) => $cq->where('contact_value', 'LIKE', "%{$search}%"));
            });
        }

        // guardian_type lives on the link, not on the person: the same man is a father to one
        // child and an uncle to another, so filtering asks whether ANY of his links match.
        if (! empty($filters['type'])) {
            $query->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('student_guardian_links')
                ->whereColumn('student_guardian_links.guardian_id', 'guardians.id')
                ->where('student_guardian_links.guardian_type', $filters['type']));
        }

        return $query->orderBy('first_name')->orderBy('last_name')
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

    /**
     * Put a plain `guardians` array on a student that is about to be serialized whole, and drop
     * the relation it was built from.
     *
     * Left loaded, the relation rides along in the body as guardian_links[].guardian — the entire
     * person row — under a key no caller gates. Callers that hand the model straight to the
     * serializer therefore have to go through here rather than eager-loading the relation.
     *
     * @param  bool  $withSensitive  include citizen_id and monthly_income
     */
    public function attachGuardiansTo(Student $student, bool $withSensitive): void
    {
        $student->loadMissing('guardianLinks.guardian.contacts');

        $guardians = $student->guardianLinks->map(function (StudentGuardianLink $link) use ($withSensitive): array {
            $data = [
                'id' => $link->id,
                'guardian_id' => $link->guardian_id,
                'student_id' => $link->student_id,
                'guardian_type' => $link->guardian_type,
                'relationship' => $link->relationship,
                'title_prefix' => $link->title_prefix,
                'first_name' => $link->first_name,
                'last_name' => $link->last_name,
                'full_name' => $link->full_name,
                'occupation' => $link->occupation,
                'workplace' => $link->workplace,
                'status' => $link->status,
                'nationality' => $link->nationality,
                'is_primary_contact' => $link->is_primary_contact,
                'is_emergency_contact' => $link->is_emergency_contact,
                'appointed_by_role' => $link->appointed_by_role,
                'verified_at' => $link->verified_at,
                'is_verified' => $link->verified_at !== null,
                'contacts' => $link->guardian?->contacts->map(fn ($contact): array => [
                    'id' => $contact->id,
                    'contact_type' => $contact->contact_type,
                    'contact_value' => $contact->contact_value,
                    'is_primary' => $contact->is_primary,
                ])->values()->all() ?? [],
            ];

            if ($withSensitive) {
                $data['citizen_id'] = $link->citizen_id;
                $data['monthly_income'] = $link->monthly_income;
            }

            return $data;
        })->values()->all();

        $student->setAttribute('guardians', $guardians);
        $student->unsetRelation('guardianLinks');
    }

    public function contactsFor(Guardian $guardian)
    {
        return $guardian->contacts()->whereNull('superseded_by_contact_id')->get();
    }
}
