<?php

namespace App\Traits;

use App\Models\HomeVisitImage;
use App\Models\HomeVisitParticipant;
use App\Models\StudentHomeVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ตัวช่วยจัดการ participants + รูปหลักฐาน ของการเยี่ยมบ้าน (HV-S4/S5)
 *
 * ใช้ร่วมกันระหว่างโฟลว์แอดมิน (HomeVisit\AdminController) และโฟลว์ master-profile
 * (Master\HomeVisitController) เพื่อไม่ให้ลอจิกอัปโหลด/แทนที่ ซ้ำสองที่
 */
trait HandlesHomeVisitAttachments
{
    /**
     * แทนที่รายชื่อผู้ร่วมเยี่ยมทั้งชุด — null = ไม่ได้ส่งมา → คงของเดิมไว้
     */
    protected function syncHomeVisitParticipants(StudentHomeVisit $visit, ?array $participants): void
    {
        if ($participants === null) {
            return;
        }

        $visit->participants()->delete();

        foreach ($participants as $participant) {
            $name = trim((string) ($participant['participant_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            HomeVisitParticipant::create([
                'home_visit_id' => $visit->id,
                'participant_name' => $name,
                'notes' => $participant['notes'] ?? null,
            ]);
        }
    }

    /**
     * ลบรูปตาม id (scoped ต่อ visit เสมอ) + อัปโหลดรูปใหม่จาก multipart `images[]`
     */
    protected function handleHomeVisitImages(Request $request, StudentHomeVisit $visit): void
    {
        foreach ((array) $request->input('images_to_delete', []) as $imageId) {
            $image = HomeVisitImage::where('home_visit_id', $visit->id)->find($imageId);
            if ($image) {
                if ($image->image_path) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imageName = time().'_'.$index.'.'.$image->getClientOriginalExtension();
                $imagePath = $image->storeAs('home_visit_images/'.$visit->id, $imageName, 'public');

                HomeVisitImage::create([
                    'home_visit_id' => $visit->id,
                    'image_path' => $imagePath,
                    'image_name' => $imageName,
                    'image_type' => 'evidence',
                    'description' => 'รูปภาพหลักฐานการเยี่ยมบ้าน',
                    'uploaded_by' => auth('api')->id(),
                ]);
            }
        }
    }
}
