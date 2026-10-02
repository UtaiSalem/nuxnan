/**
 * Turn a raw $fetch / ofetch error into a user-friendly alert.
 *
 * A friendly headline is always shown. Server (5xx) errors and raw
 * database/stack-trace dumps (e.g. "SQLSTATE[...] ...") are hidden behind a
 * collapsible technical detail instead of being printed as the headline, so
 * admins get a clean message but can still inspect the cause.
 */
export interface ParsedApiError {
  message: string
  detail: string
}

const looksTechnical = (text: string): boolean => {
  if (!text) return false
  return /SQLSTATE|Exception|stack trace|\bSQL:\b|Call to |Undefined |syntax error/i.test(text) || text.length > 180
}

export const useApiError = () => {
  const parseApiError = (err: any, fallback = 'เกิดข้อผิดพลาด กรุณาลองใหม่'): ParsedApiError => {
    const status: number | undefined =
      err?.response?.status ?? err?.statusCode ?? err?.data?.status

    const serverMessage: string | undefined =
      err?.data?.message ?? err?.data?.error ?? err?.message

    // Validation errors (422) often carry field messages — surface the first.
    const validationErrors = err?.data?.errors
    if (status === 422 && validationErrors && typeof validationErrors === 'object') {
      const first = Object.values(validationErrors)[0]
      const firstMsg = Array.isArray(first) ? first[0] : first
      if (firstMsg) return { message: String(firstMsg), detail: '' }
    }

    // Server error or a raw technical dump → friendly headline + detail.
    if ((status && status >= 500) || looksTechnical(serverMessage || '')) {
      return {
        message: 'เกิดข้อผิดพลาดในระบบ ไม่สามารถดำเนินการได้ กรุณาลองใหม่หรือติดต่อผู้ดูแล',
        detail: serverMessage || ''
      }
    }

    return { message: serverMessage || fallback, detail: '' }
  }

  return { parseApiError }
}
