/**
 * School Events / Activities Composable
 * กิจกรรมโรงเรียน — ครั้งเดียว (ลงทะเบียน), รายภาคเรียน/ต่อเนื่อง (เช็คชื่อทุกนัด)
 */
export const useSchoolEvents = () => {
  const api = useApi()

  // ============================================================
  // EVENTS
  // ============================================================

  const getEvents = (academyId: number, params?: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events`, { params })

  const getUpcomingEvents = (academyId: number, limit?: number) =>
    api.call(`/api/academies/${academyId}/events/upcoming`, { params: { limit } })

  const getMySchedule = (academyId: number, params?: { start?: string; end?: string }) =>
    api.call(`/api/academies/${academyId}/events/my-schedule`, { params })

  const getEvent = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}`)

  const createEvent = (academyId: number, data: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events`, { method: 'POST', body: data })

  const updateEvent = (academyId: number, eventId: number, data: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events/${eventId}`, { method: 'PATCH', body: data })

  const publishEvent = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/publish`, { method: 'POST' })

  const cancelEvent = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/cancel`, { method: 'POST' })

  const deleteEvent = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}`, { method: 'DELETE' })

  // ============================================================
  // ONE-TIME EVENTS — EventRegistration (attendance_pattern = one_time)
  // ============================================================

  const registerForEvent = (academyId: number, eventId: number, data?: { guests?: number; guest_names?: string[]; notes?: string }) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/register`, { method: 'POST', body: data })

  const cancelEventRegistration = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/cancel-registration`, { method: 'POST' })

  const getEventRegistrations = (academyId: number, eventId: number, params?: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/registrations`, { params })

  const confirmEventRegistration = (academyId: number, eventId: number, registrationId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/registrations/${registrationId}/confirm`, { method: 'POST' })

  const markEventRegistrationAttendance = (academyId: number, eventId: number, registrationId: number, attended: boolean) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/registrations/${registrationId}/attendance`, {
      method: 'POST',
      body: { attended },
    })

  // ============================================================
  // SEMESTER / RECURRING EVENTS — ActivityEnrollment + ActivitySession
  // (attendance_pattern = semester | recurring — e.g. ชมรม, ลูกเสือ, ละหมาดรายวัน)
  // ============================================================

  const enrollInEvent = (academyId: number, eventId: number, data?: { semester?: string; academic_year?: string; remarks?: string }) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/enroll`, { method: 'POST', body: data })

  const getSession = (academyId: number, eventId: number, sessionId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}`)

  const refreshSessionQr = (academyId: number, eventId: number, sessionId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}/refresh-qr`, { method: 'POST' })

  const getEventSessions = (academyId: number, eventId: number, params?: { from?: string; to?: string; status?: string; q?: string; per_page?: number; page?: number }) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions`, { params })

  const createEventSession = (academyId: number, eventId: number, data: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions`, { method: 'POST', body: data })

  const updateEventSession = (academyId: number, eventId: number, sessionId: number, data: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}`, { method: 'PATCH', body: data })

  const deleteEventSession = (academyId: number, eventId: number, sessionId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}`, { method: 'DELETE' })

  const getEventRoster = (academyId: number, eventId: number, params?: { session_id?: number; q?: string; per_page?: number; page?: number }) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/roster`, { params })

  const getEventAudienceCount = (academyId: number, eventId: number) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/audience-count`)

  // Always returns a blob — the endpoint also serves format=json, but reach for api.call
  // for that, otherwise the JSON arrives wrapped in a Blob nobody can read.
  const getEventAttendanceReport = (academyId: number, eventId: number, params?: { from?: string; to?: string; format?: 'xlsx' }) =>
    api.getBlob(`/api/academies/${academyId}/events/${eventId}/attendance-report`, { params })

  // Student self-check-in via QR token
  const checkInSession = (academyId: number, eventId: number, sessionId: number, qrToken: string) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}/check-in`, {
      method: 'POST',
      body: { qr_token: qrToken },
    })

  // Teacher/admin scans student card or types student code
  const scanSessionStudent = (
    academyId: number,
    eventId: number,
    sessionId: number,
    identifier: string,
    scanMethod: 'qr' | 'manual' = 'manual'
  ) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}/scan`, {
      method: 'POST',
      body: { identifier, scan_method: scanMethod },
    })

  // Teacher/admin bulk manual record
  const storeSessionRecords = (
    academyId: number,
    eventId: number,
    sessionId: number,
    records: Array<{ user_id: number; status: string; remarks?: string }>
  ) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/sessions/${sessionId}/records`, {
      method: 'POST',
      body: { records },
    })

  // Attendance summary for one enrollment — period: daily | weekly | monthly | semester
  const getEnrollmentAttendanceSummary = (
    academyId: number,
    eventId: number,
    enrollmentId: number,
    period: 'daily' | 'weekly' | 'monthly' | 'semester' = 'monthly'
  ) =>
    api.call(`/api/academies/${academyId}/events/${eventId}/enrollments/${enrollmentId}/attendance-summary`, {
      params: { period },
    })

  return {
    getEvents,
    getUpcomingEvents,
    getMySchedule,
    getEvent,
    createEvent,
    updateEvent,
    publishEvent,
    cancelEvent,
    deleteEvent,

    registerForEvent,
    cancelEventRegistration,
    getEventRegistrations,
    confirmEventRegistration,
    markEventRegistrationAttendance,

    enrollInEvent,
    getSession,
    refreshSessionQr,
    getEventSessions,
    createEventSession,
    updateEventSession,
    deleteEventSession,
    getEventRoster,
    getEventAudienceCount,
    getEventAttendanceReport,
    checkInSession,
    scanSessionStudent,
    storeSessionRecords,
    getEnrollmentAttendanceSummary,
  }
}
