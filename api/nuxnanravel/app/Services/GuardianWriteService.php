<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Support\GuardianNameNormalizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuardianWriteService
{
    public function create(Student $student, array $data, ?string $actorRole = null, ?int $actorUserId = null): StudentGuardian
    {
        return DB::transaction(function () use ($student, $data, $actorRole, $actorUserId) {
            $guardian = StudentGuardian::create($this->legacyData($student, $data));
            $person = $this->findPerson($student, $data) ?? Guardian::create($this->personData($student, $data));
            $this->link($student, $guardian, $person, $data, $actorRole, $actorUserId);
            $this->contacts($guardian, $person, $data);

            return $guardian;
        });
    }

    /**
     * Person fields are shared in the new model: editing them affects every student linked to this person,
     * unlike the legacy model where editing affected only this one student row.
     */
    public function update(StudentGuardian $legacy, array $data): StudentGuardian
    {
        return DB::transaction(function () use ($legacy, $data) {
            $legacy->update($this->legacyData($legacy->student, $data, true));
            $link = DB::table('student_guardian_links')->whereJsonContains('legacy_row_ids', $legacy->id)->first();
            if ($link) {
                Guardian::whereKey($link->guardian_id)->update($this->personData($legacy->student, $data, true));
                $linkData = $this->linkData($data);
                if ($linkData !== []) {
                    DB::table('student_guardian_links')->where('id', $link->id)->update($linkData);
                }
                $person = Guardian::find($link->guardian_id);
                $this->contacts($legacy, $person, $data, true);
            }

            return $legacy->fresh(['contacts']);
        });
    }

    public function delete(StudentGuardian $legacy): void
    {
        DB::transaction(function () use ($legacy) {
            $link = DB::table('student_guardian_links')->whereJsonContains('legacy_row_ids', $legacy->id)->first();
            GuardianContact::where('guardian_id', $legacy->id)->delete();
            $legacy->delete();
            if ($link) {
                DB::table('student_guardian_links')->where('id', $link->id)->delete();
                if (! DB::table('student_guardian_links')->where('guardian_id', $link->guardian_id)->exists()) {
                    GuardianContact::where('guardian_person_id', $link->guardian_id)->delete();
                    Guardian::whereKey($link->guardian_id)->delete();
                }
            }
        });
    }

    private function legacyData(Student $student, array $data, bool $update = false): array
    {
        $g = $data['guardian'] ?? $data;

        $values = [
            'academy_id' => $student->academy_id, 'student_id' => $student->id, 'student_code' => $student->student_id,
            'guardian_type' => $g['guardian_type'] ?? null, 'citizen_id' => $g['citizen_id'] ?? null,
            'title_prefix' => $g['title_prefix'] ?? null, 'first_name' => $g['first_name'] ?? null, 'last_name' => $g['last_name'] ?? null,
            'occupation' => $g['occupation'] ?? null, 'workplace' => $g['workplace'] ?? null, 'monthly_income' => $g['monthly_income'] ?? null,
            'relationship' => $g['relationship'] ?? null, 'is_primary_contact' => $g['is_primary_contact'] ?? false,
            'is_emergency_contact' => $g['is_emergency_contact'] ?? false, 'status' => $g['status'] ?? 'alive', 'nationality' => $g['nationality'] ?? 'ไทย',
        ];

        return array_filter($update ? array_intersect_key($values, $g) : $values, fn ($v) => $v !== null);
    }

    private function personData(Student $student, array $data, bool $update = false): array
    {
        $g = $data['guardian'] ?? $data;

        $values = ['academy_id' => $student->academy_id, 'citizen_id' => $g['citizen_id'] ?? null, 'title_prefix' => $g['title_prefix'] ?? null,
            'first_name' => $g['first_name'] ?? null, 'last_name' => $g['last_name'] ?? null, 'occupation' => $g['occupation'] ?? null,
            'workplace' => $g['workplace'] ?? null, 'monthly_income' => $g['monthly_income'] ?? null, 'nationality' => $g['nationality'] ?? 'ไทย', 'status' => $g['status'] ?? 'alive'];

        return array_filter($update ? array_intersect_key($values, $g) : $values, fn ($v) => $v !== null);
    }

    private function findPerson(Student $student, array $data): ?Guardian
    {
        $g = $data['guardian'] ?? $data;
        $citizen = (string) ($g['citizen_id'] ?? '');
        if (! preg_match('/^\d{13}$/', $citizen)) {
            return null;
        }

        return Guardian::where('academy_id', $student->academy_id)->where('citizen_id', $citizen)->get()
            ->first(fn ($p) => GuardianNameNormalizer::normalize($p->first_name) === GuardianNameNormalizer::normalize($g['first_name'] ?? '') && GuardianNameNormalizer::normalize($p->last_name) === GuardianNameNormalizer::normalize($g['last_name'] ?? ''));
    }

    private function link(Student $student, StudentGuardian $legacy, Guardian $person, array $data, ?string $role, ?int $userId): void
    {
        DB::table('student_guardian_links')->insert(array_merge(['student_id' => $student->id, 'guardian_id' => $person->id, 'legacy_row_ids' => json_encode([$legacy->id]), 'appointed_by_role' => $role ?? 'user', 'appointed_by_user_id' => $userId ?? Auth::id(), 'appointed_at' => now(), 'created_at' => now(), 'updated_at' => now()], $this->linkData($data)));
    }

    private function linkData(array $data): array
    {
        $g = $data['guardian'] ?? $data;

        return array_intersect_key($g, array_flip(['guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact']));
    }

    private function contacts(StudentGuardian $legacy, ?Guardian $person, array $data, bool $update = false): void
    {
        $g = $data['guardian'] ?? $data;
        foreach ($data['contacts'] ?? [] as $contact) {
            GuardianContact::create([
                'guardian_id' => $legacy->id,
                'guardian_person_id' => $person?->id,
                'contact_type' => $contact['contact_type'],
                'contact_value' => $contact['contact_value'],
                'is_primary' => $contact['is_primary'] ?? false,
                'is_verified' => $contact['is_verified'] ?? false,
            ]);
        }
        $contact = $data['contact'] ?? null;
        if ($contact) {
            GuardianContact::updateOrCreate(['guardian_id' => $legacy->id, 'contact_type' => $contact['contact_type']], ['guardian_person_id' => $person?->id, 'contact_value' => $contact['contact_value'], 'is_primary' => $contact['is_primary'] ?? true, 'is_verified' => false]);
        } foreach (['phone' => 'phone', 'email' => 'email'] as $key => $type) {
            if (! empty($data[$key]) || ! empty($g[$key])) {
                $value = $data[$key] ?? $g[$key];
                GuardianContact::updateOrCreate(['guardian_id' => $legacy->id, 'contact_type' => $type], ['guardian_person_id' => $person?->id, 'contact_value' => $value, 'is_primary' => $type === 'phone', 'is_verified' => false]);
            }
        }
    }
}
