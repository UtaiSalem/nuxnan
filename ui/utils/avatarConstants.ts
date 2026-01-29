/**
 * Avatar Configuration Constants
 * Single source of truth for avatar settings across the application
 */

// Default avatar path (standardized)
export const DEFAULT_AVATAR = '/images/default-avatar.png'

// Default cover image
export const DEFAULT_COVER = '/images/default-cover.jpg'

// Avatar sizes in pixels
export const AVATAR_SIZES = {
  xs: 24,    // Tiny icons, inline mentions
  sm: 32,    // Small lists, comments
  md: 40,    // Standard cards, navigation
  lg: 48,    // Profile cards
  xl: 64,    // Headers, larger cards
  '2xl': 96,   // Profile sections
  '3xl': 128,  // Profile pages
  '4xl': 150,  // Large preview, settings
} as const

// Tailwind classes for sizes
export const AVATAR_SIZE_CLASSES = {
  xs: 'w-6 h-6',
  sm: 'w-8 h-8',
  md: 'w-10 h-10',
  lg: 'w-12 h-12',
  xl: 'w-16 h-16',
  '2xl': 'w-24 h-24',
  '3xl': 'w-32 h-32',
  '4xl': 'w-[150px] h-[150px]',
} as const

// Supported image formats for upload
export const SUPPORTED_IMAGE_FORMATS = ['jpeg', 'jpg', 'png', 'gif', 'webp']

// Maximum file sizes (in bytes)
export const MAX_AVATAR_SIZE = 5 * 1024 * 1024  // 5MB
export const MAX_COVER_SIZE = 10 * 1024 * 1024  // 10MB

// UI Avatars API configuration
export const UI_AVATARS_CONFIG = {
  baseUrl: 'https://ui-avatars.com/api/',
  defaultSize: 128,
  defaultBackground: 'EBF4FF',
  defaultColor: '7F9CF5',
} as const

// Type definitions
export type AvatarSize = keyof typeof AVATAR_SIZES
export type AvatarSizeClass = keyof typeof AVATAR_SIZE_CLASSES

/**
 * Generate fallback avatar URL using UI Avatars API
 * @param name - User's name for initials
 * @param size - Size in pixels
 * @returns URL string for generated avatar
 */
export const generateFallbackAvatar = (name: string, size: number = 128): string => {
  const initials = getInitialsFromName(name)
  const { baseUrl, defaultBackground, defaultColor } = UI_AVATARS_CONFIG
  return `${baseUrl}?name=${encodeURIComponent(initials)}&size=${size}&background=${defaultBackground}&color=${defaultColor}`
}

/**
 * Get initials from a name
 * @param name - Full name
 * @returns 1-2 character initials
 */
export const getInitialsFromName = (name: string): string => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
  }
  return name.charAt(0).toUpperCase()
}

/**
 * Handle image load error - set to default avatar
 * @param event - Error event from img element
 */
export const handleAvatarError = (event: Event): void => {
  const target = event.target as HTMLImageElement
  if (target && target.src !== DEFAULT_AVATAR) {
    target.src = DEFAULT_AVATAR
  }
}

/**
 * Generate appropriate alt text for accessibility
 * @param name - User's name
 * @returns Alt text string
 */
export const getAvatarAltText = (name?: string): string => {
  if (!name) return 'User avatar'
  return `Avatar of ${name}`
}
