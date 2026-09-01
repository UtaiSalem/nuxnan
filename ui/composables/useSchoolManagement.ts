/**
 * School Management System Composable
 * ระบบบริหารจัดการโรงเรียน/สถาบัน
 */
export const useSchoolManagement = () => {
  const api = useApi()

  // Helper to safely extract data from response
  const extractData = (response: any) => {
    if (!response) return []
    if (Array.isArray(response)) return response
    if (response.data) return response.data
    return response
  }

  // ============================================================
  // PHASE 1: ACADEMIC SYSTEM
  // ============================================================

  // Classrooms
  const getClassrooms = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/classrooms`, { params })
    return extractData(response)
  }

  const getClassroom = (academyId: number, classroomId: number) => 
    api.get(`/api/academies/${academyId}/classrooms/${classroomId}`)

  const createClassroom = (academyId: number, data: Record<string, any>) => 
    api.post(`/api/academies/${academyId}/classrooms`, data)

  const updateClassroom = (academyId: number, classroomId: number, data: Record<string, any>) => 
    api.patch(`/api/academies/${academyId}/classrooms/${classroomId}`, data)

  const deleteClassroom = (academyId: number, classroomId: number) => 
    api.call(`/api/academies/${academyId}/classrooms/${classroomId}`, { method: 'DELETE' })

  // Subjects
  const getSubjects = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/subjects`, { params })
    return extractData(response)
  }

  const getSubject = (academyId: number, subjectId: number) => 
    api.call(`/api/academies/${academyId}/subjects/${subjectId}`)

  const createSubject = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/subjects`, { method: 'POST', body: data })

  const updateSubject = (academyId: number, subjectId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/subjects/${subjectId}`, { method: 'PATCH', body: data })

  const deleteSubject = (academyId: number, subjectId: number) => 
    api.call(`/api/academies/${academyId}/subjects/${subjectId}`, { method: 'DELETE' })

  // Class Schedules
  const getSchedules = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules`, { params })

  const createSchedule = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules`, { method: 'POST', body: data })

  const updateSchedule = (academyId: number, scheduleId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules/${scheduleId}`, { method: 'PATCH', body: data })

  const deleteSchedule = (academyId: number, scheduleId: number) => 
    api.call(`/api/academies/${academyId}/schedules/${scheduleId}`, { method: 'DELETE' })

  // Academic Years & Semesters
  const getAcademicYears = (academyId: number) =>
    api.call(`/api/academies/${academyId}/academic-years`)

  const getSemesters = (academyId: number, yearId: number) =>
    api.call(`/api/academies/${academyId}/academic-years/${yearId}/semesters`)

  const getCurrentAcademicYear = (academyId: number) =>
    api.call(`/api/academies/${academyId}/academic-years/current`)

  // ============================================================
  // PHASE 2: FINANCE SYSTEM
  // ============================================================

  // Fee Structures
  const getFeeStructures = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fee-structures`, { params })

  const getFeeStructure = (academyId: number, structureId: number) => 
    api.call(`/api/academies/${academyId}/fee-structures/${structureId}`)

  const createFeeStructure = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fee-structures`, { method: 'POST', body: data })

  const updateFeeStructure = (academyId: number, structureId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fee-structures/${structureId}`, { method: 'PATCH', body: data })

  // Tuition Fees
  const getTuitionFees = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/tuition-fees`, { params })

  const bulkGenerateTuitionFees = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/tuition-fees/bulk-generate`, { method: 'POST', body: data })

  const getTuitionFeeSummary = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/tuition-fees/summary`, { params })

  // Expenses
  const getExpenses = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/expenses`, { params })

  const getExpense = (academyId: number, expenseId: number) => 
    api.call(`/api/academies/${academyId}/expenses/${expenseId}`)

  const createExpense = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/expenses`, { method: 'POST', body: data })

  const approveExpense = (academyId: number, expenseId: number) => 
    api.call(`/api/academies/${academyId}/expenses/${expenseId}/approve`, { method: 'POST' })

  const rejectExpense = (academyId: number, expenseId: number, reason: string) => 
    api.call(`/api/academies/${academyId}/expenses/${expenseId}/reject`, { method: 'POST', body: { reason } })

  // Expense Categories
  const getExpenseCategories = (academyId: number) => 
    api.call(`/api/academies/${academyId}/expenses/categories`)

  const createExpenseCategory = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/expenses/categories`, { method: 'POST', body: data })

  // Budgets
  const getBudgets = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/budgets`, { params })

  const getBudget = (academyId: number, budgetId: number) => 
    api.call(`/api/academies/${academyId}/budgets/${budgetId}`)

  const createBudget = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/budgets`, { method: 'POST', body: data })

  // ============================================================
  // PHASE 3: STAFF SYSTEM
  // ============================================================

  // Staff Profiles
  const getStaffList = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/staff`, { params })

  const getStaffProfile = (academyId: number, staffId: number) => 
    api.call(`/api/academies/${academyId}/staff/${staffId}`)

  const createStaff = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/staff`, { method: 'POST', body: data })

  const updateStaff = (academyId: number, staffId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/staff/${staffId}`, { method: 'PATCH', body: data })

  // Staff Attendance
  const getStaffAttendance = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/staff-attendance`, { params })

  const recordStaffAttendance = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/staff-attendance`, { method: 'POST', body: data })

  const checkInStaff = (academyId: number, staffId: number) => 
    api.call(`/api/academies/${academyId}/staff-attendance/check-in`, { method: 'POST', body: { staff_id: staffId } })

  const checkOutStaff = (academyId: number, staffId: number) => 
    api.call(`/api/academies/${academyId}/staff-attendance/check-out`, { method: 'POST', body: { staff_id: staffId } })

  // Leave Requests
  const getLeaveRequests = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/leave-requests`, { params })

  const createLeaveRequest = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/leave-requests`, { method: 'POST', body: data })

  const approveLeaveRequest = (academyId: number, requestId: number) => 
    api.call(`/api/academies/${academyId}/leave-requests/${requestId}/approve`, { method: 'POST' })

  const rejectLeaveRequest = (academyId: number, requestId: number, reason: string) => 
    api.call(`/api/academies/${academyId}/leave-requests/${requestId}/reject`, { method: 'POST', body: { reason } })

  // Leave Types
  const getLeaveTypes = (academyId: number) => 
    api.call(`/api/academies/${academyId}/leave-types`)

  // Payroll
  const getPayrolls = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/payroll`, { params })

  const getPayroll = (academyId: number, payrollId: number) => 
    api.call(`/api/academies/${academyId}/payroll/${payrollId}`)

  const createPayroll = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/payroll`, { method: 'POST', body: data })

  const approvePayroll = (academyId: number, payrollId: number) => 
    api.call(`/api/academies/${academyId}/payroll/${payrollId}/approve`, { method: 'POST' })

  // ============================================================
  // PHASE 4: GAMIFICATION & ECONOMY
  // ============================================================

  // Point Rules
  const getPointRules = (academyId: number) => 
    api.call(`/api/academies/${academyId}/gamification/points/rules`)

  const createPointRule = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/gamification/points/rules`, { method: 'POST', body: data })

  const updatePointRule = (academyId: number, ruleId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/gamification/points/rules/${ruleId}`, { method: 'PATCH', body: data })

  const deletePointRule = (academyId: number, ruleId: number) => 
    api.call(`/api/academies/${academyId}/gamification/points/rules/${ruleId}`, { method: 'DELETE' })

  // Leaderboards
  const getHouseLeaderboard = (academyId: number) => 
    api.call(`/api/academies/${academyId}/gamification/leaderboard/houses`)

  const getClassroomLeaderboard = (academyId: number) => 
    api.call(`/api/academies/${academyId}/gamification/leaderboard/classrooms`)

  // ============================================================
  // PHASE 5: COMMUNICATION SYSTEM
  // ============================================================

  // Announcements
  const getAnnouncements = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements`, { params })

  const getAnnouncement = (academyId: number, announcementId: number) => 
    api.call(`/api/academies/${academyId}/announcements/${announcementId}`)

  const createAnnouncement = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements`, { method: 'POST', body: data })

  const updateAnnouncement = (academyId: number, announcementId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements/${announcementId}`, { method: 'PATCH', body: data })

  const publishAnnouncement = (academyId: number, announcementId: number) => 
    api.call(`/api/academies/${academyId}/announcements/${announcementId}/publish`, { method: 'POST' })

  // Events
  const getEvents = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/events`, { params })

  const getEvent = (academyId: number, eventId: number) => 
    api.call(`/api/academies/${academyId}/events/${eventId}`)

  const createEvent = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/events`, { method: 'POST', body: data })

  const updateEvent = (academyId: number, eventId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/events/${eventId}`, { method: 'PATCH', body: data })

  // Meeting Slots
  const getMeetingSlots = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/slots`, { params })

  const createMeetingSlot = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/slots`, { method: 'POST', body: data })

  const bookMeeting = (academyId: number, slotId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/slots/${slotId}/book`, { method: 'POST', body: data })

  const getMyMeetingBookings = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/my-bookings`, { params })

  const teacherBookings = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/teacher-bookings`, { params })

  const cancelBooking = (academyId: number, bookingId: number) => 
    api.call(`/api/academies/${academyId}/meetings/bookings/${bookingId}/cancel`, { method: 'POST' })

  const getAvailableSlots = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meetings/slots/available`, { params })

  // ============================================================
  // PHASE 6: REPORTS & ANALYTICS
  // ============================================================

  // Reports
  const getReports = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/reports/definitions`, { params })

  const getReportDefinitions = (academyId: number) => 
    api.call(`/api/academies/${academyId}/reports/definitions`)

  const generateReport = (academyId: number, definitionId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/reports/definitions/${definitionId}/generate`, { method: 'POST', body: params })

  const getSavedReports = (academyId: number) => 
    api.call(`/api/academies/${academyId}/reports/saved`)

  // Dashboard Widgets
  const getDashboardWidgets = (academyId: number) => 
    api.call(`/api/academies/${academyId}/dashboard/widgets`)

  const getUserDashboardLayout = (academyId: number) => 
    api.call(`/api/academies/${academyId}/dashboard/layout`)

  const updateUserDashboardLayout = (academyId: number, layout: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/dashboard/layout`, { method: 'POST', body: layout })

  // Analytics
  const getDashboardStats = (academyId: number) => 
    api.call(`/api/academies/${academyId}/analytics/dashboard-stats`)

  const getStudentDashboardStats = (academyId: number) => 
    api.call(`/api/academies/${academyId}/analytics/student-stats`)

  const getTeacherPendingAssignments = (academyId: number) => 
    api.call(`/api/academies/${academyId}/analytics/teacher-pending-assignments`)

  // Audit Logs
  const getEntityAuditLogs = (academyId: number, entityType: string, entityId: number) => 
    api.call(`/api/academies/${academyId}/audit-logs/entity`, { 
      params: { entity_type: entityType, entity_id: entityId } 
    })

  const getAtRiskStudents = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/analytics/at-risk`, { params })

  const getAnalyticsOverview = (academyId: number) => 
    api.call(`/api/academies/${academyId}/analytics/overview`)

  const getKPIs = (academyId: number) => 
    api.call(`/api/academies/${academyId}/analytics/kpis`)

  const getKPIValues = (academyId: number, kpiId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/analytics/kpis/${kpiId}/values`, { params })

  const getAnalyticsSnapshots = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/analytics/snapshots`, { params })

  const getTrends = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/analytics/trends`, { params })

  // ============================================================
  // PHASE 7: EXTENDED MODULES
  // ============================================================

  // Library
  const getLibraryBooks = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/library/books`, { params })

  const createLibraryBook = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/library/books`, { method: 'POST', body: data })

  const borrowBook = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/library/borrow`, { method: 'POST', body: data })

  const returnBook = (academyId: number, borrowingId: number) => 
    api.call(`/api/academies/${academyId}/library/borrowings/${borrowingId}/return`, { method: 'POST' })

  // Assets
  const getAssets = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/assets`, { params })

  const createAsset = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/assets`, { method: 'POST', body: data })

  const requestAssetMaintenance = (academyId: number, assetId: number, data: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/assets/${assetId}/maintenance`, { method: 'POST', body: data })

  // ============================================================
  // SCHOOL ATTENDANCE (Session-Based + QR Check-in)
  // ============================================================

  // List sessions (filter by date, date_from/to, status)
  const getSchoolAttendances = (academyId: number, params?: Record<string, any>) =>
    api.call(`/api/academies/${academyId}/school-attendances`, { params })

  // Create a new attendance session (returns qr_token + qr_url)
  const createSchoolAttendance = (academyId: number, data: {
    date: string
    title?: string
    start_time?: string
    late_minutes?: number
    notes?: string
  }) =>
    api.call(`/api/academies/${academyId}/school-attendances`, { method: 'POST', body: data })

  // Show session detail with records + summary
  const getSchoolAttendance = (academyId: number, attendanceId: number) =>
    api.call(`/api/academies/${academyId}/school-attendances/${attendanceId}`)

  // Student self-check-in via QR token
  const schoolAttendanceCheckIn = (academyId: number, attendanceId: number, qrToken: string) =>
    api.call(`/api/academies/${academyId}/school-attendances/${attendanceId}/check-in`, {
      method: 'POST',
      body: { qr_token: qrToken },
    })

  // Teacher/admin bulk record (manual)
  const recordSchoolAttendances = (
    academyId: number,
    attendanceId: number,
    records: Array<{ student_id: number; status: string; remarks?: string }>
  ) =>
    api.call(`/api/academies/${academyId}/school-attendances/${attendanceId}/records`, {
      method: 'POST',
      body: { records },
    })

  // Close session
  const closeSchoolAttendance = (academyId: number, attendanceId: number) =>
    api.call(`/api/academies/${academyId}/school-attendances/${attendanceId}/close`, {
      method: 'POST',
    })

  // Teacher scans student card QR or types student code
  const scanStudentAttendance = (
    academyId: number,
    attendanceId: number,
    identifier: string
  ) =>
    api.call(`/api/academies/${academyId}/school-attendances/${attendanceId}/scan-student`, {
      method: 'POST',
      body: { identifier },
    })

  // Student attendance history
  const getStudentAttendanceHistory = (
    academyId: number,
    studentId: number,
    params?: { date_from?: string; date_to?: string; per_page?: number }
  ) =>
    api.call(`/api/academies/${academyId}/school-attendances/student/${studentId}`, { params })

  return {
    // Academic
    getClassrooms,
    getClassroom,
    createClassroom,
    updateClassroom,
    deleteClassroom,
    getSubjects,
    getSubject,
    createSubject,
    updateSubject,
    deleteSubject,
    getSchedules,
    createSchedule,
    updateSchedule,
    deleteSchedule,
    getAcademicYears,
    getSemesters,
    getCurrentAcademicYear,
    
    // Finance
    getFeeStructures,
    getFeeStructure,
    createFeeStructure,
    updateFeeStructure,
    getTuitionFees,
    bulkGenerateTuitionFees,
    getTuitionFeeSummary,
    getExpenses,
    getExpense,
    createExpense,
    approveExpense,
    rejectExpense,
    getExpenseCategories,
    createExpenseCategory,
    getBudgets,
    getBudget,
    createBudget,
    
    // Staff
    getStaffList,
    getStaffProfile,
    createStaff,
    updateStaff,
    getStaffAttendance,
    recordStaffAttendance,
    checkInStaff,
    checkOutStaff,
    getLeaveRequests,
    createLeaveRequest,
    approveLeaveRequest,
    rejectLeaveRequest,
    getLeaveTypes,
    getPayrolls,
    getPayroll,
    createPayroll,
    approvePayroll,
    
    // Points & Gamification
    getPointRules,
    createPointRule,
    updatePointRule,
    deletePointRule,
    getHouseLeaderboard,
    getClassroomLeaderboard,
    
    // Communication
    getAnnouncements,
    getAnnouncement,
    createAnnouncement,
    updateAnnouncement,
    publishAnnouncement,
    getEvents,
    getEvent,
    createEvent,
    updateEvent,
    getMeetingSlots,
    createMeetingSlot,
    bookMeeting,
    getMyMeetingBookings,
    teacherBookings,
    cancelBooking,
    getAvailableSlots,
    
    // Reports & Analytics
    getReports,
    getReportDefinitions,
    generateReport,
    getSavedReports,
    getDashboardWidgets,
    getUserDashboardLayout,
    updateUserDashboardLayout,
    getDashboardStats,
    getStudentDashboardStats,
    getTeacherPendingAssignments,
    getEntityAuditLogs,
    getAtRiskStudents,
    getAnalyticsOverview,
    getKPIs,
    getKPIValues,
    getAnalyticsSnapshots,
    getTrends,

    // Library
    getLibraryBooks,
    createLibraryBook,
    borrowBook,
    returnBook,

    // Assets
    getAssets,
    createAsset,
    requestAssetMaintenance,

    // School Attendance
    getSchoolAttendances,
    createSchoolAttendance,
    getSchoolAttendance,
    schoolAttendanceCheckIn,
    scanStudentAttendance,
    recordSchoolAttendances,
    closeSchoolAttendance,
    getStudentAttendanceHistory,
  }
}
