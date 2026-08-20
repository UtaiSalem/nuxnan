<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\SportsAlbum;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsPhoto;
use App\Services\Sports\SportsPhotoService;
use Illuminate\Http\Request;

class SportsAlbumController extends Controller
{
    public function index(Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $albums = SportsAlbum::where('edition_id', $edition->id)
            ->withCount('photos')
            ->with('coverPhoto')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($albums);
    }

    public function store(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean',
            'discipline_id' => 'nullable|integer',
            'house_group_id' => 'nullable|integer',
        ]);

        if (isset($validated['discipline_id'])) {
            $disciplineExists = SportsDiscipline::where('edition_id', $edition->id)
                ->where('id', $validated['discipline_id'])
                ->exists();
            if (! $disciplineExists) {
                abort(422, 'Discipline is not part of this edition.');
            }
        }

        if (isset($validated['house_group_id'])) {
            $houseGroupIds = $edition->houseGroupIds();
            if (! in_array((int) $validated['house_group_id'], array_map('intval', $houseGroupIds), true)) {
                abort(422, 'House is not part of this edition.');
            }
        }

        $album = new SportsAlbum;
        $album->edition_id = $edition->id;
        $album->academy_id = $edition->academy_id;
        $album->name = $validated['name'];
        $album->description = $validated['description'] ?? null;
        $album->is_public = $validated['is_public'] ?? true;
        $album->discipline_id = $validated['discipline_id'] ?? null;
        $album->house_group_id = $validated['house_group_id'] ?? null;
        $album->created_by_user_id = $request->user()->id;
        $album->save();

        return response()->json($album, 201);
    }

    public function show(Academy $academy, SportsEdition $edition, SportsAlbum $album)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $album->edition_id === (int) $edition->id, 404);

        $album->load('photos');

        return response()->json($album);
    }

    public function update(Request $request, Academy $academy, SportsEdition $edition, SportsAlbum $album)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $album->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'description' => 'sometimes|nullable|string',
            'is_public' => 'sometimes|nullable|boolean',
            'discipline_id' => 'sometimes|nullable|integer',
            'house_group_id' => 'sometimes|nullable|integer',
            'cover_photo_id' => 'sometimes|nullable|integer',
        ]);

        if (array_key_exists('discipline_id', $validated) && $validated['discipline_id'] !== null) {
            $disciplineExists = $edition->disciplines()->where('id', $validated['discipline_id'])->exists();
            if (! $disciplineExists) {
                abort(422, 'Discipline is not part of this edition.');
            }
        }

        if (array_key_exists('house_group_id', $validated) && $validated['house_group_id'] !== null) {
            $houseGroupIds = $edition->houseGroupIds();
            if (! in_array((int) $validated['house_group_id'], array_map('intval', $houseGroupIds), true)) {
                abort(422, 'House is not part of this edition.');
            }
        }

        if (array_key_exists('cover_photo_id', $validated) && $validated['cover_photo_id'] !== null) {
            $photoExists = SportsPhoto::where('id', $validated['cover_photo_id'])
                ->where('album_id', $album->id)
                ->exists();
            if (! $photoExists) {
                abort(422, 'Photo is not part of this album.');
            }
        }

        $album->update($validated);

        return response()->json($album);
    }

    public function destroy(Academy $academy, SportsEdition $edition, SportsAlbum $album, SportsPhotoService $service)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $album->edition_id === (int) $edition->id, 404);

        $service->deleteAlbumFiles($album);
        $album->delete();

        return response()->noContent();
    }

    public function photos(Academy $academy, SportsEdition $edition, SportsAlbum $album)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $album->edition_id === (int) $edition->id, 404);

        $photos = SportsPhoto::where('album_id', $album->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return response()->json($photos);
    }

    public function uploadPhotos(Request $request, Academy $academy, SportsEdition $edition, SportsAlbum $album, SportsPhotoService $service)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $album->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'photos' => 'required|array|min:1|max:20',
            'photos.*' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:8192',
            'captions' => 'nullable|array',
            'captions.*' => 'nullable|string|max:255',
        ]);

        $photos = [];
        foreach ($validated['photos'] as $index => $file) {
            $caption = $validated['captions'][$index] ?? null;
            $photos[] = $service->upload($album, $file, $caption, $request->user());
        }

        return response()->json($photos, 201);
    }

    public function updatePhoto(Request $request, Academy $academy, SportsEdition $edition, SportsPhoto $photo)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $photo->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'caption' => 'sometimes|nullable|string|max:255',
            'display_order' => 'sometimes|required|integer|min:0',
        ]);

        $photo->update($validated);

        return response()->json($photo);
    }

    public function destroyPhoto(Academy $academy, SportsEdition $edition, SportsPhoto $photo, SportsPhotoService $service)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $photo->edition_id === (int) $edition->id, 404);

        $service->delete($photo);

        return response()->noContent();
    }
}
