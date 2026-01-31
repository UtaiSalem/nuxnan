# Photos System Implementation Guide

## Overview

The Nuxnan Photos system has been fully implemented to allow users to:
- Upload photos
- Create and manage albums
- Like and comment on photos
- View other users' photos
- Manage privacy settings (Public/Private)

---

## System Components

### 1. Database Models

#### Album Model ([`api/nuxnanravel/app/Models/Album.php`](api/nuxnanravel/app/Models/Album.php))
- **Fields:**
  - `id` - Album ID
  - `user_id` - Owner user ID
  - `name` - Album name
  - `description` - Album description (optional)
  - `cover_photo` - Cover photo URL (optional)
  - `is_public` - Privacy setting (true/false)
  - `created_at`, `updated_at` - Creation and update timestamps
  - `deleted_at` - Soft delete timestamp

- **Relationships:**
  - `user()` - Album owner
  - `photos()` - Photos in album
  - `photos_count` - Number of photos

#### Photo Model ([`api/nuxnanravel/app/Models/Photo.php`](api/nuxnanravel/app/Models/Photo.php))
- **Fields:**
  - `id` - Photo ID
  - `user_id` - Owner user ID
  - `album_id` - Album ID (optional)
  - `url` - Photo URL
  - `thumbnail_url` - Thumbnail URL (optional)
  - `caption` - Photo caption (optional)
  - `is_public` - Privacy setting (true/false)
  - `created_at`, `updated_at` - Creation and update timestamps
  - `deleted_at` - Soft delete timestamp

- **Relationships:**
  - `user()` - Photo owner
  - `album()` - Album containing photo
  - `likes()` - All likes
  - `comments()` - All comments
  - `likes_count` - Number of likes
  - `comments_count` - Number of comments
  - `is_liked` - Whether current user liked

### 2. Controllers

#### PhotoController ([`api/nuxnanravel/app/Http/Controllers/Api/PhotoController.php`](api/nuxnanravel/app/Http/Controllers/Api/PhotoController.php))

**Methods:**
- `index(Request $request, ?string $identifier = null)` - Get user's photos
- `getByAlbum(Request $request, int $albumId)` - Get photos in album
- `store(Request $request)` - Upload photos
- `show(int $id)` - Show single photo
- `update(Request $request, int $id)` - Update photo
- `destroy(int $id)` - Delete photo
- `like(int $id)` - Like photo
- `unlike(int $id)` - Unlike photo

#### AlbumController ([`api/nuxnanravel/app/Http/Controllers/Api/AlbumController.php`](api/nuxnanravel/app/Http/Controllers/Api/AlbumController.php))

**Methods:**
- `index(Request $request, ?string $identifier = null)` - Get user's albums
- `show(int $id)` - Show single album
- `store(Request $request)` - Create new album
- `update(Request $request, int $id)` - Update album
- `destroy(int $id)` - Delete album

### 3. API Routes ([`api/nuxnanravel/routes/api-photos.php`](api/nuxnanravel/routes/api-photos.php))

**Profile Routes:**
```php
GET    /api/profile/photos          // View own photos
POST   /api/profile/photos          // Upload photos
PUT    /api/profile/photos/{id}     // Update photo
DELETE /api/profile/photos/{id}     // Delete photo
GET    /api/profile/albums         // View own albums
POST   /api/profile/albums         // Create album
PUT    /api/profile/albums/{id}    // Update album
DELETE /api/profile/albums/{id}    // Delete album
```

**Photos Routes:**
```php
GET    /api/photos/album/{albumId}   // View photos in album
POST   /api/photos/{id}/like         // Like photo
POST   /api/photos/{id}/unlike       // Unlike photo
GET    /api/photos/{id}              // View single photo
```

**User Routes:**
```php
GET    /api/users/{identifier}/photos  // View other user's photos
GET    /api/users/{identifier}/albums  // View other user's albums
```

### 4. Frontend Composable ([`ui/composables/usePhotos.ts`](ui/composables/usePhotos.ts))

**State:**
- `photos` - Photos list
- `albums` - Albums list
- `isLoading` - Loading state
- `isUploading` - Upload state
- `uploadProgress` - Upload progress
- `error` - Error message
- `hasMore` - Has more data to load

**Methods:**
- `fetchPhotos(userId?, page)` - Fetch photos
- `fetchAlbums(userId?)` - Fetch albums
- `uploadPhotos(files, albumId?)` - Upload photos
- `deletePhoto(photoId)` - Delete photo
- `toggleLike(photo)` - Toggle like
- `updateCaption(photoId, caption)` - Update caption
- `createAlbum(name, description?)` - Create album
- `deleteAlbum(albumId)` - Delete album
- `loadMore(userId?)` - Load more data
- `clearState()` - Clear state

---

## Usage Examples

### 1. Upload Photos

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { uploadPhotos, isUploading, uploadProgress } = usePhotos()

