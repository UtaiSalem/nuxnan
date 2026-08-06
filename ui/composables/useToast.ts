import { useNotificationStore } from '~/stores/notification'

/**
 * PrimeVue's toast message shape. This app never registers PrimeVue's
 * ToastService, but a lot of call sites were written against its API, so
 * `add()` below accepts it and maps it onto the notification store.
 */
export interface PrimeVueToastMessage {
  severity?: 'success' | 'error' | 'warn' | 'warning' | 'info'
  summary?: string
  detail?: string
  life?: number
}

export const useToast = () => {
  const notificationStore = useNotificationStore()

  return {
    /**
     * Accepts either the PrimeVue message shape ({ severity, summary, detail,
     * life }) or this app's own ({ type, title, message, duration }).
     */
    add: (notification: PrimeVueToastMessage | Record<string, any>) => {
      const isPrimeVueShape = 'severity' in notification
        || 'detail' in notification
        || 'summary' in notification

      if (!isPrimeVueShape) {
        return notificationStore.add(notification as any)
      }

      const message = notification as PrimeVueToastMessage
      const severity = message.severity === 'warn' ? 'warning' : (message.severity ?? 'info')

      // Several call sites pass only `summary`; use it as the body rather
      // than rendering the same text as both title and message.
      return notificationStore.add({
        type: severity,
        title: message.detail ? message.summary : undefined,
        message: message.detail ?? message.summary ?? '',
        duration: message.life,
      })
    },

    success: (message: string, title?: string, duration?: number) => {
      return notificationStore.success(message, title, duration)
    },
    
    error: (message: string, title?: string, duration?: number) => {
      return notificationStore.error(message, title, duration)
    },
    
    warning: (message: string, title?: string, duration?: number) => {
      return notificationStore.warning(message, title, duration)
    },
    
    info: (message: string, title?: string, duration?: number) => {
      return notificationStore.info(message, title, duration)
    },

    // For custom notifications
    show: (notification: any) => {
      return notificationStore.add(notification)
    },

    // Remove specific notification
    remove: (id: string) => {
      notificationStore.remove(id)
    },

    // Clear all
    clear: () => {
      notificationStore.clear()
    }
  }
}
