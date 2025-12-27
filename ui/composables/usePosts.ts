import { useAuthStore } from '~/stores/auth'

// Type definitions for post options
export interface PostLocation {
  name: string
  address?: string
  city?: string
  state?: string
  country?: string
  postal_code?: string
  latitude?: number
  longitude?: number
  place_id?: string
  place_type?: string
}

export interface PostFeeling {
  id?: number
  name: string
  name_th?: string
  icon: string
  category?: string
}

export interface PostActivity {
  type: string
  text?: string
}

export interface PostBackground {
  id?: number
  name?: string
  background_color?: string
  background_gradient?: string
  text_color?: string
}

export interface CreatePostOptions {
  images?: File[]
  privacy_settings?: number
  location?: PostLocation
  location_name?: string
  feeling?: string
  feeling_icon?: string
  activity_type?: string
  activity_text?: string
  background_color?: string
  background_gradient?: string
  text_color?: string
  tagged_users?: number[]
  scheduled_at?: string
  comments_disabled?: boolean
}

export interface UpdatePostOptions extends CreatePostOptions {
  delete_images?: number[]
  image_order?: number[]
  remove_feeling?: boolean
  remove_background?: boolean
}

export interface FeelingItem {
  id: number
  name: string
  name_th: string
  icon: string
  category: string
  sort_order: number
  is_active: boolean
}

export interface ActivityTypeItem {
  id: number
  name: string
  name_th: string
  icon: string
  category: string
  sort_order: number
  is_active: boolean
}

export interface BackgroundItem {
  id: number
  name: string
  type: string
  background_color?: string
  background_gradient?: string
  text_color: string
  text_alignment: string
  font_family?: string
  category: string
  is_active: boolean
  is_premium: boolean
  sort_order: number
}

