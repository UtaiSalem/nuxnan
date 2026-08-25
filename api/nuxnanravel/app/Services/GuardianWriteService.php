<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Support\GuardianNameNormalizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuardianWriteService
{
    public function create(Student $student, array $data, ?string $actorRole = null, ?int $actorUserId = null): StudentGuardianLink
    {
        return DB::transaction(function () use ($student, $data, $actorRole, $actorUserId) {
            $person = $this->findPerson($student, $data) ?? Guardian::create($this->personData($student, $data));

            $link = new StudentGuardianLink([
                'student_id' => $student->id,
                'guardian_id' => $person->id,
            ]);
            $link->forceFill(array_merge([
                'legacy_row_ids' => null,
                'appointed_by_role' => $actorRole ?? app(GuardianAccessService::class)->actorRole(Auth::user(), $student),
                'appointed_by_user_id' => $actorUserId ?? Auth::id(),
                'appointed_at' => now(),
            ], $this->linkData($data)))->save();

            $this->contacts($person, $data);

            return $link;
        });
    }

    /**
     * Person fields are shared in the new model: editing them affects every student linked to this person,
     * unlike the legacy model where editing affected only this one student row.
     */
    public function update(StudentGuardianLink $link, array $data): StudentGuardianLink
    {
        return DB::transaction(function () use ($link, $data) {
            $student = $link->student;

            $personData = $this->personData($student, $data, true);
            if ($personData !== []) {
                Guardian::whereKey($link->guardian_id)->update($personData);
            }

            $linkData = $this->linkData($data);
            if ($linkData !== []) {
                $link->update($linkData);
            }

            $this->contacts(Guardian::find($link->guardian_id), $data, true);

            return $link->fresh();
        });
    }

    public function delete(StudentGuardianLink $link): void
    {
        DB::transaction(function () use ($link) {
            $personId = $link->guardian_id;
            $link->delete();
            if (! StudentGuardianLink::where('guardian_id', $personId)->exists()) {
                GuardianContact::where('guardian_person_id', $personId)->delete();
                Guardian::whereKey($personId)->delete();
            }
        });
    }

    /**
     * Attach an existing guardian person to a student (the "sibling's guardian" case).
     */
    public function appoint(Student $student, Guardian $person, array $linkData, ?string $actorRole = null, ?int $actorUserId = null): StudentGuardianLink
    {
        return DB::transaction(function () use ($student, $person, $linkData, $actorRole, $actorUserId) {
            $link = new StudentGuardianLink([
                'student_id' => $student->id,
                'guardian_id' => $person->id,
            ]);
            $link->forceFill(array_merge([
                'legacy_row_ids' => null,
                'appointed_by_role' => $actorRole ?? app(GuardianAccessService::class)->actorRole(Auth::user(), $student),
                'appointed_by_user_id' => $actorUserId ?? Auth::id(),
                'appointed_at' => now(),
            ], $this->linkData($linkData)))->save();

            return $link;
        });
    }

    /** Mark an appointment as checked by staff. Returns false when the link is already verified. */
    public function verify(StudentGuardianLink $link, int $verifierUserId): bool
    {
        if ($link->verified_at !== null) {
            return false;
        }

        $link->forceFill(['verified_by_user_id' => $verifierUserId, 'verified_at' => now()])->save();

        return true;
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

    private function linkData(array $data): array
    {
        $g = $data['guardian'] ?? $data;

        return array_intersect_key($g, array_flip(['guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact']));
    }

    /**
     * Contacts hang off the person, not off one student's link, so an edit here reaches every
     * sibling that shares this guardian. A person carrying several numbers of the same type
     * (the backfill kept all of them) has the first non-superseded one updated.
     */
    private function contacts(?Guardian $person, array $data, bool $update = false): void
    {
        if ($person === null) {
            return;
        }

        $g = $data['guardian'] ?? $data;
        foreach ($data['contacts'] ?? [] as $contact) {
            GuardianContact::create([
                'guardian_person_id' => $person->id,
                'contact_type' => $contact['contact_type'],
                'contact_value' => $contact['contact_value'],
                'is_primary' => $contact['is_primary'] ?? false,
                'is_verified' => $contact['is_verified'] ?? false,
            ]);
        }
        $contact = $data['contact'] ?? null;
        if ($contact) {
            GuardianContact::updateOrCreate(['guardian_person_id' => $person->id, 'contact_type' => $contact['contact_type'], 'superseded_by_contact_id' => null], ['contact_value' => $contact['contact_value'], 'is_primary' => $contact['is_primary'] ?? true, 'is_verified' => false]);
        }
        foreach (['phone' => 'phone', 'email' => 'email'] as $key => $type) {
            if (! empty($data[$key]) || ! empty($g[$key])) {
                $value = $data[$key] ?? $g[$key];
                GuardianContact::updateOrCreate(['guardian_person_id' => $person->id, 'contact_type' => $type, 'superseded_by_contact_id' => null], ['contact_value' => $value, 'is_primary' => $type === 'phone', 'is_verified' => false]);
            }
        }
    }
}
