/**
 * Friend Service - API calls for friends
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

export const friendService = {
  // ============= Friends =============

  /**
   * Get the current user's friend list, or another user's friend list
   * @param {string|number} [userId] - Optional: fetch friends of a specific user
   * @param {number} [page=1] - Page number for pagination
   */
  async getFriends(userId = null, page = 1) {
    const api = useApi()
    const endpoint = userId
      ? `/api/users/${userId}/friends?page=${page}`
      : `/api/friends?page=${page}`
    return api.get(endpoint)
  },

  /**
   * Search friends by name / username
   */
  async searchFriends(query) {
    const api = useApi()
    return api.get(`/api/friends/search?q=${encodeURIComponent(query)}`)
  },

  /**
   * Get mutual friends with a user
   */
  async getMutualFriends(userId) {
    const api = useApi()
    return api.get(`/api/users/${userId}/mutual-friends`)
  },

  // ============= Friend Requests =============

  /**
   * Get pending friend requests (received)
   */
  async getPendingRequests() {
    const api = useApi()
    return api.get('/api/friends/pending')
  },

  /**
   * Get friend suggestions
   */
  async getSuggestions() {
    const api = useApi()
    return api.get('/api/friends/suggestions')
  },

  /**
   * Send a friend request to a user
   */
  async sendRequest(userId) {
    const api = useApi()
    return api.post(`/api/friends/${userId}`, {})
  },

  /**
   * Accept a received friend request
   */
  async acceptRequest(userId) {
    const api = useApi()
    return api.patch(`/api/friends/${userId}/accept`, {})
  },

  /**
   * Deny / reject a received friend request
   */
  async denyRequest(userId) {
    const api = useApi()
    return api.post(`/api/friends/${userId}/deny`, {})
  },

  /**
   * Cancel a sent friend request
   */
  async cancelRequest(userId) {
    const api = useApi()
    return api.delete(`/api/friends/${userId}`)
  },

  /**
   * Unfriend a user
   */
  async unfriend(userId) {
    const api = useApi()
    return api.post(`/api/friends/${userId}/unfriend`, {})
  },
}