// Upload photos
const handleUpload = async (files: FileList) => {
  try {
    const newPhotos = await uploadPhotos(files)
    console.log('Uploaded:', newPhotos)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

// Upload to album
const handleUploadToAlbum = async (files: FileList, albumId: number) => {
  try {
    const newPhotos = await uploadPhotos(files, albumId)
    console.log('Uploaded to album:', newPhotos)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}
```

### 2. View Photos

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { photos, isLoading, fetchPhotos, loadMore, hasMore } = usePhotos()

// Fetch own photos
onMounted(async () => {
  await fetchPhotos()
})

// Fetch other user's photos
const loadUserPhotos = async (userId: string) => {
  await fetchPhotos(userId)
}

// Load more
const handleLoadMore = async () => {
  if (hasMore.value && !isLoading.value) {
    await loadMore()
  }
}
```

### 3. Manage Albums

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { albums, fetchAlbums, createAlbum, deleteAlbum } = usePhotos()

// Create album
const handleCreateAlbum = async () => {
  try {
    const newAlbum = await createAlbum('My Vacation', 'Photos from my trip')
    console.log('Album created:', newAlbum)
  } catch (error) {
    console.error('Create failed:', error)
  }
}

// Delete album
const handleDeleteAlbum = async (albumId: number) => {
  if (confirm('Are you sure you want to delete this album?')) {
    try {
      await deleteAlbum(albumId)
      console.log('Album deleted')
    } catch (error) {
      console.error('Delete failed:', error)
    }
  }
}
```

### 4. Like Photos

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { photos, toggleLike } = usePhotos()

const handleLike = async (photo: Photo) => {
  try {
    await toggleLike(photo)
    // is_liked and likes_count will be updated automatically
  } catch (error) {
    console.error('Like failed:', error)
  }
}
```

### 5. Edit and Delete Photos

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { updateCaption, deletePhoto } = usePhotos()

// Update caption
const handleUpdateCaption = async (photoId: number, newCaption: string) => {
  try {
    await updateCaption(photoId, newCaption)
    console.log('Caption updated')
  } catch (error) {
    console.error('Update failed:', error)
  }
}

// Delete photo
const handleDeletePhoto = async (photoId: number) => {
  if (confirm('Are you sure you want to delete this photo?')) {
    try {
      await deletePhoto(photoId)
      console.log('Photo deleted')
    } catch (error) {
      console.error('Delete failed:', error)
    }
  }
}
```

---

## Limitations and Conditions

### Upload Limits
- Maximum photos per upload: **10 photos**
- Maximum file size: **10MB per file**
- Accepted formats: **JPEG, PNG, JPG, GIF**

### Access Control
- Photos and albums can be set to **Public** or **Private**
- Public: Visible to all users
- Private: Only visible to owner

### Deletion
- Delete photo: Removes file from storage and database
- Delete album: Removes album and all photos in it

---

## API Response Formats

### List Photos
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "album_id": null,
      "url": "https://example.com/storage/photos/photo1.jpg",
      "thumbnail_url": "https://example.com/storage/photos/thumbnails/thumb1.jpg",
      "caption": "My photo",
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "likes_count": 5,
      "comments_count": 2,
      "is_liked": true,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Upload Photos
```json
{
  "success": true,
  "message": "Photos uploaded successfully",
  "photos": [
    {
      "id": 1,
      "user_id": 123,
      "album_id": null,
      "url": "https://example.com/storage/photos/photo1.jpg",
      "thumbnail_url": "https://example.com/storage/photos/thumbnails/thumb1.jpg",
      "caption": null,
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "likes_count": 0,
      "comments_count": 0,
      "is_liked": false,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ]
}
```

### List Albums
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "name": "My Vacation",
      "description": "Photos from my trip",
      "cover_photo": "https://example.com/storage/photos/cover1.jpg",
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "photos_count": 15,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ]
}
```

---

## Setup and Configuration

### 1. Database Migrations
Run migrations:
```bash
cd api/nuxnanravel
php artisan migrate
```

### 2. Storage Configuration
Ensure storage is configured:
```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### 3. Create Storage Link
```bash
php artisan storage:link
```

---

## Testing

### Test API Endpoints

**1. Test Photo Upload:**
```bash
curl -X POST http://localhost:8000/api/profile/photos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photos[]=@/path/to/photo1.jpg" \
  -F "photos[]=@/path/to/photo2.jpg" \
  -F "caption=My photos"
```

**2. Test View Photos:**
```bash
curl http://localhost:8000/api/profile/photos \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**3. Test Create Album:**
```bash
curl -X POST http://localhost:8000/api/profile/albums \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Album","description":"My photo album"}'
```

---

## Important Notes

1. **Authentication:** All API endpoints require JWT token authentication
2. **Authorization:** Users can only edit/delete their own photos and albums
3. **Privacy:** Check `is_public` flag before displaying photos
4. **Storage:** Ensure sufficient storage space is available
5. **Validation:** All requests are validated to prevent invalid data

---

## Troubleshooting

### Issue: Cannot upload photos
- Check file size (must be under 10MB)
- Check file format (only JPEG, PNG, JPG, GIF)
- Check storage permissions

### Issue: Photos not visible
- Ensure `php artisan storage:link` has been run
- Check `is_public` flag
- Check file permissions

### Issue: Cannot delete photos
- Verify you are the photo owner
- Check storage permissions

---

## Future Enhancements

### Potential features to add:
1. **Photo Tags** - Add tags to photos
2. **Photo Search** - Search photos by tags, caption
3. **Photo Sharing** - Share photos to social media
4. **Photo Editing** - Edit photos (crop, filter)
5. **Photo Download** - Allow photo downloads
6. **Photo Comments** - Comments on photos
7. **Album Collaboration** - Allow other users to contribute to albums
8. **Photo Statistics** - View photo statistics

---

## Summary

The Nuxnan Photos system is fully implemented with:

✅ **Database Models:** Album and Photo
✅ **Controllers:** PhotoController and AlbumController
✅ **API Routes:** All necessary endpoints
✅ **Frontend Composable:** usePhotos for Vue.js
✅ **Documentation:** Complete usage guide

### Next Steps:
1. Test the system thoroughly
2. Create UI components for Photos and Albums
3. Add additional features as needed
4. Optimize performance and user experience

---

**This document was last updated on: January 14, 2026**