export const usePosts = () => {
  const feedStore = useFeedStore()
  const { $apiFetch } = useNuxtApp()
  const authStore = useAuthStore()
  
  // Cached data
  const feelings = ref<FeelingItem[]>([])
  const activityTypes = ref<ActivityTypeItem[]>([])
  const backgrounds = ref<BackgroundItem[]>([])
  const isLoadingOptions = ref(false)

  const fetchPosts = async () => {
    try {
      const response = await $apiFetch('/api/newsfeed/activities')
      if (response && response.data) {
        feedStore.setPosts(response.data)
      }
    } catch (error) {
      console.error('Error fetching posts:', error)
      // Fallback to empty array on error
      feedStore.setPosts([])
    }
  }

  // Fetch feelings list
  const fetchFeelings = async (): Promise<FeelingItem[]> => {
    if (feelings.value.length > 0) return feelings.value
    
    try {
      const response = await $apiFetch('/api/posts-feelings')
      if (response.success) {
        const data = response.data || response.feelings || []
        feelings.value = data
        return data
      }
      return []
    } catch (error) {
      console.error('Error fetching feelings:', error)
      return []
    }
  }

  // Fetch activity types list
  const fetchActivityTypes = async (): Promise<ActivityTypeItem[]> => {
    if (activityTypes.value.length > 0) return activityTypes.value
    
    try {
      const response = await $apiFetch('/api/posts-activity-types')
      if (response.success) {
        const data = response.data || response.activity_types || []
        activityTypes.value = data
        return data
      }
      return []
    } catch (error) {
      console.error('Error fetching activity types:', error)
      return []
    }
  }

  // Fetch backgrounds list
  const fetchBackgrounds = async (): Promise<BackgroundItem[]> => {
    if (backgrounds.value.length > 0) return backgrounds.value
    
    try {
      const response = await $apiFetch('/api/posts-backgrounds')
      if (response.success) {
        const data = response.data || []
        backgrounds.value = data
        return data
      }
      return []
    } catch (error) {
      console.error('Error fetching backgrounds:', error)
      return []
    }
  }

  // Fetch all post options at once
  const fetchPostOptions = async () => {
    if (isLoadingOptions.value) return
    isLoadingOptions.value = true
    
    try {
      const response = await $apiFetch('/api/posts-options')
      if (response.success && response.data) {
        feelings.value = response.data.feelings || []
        activityTypes.value = response.data.activity_types || []
        backgrounds.value = response.data.backgrounds || []
      }
    } catch (error) {
      console.error('Error fetching post options:', error)
    } finally {
      isLoadingOptions.value = false
    }
  }

  const createPost = async (content: string, options: CreatePostOptions = {}) => {
    try {
      const formData = new FormData()
      formData.append('content', content)
      formData.append('privacy_settings', String(options.privacy_settings ?? 3))
      formData.append('point', '1')
      
      // Images
      if (options.images && options.images.length > 0) {
        options.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image)
        })
      }

      // Location
      if (options.location_name) {
        formData.append('location_name', options.location_name)
      }
      if (options.location) {
        formData.append('location[name]', options.location.name)
        if (options.location.address) formData.append('location[address]', options.location.address)
        if (options.location.city) formData.append('location[city]', options.location.city)
        if (options.location.state) formData.append('location[state]', options.location.state)
        if (options.location.country) formData.append('location[country]', options.location.country)
        if (options.location.latitude) formData.append('location[latitude]', String(options.location.latitude))
        if (options.location.longitude) formData.append('location[longitude]', String(options.location.longitude))
        if (options.location.place_id) formData.append('location[place_id]', options.location.place_id)
      }

      // Feeling/Activity
      if (options.feeling) {
        formData.append('feeling', options.feeling)
      }
      if (options.feeling_icon) {
        formData.append('feeling_icon', options.feeling_icon)
      }
      if (options.activity_type) {
        formData.append('activity_type', options.activity_type)
      }
      if (options.activity_text) {
        formData.append('activity_text', options.activity_text)
      }

      // Background
      if (options.background_color) {
        formData.append('background_color', options.background_color)
      }
      if (options.background_gradient) {
        formData.append('background_gradient', options.background_gradient)
      }
      if (options.text_color) {
        formData.append('text_color', options.text_color)
      }

      // Tagged users
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId))
        })
      }

      // Scheduling
      if (options.scheduled_at) {
        formData.append('scheduled_at', options.scheduled_at)
      }

      // Comments disabled
      if (options.comments_disabled !== undefined) {
        formData.append('comments_disabled', options.comments_disabled ? '1' : '0')
      }

      const response = await $apiFetch('/api/posts', {
        method: 'POST',
        body: formData,
      })

      if (response.success && response.activity) {
        feedStore.addPost(response.activity)
        // Update user points
        if (authStore.user) {
          authStore.user.pp -= 180
        }
      }

      return response
    } catch (error) {
      console.error('Error creating post:', error)
      throw error
    }
  }

  const updatePost = async (postId: number, content: string, options: UpdatePostOptions = {}) => {
    try {
      const formData = new FormData()
      formData.append('_method', 'PUT')
      formData.append('content', content)
      
      if (options.privacy_settings !== undefined) {
        formData.append('privacy_settings', String(options.privacy_settings))
      }
      
      // New Images
      if (options.images && options.images.length > 0) {
        options.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image)
        })
      }

      // Delete images
      if (options.delete_images && options.delete_images.length > 0) {
        options.delete_images.forEach((imageId, index) => {
          formData.append(`delete_images[${index}]`, String(imageId))
        })
      }

      // Image order
      if (options.image_order && options.image_order.length > 0) {
        options.image_order.forEach((imageId, index) => {
          formData.append(`image_order[${index}]`, String(imageId))
        })
      }

      // Remove feeling/background
      if (options.remove_feeling) {
        formData.append('remove_feeling', '1')
      }
      if (options.remove_background) {
        formData.append('remove_background', '1')
      }

      // Location
      if (options.location_name) {
        formData.append('location_name', options.location_name)
      }

      // Feeling/Activity (only if not removing)
      if (!options.remove_feeling) {
        if (options.feeling) {
          formData.append('feeling', options.feeling)
        }
        if (options.feeling_icon) {
          formData.append('feeling_icon', options.feeling_icon)
        }
        if (options.activity_type) {
          formData.append('activity_type', options.activity_type)
        }
        if (options.activity_text) {
          formData.append('activity_text', options.activity_text)
        }
      }

      // Background (only if not removing)
      if (!options.remove_background) {
        if (options.background_color) {
          formData.append('background_color', options.background_color)
        }
        if (options.background_gradient) {
          formData.append('background_gradient', options.background_gradient)
        }
        if (options.text_color) {
          formData.append('text_color', options.text_color)
        }
      }

      // Tagged users
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId))
        })
      }

      // Comments disabled
      if (options.comments_disabled !== undefined) {
        formData.append('comments_disabled', options.comments_disabled ? '1' : '0')
      }

      const response = await $apiFetch(`/api/posts/${postId}`, {
        method: 'POST', // Using POST with _method override for FormData
        body: formData,
      })

      if (response.success && response.post) {
        // Update post in feed store
        feedStore.updatePost(postId, response.post)
      }

      return response
    } catch (error) {
      console.error('Error updating post:', error)
      throw error
    }
  }

  return {
    // Data
    feelings,
    activityTypes,
    backgrounds,
    isLoadingOptions,
    // Methods
    fetchPosts,
    fetchFeelings,
    fetchActivityTypes,
    fetchBackgrounds,
    fetchPostOptions,
    createPost,
    updatePost
  }
}
