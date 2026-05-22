import { useAuthStore } from '~/stores/auth'

export const useUsageTracking = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  /**
   * Track a generic usage event.
   */
  const trackEvent = async (eventType: string, sourceType?: string, sourceId?: number, context?: Record<string, any>) => {
    if (!authStore.isLoggedIn) return

    try {
      await $fetch(`${apiBase}/api/usage-events`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: {
          event_type: eventType,
          source_type: sourceType,
          source_id: sourceId,
          context: context,
        },
      })
    } catch (err) {
      console.error('Usage tracking error:', err)
    }
  }

  /**
   * Track video progress.
   */
  const trackVideoProgress = (lessonId: number, percent: number) => {
    // Only track at major milestones to avoid flooding
    if ([25, 50, 75, 100].includes(percent)) {
      trackEvent('video_progress', 'lesson', lessonId, { percent })
    }
  }

  /**
   * Track page view.
   */
  const trackPageView = (pageName: string) => {
    trackEvent('page_view', 'page', undefined, { name: pageName })
  }

  return {
    trackEvent,
    trackVideoProgress,
    trackPageView,
  }
}
