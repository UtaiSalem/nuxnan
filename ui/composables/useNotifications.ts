/**
 * Composable สำหรับจัดการ Notifications
 */
export interface ApiNotification {
  id: number
  user_id: number
  type: string
  content: string
  action_url: string | null
  read_status: boolean
  sender_id: number | null
  sender?: {
    id: number
    name: string
    avatar: string | null
  }
  related_id: number | null
  metadata: Record<string, any> | null
  icon: string
  color: string
  created_at: string
  updated_at: string
}

export interface NotificationsResponse {
  notifications: ApiNotification[]
  unread_count: number
}

export const useNotifications = () => {
  const api = useApi()
  
  // State
  const notifications = ref<ApiNotification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Notification type labels
  const typeLabels: Record<string, string> = {
    grade_published: 'ประกาศเกรด',
    grade_finalized: 'ยืนยันเกรด',
    appeal_submitted: 'คำร้องอุทธรณ์',
    appeal_responded: 'ตอบกลับอุทธรณ์',
    certificate_issued: 'ใบประกาศนียบัตร',
    eligibility_unlocked: 'ปลดล็อคสิทธิ์สอบ',
    remediation_opened: 'เปิดซ่อมเสริม',
    remediation_completed: 'ผ่านซ่อมเสริม',
    general: 'ทั่วไป',
    course: 'รายวิชา',
    assignment: 'งาน',
    quiz: 'แบบทดสอบ',
  }

  // Color classes
  const colorClasses: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
    emerald: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400',
    cyan: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900 dark:text-cyan-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400',
    teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900 dark:text-teal-400',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400',
  }

  /**
   * Fetch recent notifications (for dropdown)
   */
  const fetchRecent = async (limit: number = 10): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const res: any = await api.get(`/api/notifications/recent?limit=${limit}`)
      if (res.success) {
        notifications.value = res.data.notifications || []
        unreadCount.value = res.data.unread_count || 0
      }
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดการแจ้งเตือน'
      console.error('Failed to fetch notifications:', err)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch unread count only
   */
  const fetchUnreadCount = async (): Promise<void> => {
    try {
      const res: any = await api.get('/api/notifications/unread-count')
      if (res.success) {
        unreadCount.value = res.data.count || 0
      }
    } catch (err) {
      console.error('Failed to fetch unread count:', err)
    }
  }

  /**
   * Mark a notification as read
   */
  const markAsRead = async (notificationId: number): Promise<void> => {
    try {
      await api.post(`/api/notifications/${notificationId}/read`)
      
      // Update local state
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification && !notification.read_status) {
        notification.read_status = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      console.error('Failed to mark as read:', err)
    }
  }

  /**
   * Mark all notifications as read
   */
  const markAllAsRead = async (): Promise<void> => {
    try {
      await api.post('/api/notifications/mark-all-read')
      
      // Update local state
      notifications.value.forEach(n => {
        n.read_status = true
      })
      unreadCount.value = 0
    } catch (err) {
      console.error('Failed to mark all as read:', err)
    }
  }

  /**
   * Delete a notification
   */
  const deleteNotification = async (notificationId: number): Promise<void> => {
    try {
      await api.delete(`/api/notifications/${notificationId}`)
      
      // Update local state
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index !== -1) {
        const notification = notifications.value[index]
        if (!notification.read_status) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch (err) {
      console.error('Failed to delete notification:', err)
    }
  }

  /**
   * Get type label
   */
  const getTypeLabel = (type: string): string => {
    return typeLabels[type] || type
  }

  /**
   * Get color class for icon background
   */
  const getColorClass = (color: string): string => {
    return colorClasses[color] || colorClasses.gray
  }

  /**
   * Format relative time
   */
  const formatRelativeTime = (dateString: string): string => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'เมื่อสักครู่'
    if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`
    if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`
    if (diffDays < 7) return `${diffDays} วันที่แล้ว`
    
    return date.toLocaleDateString('th-TH', { 
      day: 'numeric', 
      month: 'short',
      year: diffDays > 365 ? 'numeric' : undefined
    })
  }

  /**
   * Navigate to notification action
   */
  const navigateTo = async (notification: Notification): Promise<void> => {
    // Mark as read first
    if (!notification.read_status) {
      await markAsRead(notification.id)
    }
    
    // Navigate if action_url exists
    if (notification.action_url) {
      await navigateTo(notification.action_url as any)
    }
  }

  return {
    // State
    notifications,
    unreadCount,
    isLoading,
    error,
    
    // Actions
    fetchRecent,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    navigateTo,
    
    // Helpers
    getTypeLabel,
    getColorClass,
    formatRelativeTime,
  }
}
