import { defineStore, skipHydrate } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  
  const user = ref(null)
  const token = useCookie('token', {
    maxAge: 60 * 60 * 24 * 7,
    sameSite: 'lax',
    path: '/',
  })
  const isAuthenticated = computed(() => !!token.value)
  const isRefreshing = ref(false)
  const isLoggingOut = ref(false)
  const isLoading = ref(false)
  const isLoginTransitioning = ref(false)

  async function login(credentials: any) {
    isLoading.value = true
    try {
      const res = await fetch(`${apiBase}/api/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          login: credentials.email || credentials.login,
          password: credentials.password,
        }),
      })

      const response = await res.json()

      if (!res.ok) {
        throw {
          data: response,
          status: res.status,
          statusMessage: res.statusText,
          message: response?.message || `Login failed (${res.status})`,
        }
      }

      // Backend returns: { success: true, access_token, token_type, expires_in, user }
      // OR nested format: { success: true, data: { accessToken, user } }
      if (response.success) {
        // Handle direct format (access_token at root level)
        const accessToken = response.access_token || response.data?.accessToken
        const userData = response.user || response.data?.user
        
        if (accessToken) {
          token.value = accessToken
          user.value = userData
          // Store token TTL for dynamic refresh scheduling
          if (response.expires_in) {
            tokenExpiresIn.value = response.expires_in
          }
        } else {
          throw new Error('Invalid response from server')
        }
      } else {
        throw new Error(response.message || 'Invalid response from server')
      }

      return response
    } catch (e: any) {
      console.error('Login error:', e)
      const errorMessage = e.data?.message || e.statusMessage || e.message || 'Login failed'
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  // Admin-specific login using the admin API endpoint
  async function adminLogin(credentials: { email: string; password: string }) {
    isLoading.value = true
    try {
      const response = await $fetch(`${apiBase}/api/admin/auth/login`, {
        method: 'POST',
        credentials: 'include',
        body: {
          login: credentials.email, // Backend expects 'login' field, not 'email'
          password: credentials.password,
        },
      }) as any

      if (response.success) {
        const accessToken = response.data?.token
        const userData = response.data?.user

        if (accessToken && userData) {
          token.value = accessToken
          user.value = userData
          
          // Also store admin token in separate cookie
          const adminToken = useCookie('admin_token', {
            maxAge: 60 * 60 * 24 * 7,
            sameSite: 'lax',
            path: '/',
          })
          adminToken.value = accessToken
        } else {
          throw new Error('Invalid response from server')
        }
      } else {
        throw new Error(response.message || 'Admin login failed')
      }

      return response
    } catch (e: any) {
      console.error('Admin login error:', e)
      const errorMessage = e.data?.message || e.statusMessage || e.message || 'Admin login failed'
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  async function register(userData: any) {
    isLoading.value = true
    try {
      const res = await fetch(`${apiBase}/api/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(userData),
      })

      const response = await res.json()

      if (!res.ok) {
        throw {
          data: response,
          status: res.status,
          statusMessage: res.statusText,
          message: response?.message || `Registration failed (${res.status})`,
        }
      }

      // Backend may return direct JWT fields or a nested data payload.
      if (response.success) {
        const accessToken = response.access_token || response.data?.accessToken
        const responseUser = response.user || response.data?.user
        
        if (accessToken) {
          token.value = accessToken
          user.value = responseUser
          if (response.expires_in) {
            tokenExpiresIn.value = response.expires_in
          }
        } else {
          throw new Error('Invalid response from server')
        }
      } else {
        throw new Error('Invalid response from server')
      }

      return response
    } catch (e: any) {
      console.error('Registration error:', e)
      const errorMessage = e.data?.message || e.statusMessage || e.message || 'Registration failed'
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) {
      throw new Error('No authentication token')
    }

    try {
      const response = await $fetch(`${apiBase}/api/me`, {
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      }) as any

      // Handle response structure from AuthController
      // Response format: { success: true, data: UserResource }
      if (response.success && response.data) {
        user.value = response.data
      } else if (response.user) {
        // Fallback for other response formats
        user.value = response.user
      } else if (response.data) {
        user.value = response.data
      } else {
        // If we have a response but no user data
        user.value = response
      }
      
      if (!user.value) {
        throw new Error('No user data received from server')
      }
      
    } catch (e: any) {
      // Only log if not a 401 (expected when token is invalid/expired)
      if (e.statusCode !== 401) {
        console.error('Fetch user error:', e)
      }
      // If token is invalid, clear it
      if (e.statusCode === 401) {
        token.value = null
        user.value = null
      }
      throw e
    }
  }

  // Track token expiry so plugins can schedule refresh dynamically
  const tokenExpiresIn = ref<number>(60 * 60) // default 1 hour in seconds

  // Promise that concurrent 401 handlers can await while a refresh is in-flight
  let refreshPromise: Promise<boolean> | null = null

  async function refreshToken() {
    if (!token.value) return false

    // If a refresh is already in-flight, reuse that promise (dedup concurrent calls)
    if (isRefreshing.value && refreshPromise) return refreshPromise

    isRefreshing.value = true
    refreshPromise = _doRefresh()
    const result = await refreshPromise
    refreshPromise = null
    return result
  }

  async function _doRefresh(): Promise<boolean> {
    try {
      const response = await $fetch(`${apiBase}/api/refresh`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      }) as any

      // Backend respondWithToken returns:
      // { success: true, access_token: "...", token_type: "bearer", expires_in: 3600, user: {...} }
      if (response.success) {
        const accessToken = response.access_token || response.data?.accessToken
        if (accessToken) {
          token.value = accessToken

          // Store expires_in for dynamic refresh scheduling
          if (response.expires_in) {
            tokenExpiresIn.value = response.expires_in
          }

          // Update user data if included
          const userData = response.user || response.data?.user
          if (userData) {
            user.value = userData
          }

          return true
        }
      }

      return false
    } catch (e: any) {
      // Refresh failure should not hard-logout the user mid-game.
      // We'll keep the existing token and let feature-level calls (like earn points)
      // fail gracefully if the token is actually expired.
      console.error('Token refresh error:', e)
      return false
    } finally {
      isRefreshing.value = false
    }
  }

  async function logout() {
    if (isLoggingOut.value) return
    isLoggingOut.value = true

    try {
      if (token.value) {
        await $fetch(`${apiBase}/api/logout`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            Authorization: `Bearer ${token.value}`,
          },
        })
      }
    } catch (e) {
      console.error('Logout error:', e)
      // Continue with logout even if API call fails
    } finally {
      token.value = null
      user.value = null
      isLoggingOut.value = false
      await navigateTo('/auth')
    }
  }

  /**
   * Handle OAuth callback with token
   * Standards-compliant token handling
   */
  async function handleOAuthCallback(oauthToken: string) {
    isLoading.value = true
    try {
      // Validate token format (JWT has 3 parts separated by dots)
      if (!oauthToken || oauthToken.split('.').length !== 3) {
        throw new Error('Invalid token format')
      }
      
      // Store token
      token.value = oauthToken
      
      // Fetch user data
      await fetchUser()
      
      return true
    } catch (error: any) {
      // Clear any partial state
      token.value = null
      user.value = null
      
      throw new Error(error.message || 'Failed to process authentication')
    } finally {
      isLoading.value = false
    }
  }

  // Points Management
  const points = computed(() => Number(user.value?.points) || 0)

  // Avatar URL - always has a value with proper fallback
  const avatarUrl = computed(() => {
    if (!user.value) return '/images/default-avatar.png'
    // Use avatar field first (always set by backend), then profile_photo_url
    return user.value.avatar || user.value.profile_photo_url || '/images/default-avatar.png'
  })

  function deductPoints(amount: number): boolean {
    if (!user.value) {
      console.error('❌ deductPoints: No user')
      return false
    }
    const currentPoints = Number(user.value.points) || 0
    if (currentPoints < amount) {
      console.error('❌ deductPoints: Not enough points', { currentPoints, amount })
      return false
    }

    user.value.points = currentPoints - amount

    return true
  }

  function addPoints(amount: number): void {
    if (!user.value) return
    const currentPoints = Number(user.value.points) || 0
    user.value.points = currentPoints + amount
  }

  function setPoints(amount: number): void {
    if (!user.value) return
    user.value.points = amount
  }

  function rollback(amount: number): void {
    addPoints(amount)
  }

  const hasEnoughPoints = computed(() => (amount: number) => {
    return points.value >= amount
  })

  const canLike = computed(() => points.value >= 24)
  const canDislike = computed(() => points.value >= 12)

  // Specialized deduction methods
  function deductForLike(): boolean {
    return deductPoints(24)
  }

  function deductForDislike(): boolean {
    return deductPoints(12)
  }

  function deductForUnlike(): boolean {
    return deductPoints(12)
  }

  function deductForUndislike(): boolean {
    return deductPoints(12)
  }

  // Wallet Management
  const wallet = computed(() => Number(user.value?.wallet) || 0)

  function setWallet(amount: number): void {
    if (!user.value) return
    user.value.wallet = amount
  }

  function addWallet(amount: number): void {
    if (!user.value) return
    const currentWallet = Number(user.value.wallet) || 0
    user.value.wallet = currentWallet + amount
  }

  function deductWallet(amount: number): boolean {
    if (!user.value) {
      console.error('❌ deductWallet: No user')
      return false
    }
    const currentWallet = Number(user.value.wallet) || 0
    if (currentWallet < amount) {
      console.error('❌ deductWallet: Not enough wallet balance', { currentWallet, amount })
      return false
    }

    user.value.wallet = currentWallet - amount

    return true
  }

  const hasEnoughWallet = computed(() => (amount: number) => {
    return wallet.value >= amount
  })

  return {
    user,
    token: skipHydrate(token),
    isAuthenticated,
    isRefreshing,
    isLoggingOut,
    isLoading,
    isLoginTransitioning,
    tokenExpiresIn,
    login,
    adminLogin,
    register,
    fetchUser,
    refreshToken,
    logout,
    handleOAuthCallback,
    // Points management
    points,
    deductPoints,
    addPoints,
    setPoints,
    rollback,
    hasEnoughPoints,
    canLike,
    canDislike,
    deductForLike,
    deductForDislike,
    deductForUnlike,
    deductForUndislike,
    // Wallet management
    wallet,
    setWallet,
    addWallet,
    deductWallet,
    hasEnoughWallet,
    // Avatar
    avatarUrl,
  }
})
