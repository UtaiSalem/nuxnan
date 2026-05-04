/**
 * Course Service - API calls for courses
 *
 * Uses the centralized useApi() composable for:
 * - Automatic JWT authentication (Authorization: Bearer <token>)
 * - Retry + exponential backoff on transient failures
 * - Standardized error objects
 * - Timeout handling
 *
 * NOTE: Each method calls useApi() internally. Must be invoked from
 * a Nuxt setup context (e.g. inside <script setup>, useAsyncData,
 * composables, or Pinia store actions).
 */

export const courseService = {
  // ============= Courses =============

  /**
   * Get a single course
   */
  async getCourse(courseId) {
    const api = useApi()
    return api.get(`/api/courses/${courseId}`)
  },

  /**
   * Get course list
   */
  async getCourses(params = {}) {
    const api = useApi()
    const query = new URLSearchParams(params).toString()
    return api.get(`/api/courses${query ? `?${query}` : ''}`)
  },

  /**
   * Get favorite courses
   */
  async getFavoriteCourses(params = {}) {
    const api = useApi()
    const query = new URLSearchParams(params).toString()
    return api.get(`/api/courses/favorites${query ? `?${query}` : ''}`)
  },

  /**
   * Create a new course
   */
  async createCourse(data) {
    const api = useApi()
    return api.post('/api/courses', data)
  },

  /**
   * Update course
   */
  async updateCourse(courseId, data) {
    const api = useApi()
    return api.patch(`/api/courses/${courseId}`, data)
  },

  /**
   * Delete course
   */
  async deleteCourse(courseId) {
    const api = useApi()
    return api.delete(`/api/courses/${courseId}`)
  },

  /**
   * Toggle favorite
   */
  async toggleFavorite(courseId) {
    const api = useApi()
    return api.post(`/api/courses/${courseId}/favorite`, {})
  },

  /**
   * Update course cover image (FormData upload)
   */
  async updateCourseCover(courseId, file) {
    const api = useApi()
    const formData = new FormData()
    formData.append('cover', file)
    formData.append('_method', 'PATCH')
    return api.post(`/api/courses/${courseId}`, formData)
  },

  /**
   * Update course logo (FormData upload)
   */
  async updateCourseLogo(courseId, file) {
    const api = useApi()
    const formData = new FormData()
    formData.append('logo', file)
    formData.append('_method', 'PATCH')
    return api.post(`/api/courses/${courseId}`, formData)
  },

  /**
   * Update course name
   */
  async updateCourseHeader(courseId, header) {
    const api = useApi()
    return api.patch(`/api/courses/${courseId}`, { name: header })
  },

  /**
   * Update course code
   */
  async updateCourseSubheader(courseId, subheader) {
    const api = useApi()
    return api.patch(`/api/courses/${courseId}`, { code: subheader })
  },

  // ============= Member Management =============

  /**
   * Request course membership
   */
  async requestMembership(courseId, groupId = null) {
    const api = useApi()
    return api.post(`/api/courses/${courseId}/members`, {
      group_id: groupId,
    })
  },

  /**
   * Cancel course membership
   */
  async cancelMembership(courseId, memberId) {
    const api = useApi()
    return api.delete(`/api/courses/${courseId}/members/${memberId}`)
  },

  /**
   * Get course members
   */
  async getCourseMembers(courseId, params = {}) {
    const api = useApi()
    const query = new URLSearchParams(params).toString()
    return api.get(`/api/courses/${courseId}/members${query ? `?${query}` : ''}`)
  },

  /**
   * Update member status (admin)
   */
  async updateMemberStatus(courseId, memberId, status) {
    const api = useApi()
    return api.patch(`/api/courses/${courseId}/members/${memberId}`, { status })
  },

  // ============= Groups =============

  /**
   * Get course groups
   */
  async getCourseGroups(courseId) {
    const api = useApi()
    return api.get(`/api/courses/${courseId}/groups`)
  },

  /**
   * Create a group
   */
  async createCourseGroup(courseId, data) {
    const api = useApi()
    return api.post(`/api/courses/${courseId}/groups`, data)
  },

  /**
   * Update a group
   */
  async updateCourseGroup(courseId, groupId, data) {
    const api = useApi()
    return api.patch(`/api/courses/${courseId}/groups/${groupId}`, data)
  },

  /**
   * Delete a group
   */
  async deleteCourseGroup(courseId, groupId) {
    const api = useApi()
    return api.delete(`/api/courses/${courseId}/groups/${groupId}`)
  },
}
