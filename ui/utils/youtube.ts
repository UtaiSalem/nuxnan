/**
 * YouTube Utility Functions
 * Centralized logic for parsing YouTube URLs and constructing media links.
 */

/**
 * Extract YouTube video ID from various URL formats or returns the ID directly if it is already an 11-char string.
 */
export const getYoutubeVideoId = (url: string | null | undefined): string | null => {
  if (!url) return null

  const trimmed = url.trim()

  // If it's already just an 11-character video ID
  if (/^[a-zA-Z0-9_-]{11}$/.test(trimmed)) {
    return trimmed
  }

  // Various YouTube URL patterns
  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/, // youtube.com/watch?v=ID
    /youtu\.be\/([a-zA-Z0-9_-]{11})/, // youtu.be/ID
    /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/, // youtube.com/embed/ID
    /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/, // youtube.com/v/ID
    /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/, // youtube.com/shorts/ID
  ]

  for (const pattern of patterns) {
    const match = trimmed.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

/**
 * Returns the high-resolution YouTube thumbnail URL for a given video ID.
 */
export const getYoutubeThumbnailUrl = (videoId: string | null | undefined): string | null => {
  if (!videoId) return null
  return `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`
}

/**
 * Returns the YouTube embed URL with privacy-enhanced mode (youtube-nocookie.com).
 */
export const getYoutubeEmbedUrl = (videoId: string | null | undefined, autoplay: boolean = true): string | null => {
  if (!videoId) return null
  const params = new URLSearchParams({
    rel: '0',
    modestbranding: '1',
    playsinline: '1',
  })
  if (autoplay) {
    params.set('autoplay', '1')
  }
  return `https://www.youtube-nocookie.com/embed/${videoId}?${params.toString()}`
}
