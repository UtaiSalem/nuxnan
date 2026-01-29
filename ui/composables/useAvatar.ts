/**
 * useAvatar - Composable for generating user avatar URLs
 * Generates avatar from name initials if no profile image exists
 * 
 * Uses centralized constants from ~/utils/avatarConstants
 */

import {
  DEFAULT_AVATAR,
  UI_AVATARS_CONFIG,
  AVATAR_SIZES,
  type AvatarSize,
  getInitialsFromName,
  handleAvatarError,
  getAvatarAltText,
} from '~/utils/avatarConstants'

// Generate consistent color from name
const getColorFromName = (name: string): string => {
  if (!name) return 'f5f5f5' // default soft white
  
  // Soft, eye-friendly pastel colors
  const colors = [
    'f5f5f5', // soft white/cream
    'fafafa', // near white
    'f0f0f0', // light gray
    'e8e8e8', // slightly darker gray
    'f5f0e8', // warm cream
    'f0f5f0', // soft green tint
    'f0f0f5', // soft blue tint
    'f5f0f5', // soft purple tint
    'f5f5f0', // soft yellow tint
    'f0f5f5', // soft cyan tint
  ]
  
  // Simple hash from name
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  
  return colors[Math.abs(hash) % colors.length]
}

export const useAvatar = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase

  /**
   * Get avatar URL for a user with standardized size and fallback
   * @param userData - User object with possible avatar fields
   * @param size - Size of avatar (default: 150 for consistency)
   * @returns Avatar URL string
   */
  const getAvatarUrl = (userData: any, size: number = 150): string => {
    if (!userData) return generateFromName('User', size)

    // Check various possible avatar fields
    const avatarPath = userData.avatar || userData.profile_photo_path || userData.profile_photo_url

    if (avatarPath) {
      // If already a full URL
      if (avatarPath.startsWith('http')) return avatarPath

      // If starts with /storage, likely from backend
      if (avatarPath.startsWith('/storage')) return `${apiBase}${avatarPath}`

      // If starts with / but not storage, assume local asset
      if (avatarPath.startsWith('/')) return avatarPath

      // Otherwise construct the storage URL
      return `${apiBase}/storage/${avatarPath}`
    }

    // No avatar found - generate from name
    const name = userData.name || userData.username || 'User'
    return generateFromName(name, size)
  }

  /**
   * Generate avatar from name using UI Avatars API
   * Uses same style as newsfeed/register pages for consistency
   * @param name - User's name
   * @param size - Size of avatar
   * @returns Generated avatar URL
   */
  const generateFromName = (name: string, size: number = 128): string => {
    const initials = getInitialsFromName(name)
    const { baseUrl, defaultBackground, defaultColor } = UI_AVATARS_CONFIG
    
    return `${baseUrl}?name=${encodeURIComponent(initials)}&size=${size}&color=${defaultColor}&background=${defaultBackground}`
  }

  /**
   * Get initials from name
   * @param name - User's name
   * @returns Initials string (1-2 characters)
   */
  const getNameInitials = (name: string): string => {
    return getInitialsFromName(name)
  }

  /**
   * Get consistent color for a name
   * @param name - User's name
   * @returns Hex color string (without #)
   */
  const getNameColor = (name: string): string => {
    return getColorFromName(name)
  }

  /**
   * Handle image error - fallback to default avatar
   * Use this on @error of img elements
   * @param event - Error event from img element
   */
  const onImageError = (event: Event): void => {
    handleAvatarError(event)
  }

  /**
   * Get alt text for avatar accessibility
   * @param name - User's name
   * @returns Appropriate alt text
   */
  const getAltText = (name?: string): string => {
    return getAvatarAltText(name)
  }

  /**
   * Get default avatar URL
   * @returns Standard default avatar path
   */
  const getDefaultAvatar = (): string => {
    return DEFAULT_AVATAR
  }

  /**
   * Get avatar size in pixels
   * @param size - Size key (xs, sm, md, lg, xl, etc)
   * @returns Size in pixels
   */
  const getAvatarSizePixels = (size: AvatarSize): number => {
    return AVATAR_SIZES[size] || AVATAR_SIZES.md
  }

  return {
    getAvatarUrl,
    generateFromName,
    getNameInitials,
    getNameColor,
    onImageError,
    getAltText,
    getDefaultAvatar,
    getAvatarSizePixels,
  }
}
