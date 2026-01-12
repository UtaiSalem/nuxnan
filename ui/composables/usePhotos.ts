import { ref, computed } from 'vue'

// Types
export interface Photo {
  id: number
  url: string
  thumbnail_url?: string
  caption?: string
  created_at: string
  likes_count: number
  comments_count: number
  is_liked: boolean
  album_id?: number
}

export interface Album {
  id: number
  name: string
  description?: string
  cover_photo?: string
  photos_count: number
  created_at: string
  updated_at: string
}

export const usePhotos = () => {
  const api = useApi()
  
  // State
  const photos = ref<Photo[]>([])
  const albums = ref<Album[]>([])
  const isLoading = ref(false)
  const isUploading = ref(false)
  const uploadProgress = ref(0)
  const error = ref<string | null>(null)
  
  // Pagination
  const currentPage = ref(1)
  const lastPage = ref(1)
  const hasMore = computed(() => currentPage.value < lastPage.value)

  /**
   * Fetch photos
   */
  const fetchPhotos = async (userId?: string | number, page: number = 1): Promise<Photo[]> => {
    isLoading.value = true
    error.value = null
    
    try {
      const endpoint = userId 
        ? `/api/users/${userId}/photos?page=${page}`
        : `/api/profile/photos?page=${page}`
      
      const response = await api.get(endpoint) as {
        success: boolean
        data?: Photo[]
        photos?: Photo[]
        meta?: { current_page: number; last_page: number }
      }
      
      if (response.success) {
        const photosList = response.data || response.photos || []
        
        if (page === 1) {
          photos.value = photosList
        } else {
          photos.value = [...photos.value, ...photosList]
        }
        
        if (response.meta) {
          currentPage.value = response.meta.current_page
          lastPage.value = response.meta.last_page
        }
        
        return photosList
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดรูปภาพได้'
      console.error('Error fetching photos:', err)
      return []
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch albums
   */
  const fetchAlbums = async (userId?: string | number): Promise<Album[]> => {
    isLoading.value = true
    error.value = null
    
    try {
      const endpoint = userId 
        ? `/api/users/${userId}/albums`
        : `/api/profile/albums`
      
      const response = await api.get(endpoint) as {
        success: boolean
        data?: Album[]
        albums?: Album[]
      }
      
      if (response.success) {
        albums.value = response.data || response.albums || []
        return albums.value
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดอัลบั้มได้'
      console.error('Error fetching albums:', err)
      return []
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Upload photos
   */
  const uploadPhotos = async (files: FileList | File[], albumId?: number): Promise<Photo[]> => {
    isUploading.value = true
    uploadProgress.value = 0
    error.value = null
    
    try {
      const formData = new FormData()
      Array.from(files).forEach((file, index) => {
        formData.append(`photos[${index}]`, file)
      })
      
      if (albumId) {
        formData.append('album_id', String(albumId))
      }
      
      const response = await api.call('/api/profile/photos', {
        method: 'POST',
        body: formData,
        onUploadProgress: (progressEvent: any) => {
          if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          }
        }
      }) as { success: boolean; photos?: Photo[]; data?: Photo[] }
      
      if (response.success) {
        const newPhotos = response.photos || response.data || []
        photos.value = [...newPhotos, ...photos.value]
        return newPhotos
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพโหลดรูปภาพได้'
      console.error('Error uploading photos:', err)
      throw err
    } finally {
      isUploading.value = false
      uploadProgress.value = 0
    }
  }

  /**
   * Delete photo
   */
  const deletePhoto = async (photoId: number): Promise<boolean> => {
    try {
      const response = await api.delete(`/api/profile/photos/${photoId}`) as { success: boolean }
      
      if (response.success) {
        photos.value = photos.value.filter(p => p.id !== photoId)
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถลบรูปภาพได้'
      console.error('Error deleting photo:', err)
      return false
    }
  }

  /**
   * Like/Unlike photo
   */
  const toggleLike = async (photo: Photo): Promise<boolean> => {
    try {
      const endpoint = photo.is_liked 
        ? `/api/photos/${photo.id}/unlike`
        : `/api/photos/${photo.id}/like`
      
      const response = await api.post(endpoint) as { success: boolean }
      
      if (response.success) {
        photo.is_liked = !photo.is_liked
        photo.likes_count += photo.is_liked ? 1 : -1
        return true
      }
      return false
    } catch (err: any) {
      console.error('Error toggling like:', err)
      return false
    }
  }

  /**
   * Update photo caption
   */
  const updateCaption = async (photoId: number, caption: string): Promise<boolean> => {
    try {
      const response = await api.put(`/api/profile/photos/${photoId}`, { caption }) as { 
        success: boolean 
      }
      
      if (response.success) {
        const photo = photos.value.find(p => p.id === photoId)
        if (photo) {
          photo.caption = caption
        }
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพเดทคำอธิบายได้'
      console.error('Error updating caption:', err)
      return false
    }
  }

  /**
   * Create album
   */
  const createAlbum = async (name: string, description?: string): Promise<Album | null> => {
    try {
      const response = await api.post('/api/profile/albums', { name, description }) as {
        success: boolean
        album?: Album
        data?: Album
      }
      
      if (response.success) {
        const newAlbum = response.album || response.data
        if (newAlbum) {
          albums.value.unshift(newAlbum)
          return newAlbum
        }
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถสร้างอัลบั้มได้'
      console.error('Error creating album:', err)
      return null
    }
  }

  /**
   * Delete album
   */
  const deleteAlbum = async (albumId: number): Promise<boolean> => {
    try {
      const response = await api.delete(`/api/profile/albums/${albumId}`) as { success: boolean }
      
      if (response.success) {
        albums.value = albums.value.filter(a => a.id !== albumId)
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถลบอัลบั้มได้'
      console.error('Error deleting album:', err)
      return false
    }
  }

  /**
   * Load more photos
   */
  const loadMore = async (userId?: string | number): Promise<void> => {
    if (!hasMore.value || isLoading.value) return
    await fetchPhotos(userId, currentPage.value + 1)
  }

  /**
   * Clear state
   */
  const clearState = () => {
    photos.value = []
    albums.value = []
    currentPage.value = 1
    lastPage.value = 1
    error.value = null
  }

  return {
    // State
    photos,
    albums,
    isLoading,
    isUploading,
    uploadProgress,
    error,
    hasMore,
    
    // Methods
    fetchPhotos,
    fetchAlbums,
    uploadPhotos,
    deletePhoto,
    toggleLike,
    updateCaption,
    createAlbum,
    deleteAlbum,
    loadMore,
    clearState,
  }
}
