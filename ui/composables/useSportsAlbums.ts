/**
 * จัดการอัลบั้มรูปภาพกีฬาสี
 * 
 * หมายเหตุ: ฝั่ง API ย่อรูปให้เองแล้ว (ยาวสุด 2048 + thumbnail 400x400 เป็น JPEG)
 * หน้าจอ ห้ามย่อ/แปลงไฟล์เอง และต้องแสดงด้วย thumbnail_url ในกริด
 * url เฉพาะตอนเปิดดูเต็ม
 */

export interface SportsPhoto {
  id: number
  album_id: number
  edition_id: number
  academy_id: number
  path: string
  thumbnail_path: string | null
  caption: string | null
  width: number | null
  height: number | null
  size: number
  mime_type: string | null
  display_order: number
  uploaded_by_user_id: number
  created_at?: string
  /** accessor จากฝั่ง API — ใช้ตัวนี้แสดงผลเสมอ ห้ามประกอบ path เอง */
  url: string
  thumbnail_url: string
}

export interface SportsAlbum {
  id: number
  edition_id: number
  academy_id: number
  discipline_id: number | null
  house_group_id: number | null
  name: string
  description: string | null
  cover_photo_id: number | null
  is_public: boolean
  created_by_user_id: number
  created_at?: string
  photos_count?: number
  cover_photo?: SportsPhoto | null
  photos?: SportsPhoto[]
}

export const useSportsAlbums = () => {
  const api = useApi()

  const base = (academyId: number | string, editionId: number | string) =>
    `/api/academies/${academyId}/sports-editions/${editionId}`

  const listAlbums = (academyId: number, editionId: number) =>
    api.get<SportsAlbum[]>(`${base(academyId, editionId)}/albums`)

  const createAlbum = (academyId: number, editionId: number, payload: Partial<SportsAlbum>) =>
    api.post<SportsAlbum>(`${base(academyId, editionId)}/albums`, payload)

  const showAlbum = (academyId: number, editionId: number, albumId: number) =>
    api.get<SportsAlbum>(`${base(academyId, editionId)}/albums/${albumId}`)

  const updateAlbum = (
    academyId: number,
    editionId: number,
    albumId: number,
    payload: Partial<SportsAlbum>
  ) => api.put<SportsAlbum>(`${base(academyId, editionId)}/albums/${albumId}`, payload)

  const deleteAlbum = (academyId: number, editionId: number, albumId: number) =>
    api.delete(`${base(academyId, editionId)}/albums/${albumId}`)

  const listPhotos = (academyId: number, editionId: number, albumId: number) =>
    api.get<SportsPhoto[]>(`${base(academyId, editionId)}/albums/${albumId}/photos`)

  const uploadPhotos = (
    academyId: number,
    editionId: number,
    albumId: number,
    files: File[],
    captions?: string[]
  ) => {
    const form = new FormData()
    files.forEach((file, i) => {
      form.append('photos[]', file)
      form.append(`captions[${i}]`, captions?.[i] ?? '')
    })
    return api.post<SportsPhoto[]>(`${base(academyId, editionId)}/albums/${albumId}/photos`, form)
  }

  const updatePhoto = (
    academyId: number,
    editionId: number,
    photoId: number,
    payload: Partial<SportsPhoto>
  ) => api.put<SportsPhoto>(`${base(academyId, editionId)}/photos/${photoId}`, payload)

  const deletePhoto = (academyId: number, editionId: number, photoId: number) =>
    api.delete(`${base(academyId, editionId)}/photos/${photoId}`)

  /** แปลงขนาดไฟล์ให้เป็นข้อความอ่านง่าย เช่น 1536 -> '1.5 KB' */
  const formatFileSize = (bytes: number | null | undefined): string => {
    if (bytes == null) return ''
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
  }

  return {
    listAlbums,
    createAlbum,
    showAlbum,
    updateAlbum,
    deleteAlbum,
    listPhotos,
    uploadPhotos,
    updatePhoto,
    deletePhoto,
    formatFileSize,
  }
}
