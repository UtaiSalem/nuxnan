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
    api.call(`/api/academies/${academyId}/subjects/${subjectId}`, { method: 'PUT', body: data })

  const deleteSubject = (academyId: number, subjectId: number) => 
    api.call(`/api/academies/${academyId}/subjects/${subjectId}`, { method: 'DELETE' })

  // Class Schedules
  const getSchedules = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules`, { params })

  const createSchedule = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules`, { method: 'POST', body: data })

  const updateSchedule = (academyId: number, scheduleId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/schedules/${scheduleId}`, { method: 'PUT', body: data })

  const deleteSchedule = (academyId: number, scheduleId: number) => 
    api.call(`/api/academies/${academyId}/schedules/${scheduleId}`, { method: 'DELETE' })

  // ============================================================
  // PHASE 2: FINANCE SYSTEM
  // ============================================================

  // Fee Structures
  const getFeeStructures = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fees/structures`, { params })

  const getFeeStructure = (academyId: number, structureId: number) => 
    api.call(`/api/academies/${academyId}/fees/structures/${structureId}`)

  const createFeeStructure = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fees/structures`, { method: 'POST', body: data })

  const updateFeeStructure = (academyId: number, structureId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/fees/structures/${structureId}`, { method: 'PUT', body: data })

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
    api.call(`/api/academies/${academyId}/expense-categories`)

  const createExpenseCategory = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/expense-categories`, { method: 'POST', body: data })

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
    api.call(`/api/academies/${academyId}/staff/${staffId}`, { method: 'PUT', body: data })

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
  // PHASE 4: COMMUNICATION SYSTEM
  // ============================================================

  // Announcements
  const getAnnouncements = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements`, { params })

  const getAnnouncement = (academyId: number, announcementId: number) => 
    api.call(`/api/academies/${academyId}/announcements/${announcementId}`)

  const createAnnouncement = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements`, { method: 'POST', body: data })

  const updateAnnouncement = (academyId: number, announcementId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/announcements/${announcementId}`, { method: 'PUT', body: data })

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
    api.call(`/api/academies/${academyId}/events/${eventId}`, { method: 'PUT', body: data })

  // Meeting Slots
  const getMeetingSlots = (academyId: number, params?: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meeting-slots`, { params })

  const createMeetingSlot = (academyId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meeting-slots`, { method: 'POST', body: data })

  const bookMeeting = (academyId: number, slotId: number, data: Record<string, any>) => 
    api.call(`/api/academies/${academyId}/meeting-slots/${slotId}/book`, { method: 'POST', body: data })

  // ============================================================
  // PHASE 5: REPORTS & ANALYTICS
  // ============================================================

  // Reports
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
    api.call(`/api/academies/${academyId}/dashboard/layout`, { method: 'PUT', body: layout })

  // Analytics
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
    
    // Finance
    getFeeStructures,
    getFeeStructure,
    createFeeStructure,
    updateFeeStructure,
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
    
    // Reports & Analytics
    getReportDefinitions,
    generateReport,
    getSavedReports,
    getDashboardWidgets,
    getUserDashboardLayout,
    updateUserDashboardLayout,
    getAnalyticsOverview,
    getKPIs,
    getKPIValues,
    getAnalyticsSnapshots,
    getTrends,
  }
}
