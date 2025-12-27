import { defineStore } from 'pinia'

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message: string
  duration?: number
  icon?: string
}

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [] as Notification[]
  }),

  actions: {
    add(notification: Omit<Notification, 'id'>) {
      const id = `${Date.now()}-${Math.random()}`
      const duration = notification.duration ?? 3000

      // Default icons based on type
      const defaultIcons = {
        success: 'fluent:checkmark-circle-24-filled',
        error: 'fluent:dismiss-circle-24-filled',
        warning: 'fluent:warning-24-filled',
        info: 'fluent:info-24-filled'
      }

      const newNotification: Notification = {
        id,
        icon: notification.icon || defaultIcons[notification.type],
        ...notification,
        duration
      }

      this.notifications.push(newNotification)

      // Auto remove after duration
      if (duration > 0) {
        setTimeout(() => {
          this.remove(id)
        }, duration)
      }

      return id
    },

    remove(id: string) {
      const index = this.notifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.notifications.splice(index, 1)
      }
    },

    clear() {
      this.notifications = []
    },

    // Convenience methods
    success(message: string, title?: string, duration?: number) {
      return this.add({ type: 'success', message, title, duration })
    },

    error(message: string, title?: string, duration?: number) {
      return this.add({ type: 'error', message, title, duration })
    },

    warning(message: string, title?: string, duration?: number) {
      return this.add({ type: 'warning', message, title, duration })
    },

    info(message: string, title?: string, duration?: number) {
      return this.add({ type: 'info', message, title, duration })
    }
  }
})
