import { useNotificationStore } from '~/stores/notification'

export const useToast = () => {
  const notificationStore = useNotificationStore()

  return {
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
