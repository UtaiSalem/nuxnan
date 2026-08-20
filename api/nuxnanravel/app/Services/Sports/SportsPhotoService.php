<?php

namespace App\Services\Sports;

use App\Models\SportsAlbum;
use App\Models\SportsPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class SportsPhotoService
{
    /**
     * อัปโหลด 1 รูปเข้าอัลบั้ม — ย่อ + ทำ thumbnail + เขียนแถว
     */
    public function upload(SportsAlbum $album, UploadedFile $file, ?string $caption, User $user): SportsPhoto
    {
        $manager = new ImageManager(new Driver);

        /**
         * ถอดรหัสรูปต้นฉบับ "ครั้งเดียว" แล้วทำ thumbnail ต่อจากรูปที่ย่อแล้ว
         * อ่านไฟล์ต้นฉบับรอบสองเพื่อทำ thumbnail ทำให้ใช้หน่วยความจำเป็นสองเท่า —
         * รูป 12 ล้านพิกเซลกินราว 48MB ต่อการถอดรหัสหนึ่งครั้ง จึงชน memory_limit 128MB ได้จริง
         * (เจอตอนรันเทสต์: Allowed memory size exhausted ที่ Gd\Cloner.php)
         * ย่อจาก 2048 ลง 400 คุณภาพเพียงพอสำหรับ thumbnail อยู่แล้ว
         */
        $image = $manager->read($file->getRealPath());
        $image->scaleDown(2048, 2048);
        $binary = (string) $image->toJpeg(85);
        $width = $image->width();
        $height = $image->height();

        $image->cover(400, 400);
        $thumbBinary = (string) $image->toJpeg(80);

        // ปล่อย GD resource ก่อนวนไปไฟล์ถัดไป — อัปหลายใบต่อคำขอเดียวจะสะสมหน่วยความจำถ้าไม่ปล่อย
        unset($image);

        $filename = uniqid().'_'.time().'.jpg';
        $path = "images/sports/{$album->edition_id}/{$filename}";
        $thumbnailPath = "images/sports/{$album->edition_id}/thumbs/{$filename}";

        Storage::disk('public')->put($path, $binary);
        Storage::disk('public')->put($thumbnailPath, $thumbBinary);

        return DB::transaction(function () use ($album, $path, $thumbnailPath, $caption, $width, $height, $binary, $user) {
            $maxOrder = SportsPhoto::where('album_id', $album->id)->max('display_order') ?? 0;

            $photo = SportsPhoto::create([
                'album_id' => $album->id,
                'edition_id' => $album->edition_id,
                'academy_id' => $album->academy_id,
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'caption' => $caption,
                'width' => $width,
                'height' => $height,
                'size' => strlen($binary),
                'mime_type' => 'image/jpeg',
                'display_order' => $maxOrder + 1,
                'uploaded_by_user_id' => $user->id,
            ]);

            if (is_null($album->cover_photo_id)) {
                $album->update(['cover_photo_id' => $photo->id]);
            }

            return $photo;
        });
    }

    /**
     * ลบรูป 1 ใบ: ลบไฟล์ทั้ง 2 ไฟล์ออกจาก disk แล้วลบแถว
     */
    public function delete(SportsPhoto $photo): void
    {
        $pathsToDelete = array_filter([$photo->path, $photo->thumbnail_path]);
        if (! empty($pathsToDelete)) {
            Storage::disk('public')->delete($pathsToDelete);
        }

        DB::transaction(function () use ($photo) {
            $album = $photo->album;

            if ($album && $album->cover_photo_id === $photo->id) {
                $nextPhoto = SportsPhoto::where('album_id', $album->id)
                    ->where('id', '!=', $photo->id)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->first();

                $album->update(['cover_photo_id' => $nextPhoto ? $nextPhoto->id : null]);
            }

            $photo->delete();
        });
    }

    /**
     * ลบทุกไฟล์ของอัลบั้ม (ใช้ก่อนลบอัลบั้ม)
     */
    public function deleteAlbumFiles(SportsAlbum $album): void
    {
        $photos = SportsPhoto::where('album_id', $album->id)->get();
        foreach ($photos as $photo) {
            $this->delete($photo);
        }
    }
}
