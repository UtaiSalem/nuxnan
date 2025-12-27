import Swal from 'sweetalert2'

// SweetAlert2 composable for Nuxt 3
// Provides consistent alert/confirm dialogs with dark mode support

export const useSweetAlert = () => {
  // Check if dark mode is active
  const isDarkMode = () => {
    if (typeof document === 'undefined') return false
    return document.documentElement.classList.contains('dark')
  }

  // Base configuration for dark mode
  const getDarkModeConfig = () => {
    if (isDarkMode()) {
      return {
        background: '#1a1d2e', // vikinger-dark-100
        color: '#e5e7eb',
        confirmButtonColor: '#8b5cf6', // vikinger-purple
        cancelButtonColor: '#6b7280',
        customClass: {
          popup: 'swal-dark-popup',
          title: 'swal-dark-title',
          htmlContainer: 'swal-dark-content',
        }
      }
    }
    return {
      confirmButtonColor: '#8b5cf6', // vikinger-purple
      cancelButtonColor: '#6b7280',
    }
  }

  // Success alert
  const success = (message: string, title: string = 'สำเร็จ!') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'success',
      timer: 2500,
      timerProgressBar: true,
      showConfirmButton: false,
      ...getDarkModeConfig()
    })
  }

  // Error alert
  const error = (message: string, title: string = 'เกิดข้อผิดพลาด') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'error',
      confirmButtonText: 'ตกลง',
      ...getDarkModeConfig()
    })
  }

  // Warning alert
  const warning = (message: string, title: string = 'คำเตือน') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'warning',
      confirmButtonText: 'ตกลง',
      ...getDarkModeConfig()
    })
  }

  // Info alert
  const info = (message: string, title: string = 'ข้อมูล') => {
    return Swal.fire({
      title,
      text: message,
      icon: 'info',
      confirmButtonText: 'ตกลง',
      ...getDarkModeConfig()
    })
  }

  // Confirm dialog - returns true if confirmed, false otherwise
  const confirm = async (
    message: string, 
    title: string = 'ยืนยันการดำเนินการ',
    options: {
      confirmText?: string
      cancelText?: string
      icon?: 'warning' | 'question' | 'info'
      isDanger?: boolean
    } = {}
  ): Promise<boolean> => {
    const {
      confirmText = 'ยืนยัน',
      cancelText = 'ยกเลิก',
      icon = 'question',
      isDanger = false
    } = options

    const result = await Swal.fire({
      title,
      text: message,
      icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: isDanger ? '#ef4444' : '#8b5cf6',
      reverseButtons: true,
      focusCancel: isDanger,
      ...getDarkModeConfig(),
      // Override confirm button color for danger actions
      ...(isDanger && { confirmButtonColor: '#ef4444' })
    })

    return result.isConfirmed
  }

  // Confirm delete - specialized for delete actions
  const confirmDelete = async (
    itemName: string = 'รายการนี้',
    additionalMessage: string = ''
  ): Promise<boolean> => {
    const message = additionalMessage 
      ? `คุณต้องการลบ${itemName}หรือไม่?\n\n${additionalMessage}`
      : `คุณต้องการลบ${itemName}หรือไม่?`

    return confirm(message, 'ยืนยันการลบ', {
      confirmText: 'ลบ',
      cancelText: 'ยกเลิก',
      icon: 'warning',
      isDanger: true
    })
  }

  // Toast notification (non-blocking)
  const toast = (message: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer
        toast.onmouseleave = Swal.resumeTimer
      },
      ...getDarkModeConfig()
    })

    return Toast.fire({
      icon,
      title: message
    })
  }

  // Loading indicator
  const showLoading = (message: string = 'กำลังดำเนินการ...') => {
    return Swal.fire({
      title: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading()
      },
      ...getDarkModeConfig()
    })
  }

  // Close any open dialog
  const close = () => {
    Swal.close()
  }

  // Input dialog
  const input = async (
    title: string,
    options: {
      inputType?: 'text' | 'textarea' | 'email' | 'password' | 'number'
      placeholder?: string
      inputValue?: string
      confirmText?: string
      cancelText?: string
      inputValidator?: (value: string) => string | null
    } = {}
  ): Promise<string | null> => {
    const {
      inputType = 'text',
      placeholder = '',
      inputValue = '',
      confirmText = 'ตกลง',
      cancelText = 'ยกเลิก',
      inputValidator
    } = options

    const result = await Swal.fire({
      title,
      input: inputType,
      inputPlaceholder: placeholder,
      inputValue,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true,
      inputValidator: inputValidator ? (value) => {
        return inputValidator(value as string)
      } : undefined,
      ...getDarkModeConfig()
    })

    return result.isConfirmed ? (result.value as string) : null
  }

  return {
    success,
    error,
    warning,
    info,
    confirm,
    confirmDelete,
    toast,
    showLoading,
    close,
    input,
    // Expose raw Swal for advanced usage
    Swal
  }
}
