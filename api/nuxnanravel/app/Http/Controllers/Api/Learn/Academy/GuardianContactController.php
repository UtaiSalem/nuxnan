<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Guardian;
use App\Models\GuardianContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianContactController extends Controller
{
    public function index(Academy $academy, Guardian $guardian)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        $contacts = $guardian->contacts->map(fn ($c) => [
            'id' => $c->id,
            'contact_type' => $c->contact_type,
            'contact_value' => $c->contact_value,
            'is_primary' => (bool) $c->is_primary,
            'is_verified' => (bool) $c->is_verified,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    public function store(Request $request, Academy $academy, Guardian $guardian)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        $validated = $request->validate([
            'contact_type' => 'required|in:phone,mobile,email,line,facebook',
            'contact_value' => 'required|string|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validated['contact_type'] === 'email') {
            $request->validate(['contact_value' => 'email']);
        }

        $exists = $guardian->contacts()
            ->where('contact_type', $validated['contact_type'])
            ->where('contact_value', $validated['contact_value'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'ช่องทางติดต่อนี้มีอยู่แล้ว',
            ], 409);
        }

        DB::beginTransaction();
        try {
            if ($request->boolean('is_primary')) {
                $guardian->contacts()
                    ->where('contact_type', $validated['contact_type'])
                    ->update(['is_primary' => false]);
            }

            $contact = GuardianContact::create([
                'guardian_person_id' => $guardian->id,
                'contact_type' => $validated['contact_type'],
                'contact_value' => $validated['contact_value'],
                'is_primary' => $request->boolean('is_primary'),
                'is_verified' => false,
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $contact->id,
                    'contact_type' => $contact->contact_type,
                    'contact_value' => $contact->contact_value,
                    'is_primary' => (bool) $contact->is_primary,
                    'is_verified' => (bool) $contact->is_verified,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Academy $academy, Guardian $guardian, GuardianContact $contact)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        if ((int) $contact->guardian_person_id !== $guardian->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบช่องทางติดต่อนี้ของผู้ปกครองคนนี้'], 404);
        }

        $validated = $request->validate([
            'contact_type' => 'sometimes|in:phone,mobile,email,line,facebook',
            'contact_value' => 'sometimes|string|max:255',
            'is_primary' => 'sometimes|boolean',
        ]);

        if (isset($validated['contact_type']) && $validated['contact_type'] === 'email' && isset($validated['contact_value'])) {
            $request->validate(['contact_value' => 'email']);
        } elseif (! isset($validated['contact_type']) && $contact->contact_type === 'email' && isset($validated['contact_value'])) {
            $request->validate(['contact_value' => 'email']);
        }

        $newType = $validated['contact_type'] ?? $contact->contact_type;
        $newValue = $validated['contact_value'] ?? $contact->contact_value;

        if (isset($validated['contact_type']) || isset($validated['contact_value'])) {
            $exists = $guardian->contacts()
                ->where('id', '!=', $contact->id)
                ->where('contact_type', $newType)
                ->where('contact_value', $newValue)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'ช่องทางติดต่อนี้มีอยู่แล้ว',
                ], 409);
            }
        }

        DB::beginTransaction();
        try {
            $dataToUpdate = $validated;

            if (isset($validated['contact_value']) && $validated['contact_value'] !== $contact->contact_value) {
                $dataToUpdate['is_verified'] = false;
            }

            // is_verified is deliberately absent from the validation rules, so it can never
            // arrive from the request — the only way it moves is the reset above.

            if (isset($validated['is_primary']) && $validated['is_primary']) {
                $guardian->contacts()
                    ->where('id', '!=', $contact->id)
                    ->where('contact_type', $newType)
                    ->update(['is_primary' => false]);
            }

            $contact->update($dataToUpdate);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $contact->id,
                    'contact_type' => $contact->contact_type,
                    'contact_value' => $contact->contact_value,
                    'is_primary' => (bool) $contact->is_primary,
                    'is_verified' => (bool) $contact->is_verified,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Academy $academy, Guardian $guardian, GuardianContact $contact)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        if ((int) $contact->guardian_person_id !== $guardian->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบช่องทางติดต่อนี้ของผู้ปกครองคนนี้'], 404);
        }

        DB::beginTransaction();
        try {
            $contact->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ลบช่องทางติดต่อเรียบร้อยแล้ว',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    public function setPrimary(Academy $academy, Guardian $guardian, GuardianContact $contact)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        if ((int) $contact->guardian_person_id !== $guardian->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบช่องทางติดต่อนี้ของผู้ปกครองคนนี้'], 404);
        }

        DB::beginTransaction();
        try {
            $guardian->contacts()
                ->where('contact_type', $contact->contact_type)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);

            $contact->update(['is_primary' => true]);
            DB::commit();

            return response()->json([
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }
}
