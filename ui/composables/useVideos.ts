/**
 * Videos Management Composable
 * Provides functionality for fetching, uploading and managing videos
 */

export interface Video {
  id: number
  title: string
  description?: string
  thumbnail: string
  video_url: string
  duration: number
  views_count: number
  likes_count: number
  comments_count?: number
  is_liked?: boolean
  created_at: string
  privacy: 'public' | 'friends' | 'private'
  user?: {
    id: number
    name: string
    avatar?: string
  }
}

export interface VideoUploadData {
  title: string
  description?: string
  privacy?: 'public' | 'friends' | 'private'
  file: File
  thumbnail?: File
}

export function useVideos() {
  const api = useApi()

  /**
   * Fetch user videos
   */
  const fetchVideos = async (userId?: string | number, page = 1, perPage = 12) => {
    try {
      const endpoint = userId 
        ? `/api/users/${userId}/videos`
        : `/api/profile/videos`
      
      const response = await api.get(endpoint, { 
        params: { page, per_page: perPage }
      }) as {
        success: boolean
        data?: Video[]
        videos?: Video[]
        meta?: {
          current_page: number
          last_page: number
          total: number
        }
      }
      
      if (response.success) {
        return {
          videos: response.data || response.videos || [],
          meta: response.meta
        }
      }
      
      return { videos: [], meta: null }
    } catch (error) {
      console.error('Error fetching videos:', error)
      return { videos: [], meta: null }
    }
  }

  /**
   * Upload a new video
   */
  const uploadVideo = async (data: VideoUploadData, onProgress?: (progress: number) => void) => {
    try {
      const formData = new FormData()
      formData.append('title', data.title)
      formData.append('file', data.file)
      
      if (data.description) {
        formData.append('description', data.description)
      }
      if (data.privacy) {
        formData.append('privacy', data.privacy)
      }
      if (data.thumbnail) {
        formData.append('thumbnail', data.thumbnail)
      }

      const response = await api.post('/api/profile/videos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent: { loaded: number; total?: number }) => {
          if (progressEvent.total && onProgress) {
            const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
            onProgress(progress)
          }
        }
      }) as { success: boolean; video?: Video }

      return response.success ? response.video : null
    } catch (error) {
      console.error('Error uploading video:', error)
      return null
    }
  }

  /**
   * Delete a video
   */
  const deleteVideo = async (videoId: number) => {
    try {
      const response = await api.delete(`/api/profile/videos/${videoId}`) as { success: boolean }
      return response.success
    } catch (error) {
      console.error('Error deleting video:', error)
      return false
    }
  }

  /**
   * Update video details
   */
  const updateVideo = async (videoId: number, data: Partial<VideoUploadData>) => {
    try {
      const response = await api.put(`/api/profile/videos/${videoId}`, data) as { 
        success: boolean
        video?: Video 
      }
      return response.success ? response.video : null
    } catch (error) {
      console.error('Error updating video:', error)
      return null
    }
  }

  /**
   * Toggle like on a video
   */
  const toggleLike = async (videoId: number) => {
    try {
      const response = await api.post(`/api/videos/${videoId}/like`) as {
        success: boolean
        is_liked: boolean
        likes_count: number
      }
      return response.success ? { is_liked: response.is_liked, likes_count: response.likes_count } : null
    } catch (error) {
      console.error('Error toggling video like:', error)
      return null
    }
  }

  /**
   * Increment view count
   */
  const recordView = async (videoId: number) => {
    try {
      await api.post(`/api/videos/${videoId}/view`)
    } catch (error) {
      // Silent fail for view tracking
    }
  }

  /**
   * Get video details
   */
  const getVideo = async (videoId: number) => {
    try {
      const response = await api.get(`/api/videos/${videoId}`) as {
        success: boolean
        video?: Video
        data?: Video
      }
      return response.success ? (response.video || response.data) : null
    } catch (error) {
      console.error('Error fetching video:', error)
      return null
    }
  }

  return {
    fetchVideos,
    uploadVideo,
    deleteVideo,
    updateVideo,
    toggleLike,
    recordView,
    getVideo
  }
}
