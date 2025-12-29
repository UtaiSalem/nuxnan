import { ref, computed } from 'vue'
import type { Ref } from 'vue'

// Types
export interface ProfileCompletion {
  percentage: number
  completed_weight: number
  total_weight: number
  missing_fields: Array<{
    field: string
    weight: number
    label: string
  }>
  is_complete: boolean
}

export interface SocialMediaLinks {
  facebook?: string
  twitter?: string
  instagram?: string
  linkedin?: string
  youtube?: string
  tiktok?: string
}

export interface UserProfile {
  id: number
  user_id: number
  username: string
  email: string
  phone: string
  personal_code: string
  reference_code: string
  first_name: string | null
  last_name: string | null
  full_name: string
  bio: string | null
  birthdate: string | null
  gender: string | null
  location: string | null
  website: string | null
  interests: string | null
  avatar: string
  cover_image: string | null
  cover_photo?: string | null
  social_media_links: SocialMediaLinks
  followers: number
  following: number
  friends: number
  friends_count?: number
  posts_count: number
  visits_count?: number
  level: number
  grade?: number  // ระดับชั้น ม.1-6 (1-6)
  experience?: number
  experience_to_next_level?: number
  points: number
  wallet: number
  privacy_settings: 'public' | 'friends' | 'private'
  profile_completion: ProfileCompletion
  join_date: string
  last_login: string | null
  is_verified: boolean
  is_plearnd_admin: boolean
  created_at: string
  updated_at: string
}

export interface FriendshipStatus {
  status: 'self' | 'friends' | 'pending_sent' | 'pending_received' | 'none'
  label: string
}

export interface ProfileUpdateData {
  first_name?: string
  last_name?: string
  bio?: string
  birthdate?: string
  gender?: string
  location?: string
  website?: string
  interests?: string
  social_media_links?: SocialMediaLinks
  privacy_settings?: 'public' | 'friends' | 'private'
}

export const useProfile = () => {
  const api = useApi()
  
  // State
  const profile: Ref<UserProfile | null> = ref(null)
  const isLoading = ref(false)
  const error: Ref<string | null> = ref(null)
  const isSaving = ref(false)

  // Computed
  const isProfileLoaded = computed(() => !!profile.value)
  const profileCompletion = computed(() => profile.value?.profile_completion ?? null)
  const missingFields = computed(() => profile.value?.profile_completion?.missing_fields ?? [])

  /**
   * Fetch current user's profile
   */
  const fetchMyProfile = async (): Promise<UserProfile | null> => {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/api/profile/me') as { success: boolean; data: UserProfile }
      
      if (response.success) {
        profile.value = response.data
        return response.data
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดโปรไฟล์ได้'
      console.error('Error fetching profile:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch user profile by reference code
   */
  const fetchUserProfile = async (referenceCode: string): Promise<{
    profile: UserProfile | null
    friendshipStatus: FriendshipStatus | null
    canViewFullProfile: boolean
    isOwnProfile: boolean
  } | null> => {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/users/${referenceCode}/show`) as {
        success: boolean
        data: UserProfile
        friendship_status: FriendshipStatus
        can_view_full_profile: boolean
        is_own_profile: boolean
      }
      
      if (response.success) {
        return {
          profile: response.data,
          friendshipStatus: response.friendship_status,
          canViewFullProfile: response.can_view_full_profile,
          isOwnProfile: response.is_own_profile,
        }
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดโปรไฟล์ได้'
      console.error('Error fetching user profile:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update profile
   */
  const updateProfile = async (data: ProfileUpdateData): Promise<UserProfile | null> => {
    isSaving.value = true
    error.value = null

    try {
      const response = await api.put('/api/profile/update', data) as {
        success: boolean
        data: UserProfile
        message: string
      }
      
      if (response.success) {
        profile.value = response.data
        return response.data
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพเดทโปรไฟล์ได้'
      console.error('Error updating profile:', err)
      throw err
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Update avatar
   */
  const updateAvatar = async (file: File): Promise<string | null> => {
    isSaving.value = true
    error.value = null

    try {
      const formData = new FormData()
      formData.append('avatar', file)

      const response = await api.call('/api/profile/avatar', {
        method: 'POST',
        body: formData,
      }) as { success: boolean; avatar: string; message: string }
      
      if (response.success && profile.value) {
        profile.value.avatar = response.avatar
        return response.avatar
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพโหลดรูปโปรไฟล์ได้'
      console.error('Error updating avatar:', err)
      throw err
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Update cover image
   */
  const updateCover = async (file: File): Promise<string | null> => {
    isSaving.value = true
    error.value = null

    try {
      const formData = new FormData()
      formData.append('cover', file)

      const response = await api.call('/api/profile/cover', {
        method: 'POST',
        body: formData,
      }) as { success: boolean; cover_image: string; message: string }
      
      if (response.success && profile.value) {
        profile.value.cover_image = response.cover_image
        return response.cover_image
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพโหลดรูปปกได้'
      console.error('Error updating cover:', err)
      throw err
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Get profile completion
   */
  const fetchProfileCompletion = async (): Promise<ProfileCompletion | null> => {
    try {
      const response = await api.get('/api/profile/completion') as {
        success: boolean
        data: ProfileCompletion
      }
      
      if (response.success) {
        if (profile.value) {
          profile.value.profile_completion = response.data
        }
        return response.data
      }
      return null
    } catch (err: any) {
      console.error('Error fetching profile completion:', err)
      return null
    }
  }

  /**
   * Update privacy settings
   */
  const updatePrivacy = async (setting: 'public' | 'friends' | 'private'): Promise<boolean> => {
    isSaving.value = true
    error.value = null

    try {
      const response = await api.put('/api/profile/privacy', {
        privacy_settings: setting,
      }) as { success: boolean; privacy_settings: string }
      
      if (response.success && profile.value) {
        profile.value.privacy_settings = setting
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถอัพเดทการตั้งค่าได้'
      console.error('Error updating privacy:', err)
      return false
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Get user stats
   */
  const fetchStats = async (referenceCode?: string): Promise<{
    posts: number
    followers: number
    following: number
    friends: number
    points: number
    wallet: number
  } | null> => {
    try {
      const endpoint = referenceCode 
        ? `/api/users/${referenceCode}/stats`
        : '/api/profile/stats'
      
      const response = await api.get(endpoint) as {
        success: boolean
        data: {
          posts: number
          followers: number
          following: number
          friends: number
          points: number
          wallet: number
        }
      }
      
      return response.success ? response.data : null
    } catch (err: any) {
      console.error('Error fetching stats:', err)
      return null
    }
  }

  /**
   * Clear profile state
   */
  const clearProfile = () => {
    profile.value = null
    error.value = null
  }

  return {
    // State
    profile,
    isLoading,
    error,
    isSaving,
    
    // Computed
    isProfileLoaded,
    profileCompletion,
    missingFields,
    
    // Methods
    fetchMyProfile,
    fetchUserProfile,
    updateProfile,
    updateAvatar,
    updateCover,
    fetchProfileCompletion,
    updatePrivacy,
    fetchStats,
    clearProfile,
  }
}
