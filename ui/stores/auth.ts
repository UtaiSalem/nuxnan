import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  
  const user = ref(null)
  const token = useCookie('token', {
    maxAge: 60 * 60 * 24 * 7, // 7 days
    secure: process.env.NODE_ENV === 'production', // Only secure in production (HTTPS)
    sameSite: 'lax',
  })
  const isAuthenticated = computed(() => !!token.value)
  const isRefreshing = ref(false)
  const isLoading = ref(false)

  async function login(credentials: any) {
    isLoading.value = true
    try {
      const response = await $fetch<any>(`${apiBase}/api/login`, {
        method: 'POST',
        credentials: 'include',
        body: {
          login: credentials.email || credentials.login,
          password: credentials.password,
        },
      })

      // Backend returns: { success: true, access_token, token_type, expires_in, user }
      // OR nested format: { success: true, data: { accessToken, user } }
      if (response.success) {
        // Handle direct format (access_token at root level)
        const accessToken = response.access_token || response.data?.accessToken
        const userData = response.user || response.data?.user
        
        if (accessToken) {
          token.value = accessToken
          user.value = userData
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

  async function register(userData: any) {
    isLoading.value = true
    try {
      const response = await $fetch<any>(`${apiBase}/api/register`, {
        method: 'POST',
        credentials: 'include',
        body: userData,
      })

      // Backend returns: { success: true, data: { user, accessToken, tokenType, expiresIn } }
      if (response.success && response.data) {
        const { accessToken, user: responseUser } = response.data
        
        if (accessToken) {
          token.value = accessToken
          user.value = responseUser
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
      const response = await $fetch<any>(`${apiBase}/api/me`, {
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      })

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

  async function refreshToken() {
    if (isRefreshing.value || !token.value) return false

    isRefreshing.value = true

    try {
      const response = await $fetch<any>(`${apiBase}/api/refresh`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      })

      // Backend returns: { success: true, data: { accessToken, ... } }
      if (response.success && response.data?.accessToken) {
        token.value = response.data.accessToken

        // Optionally update user data if included
        if (response.data.user) {
          user.value = response.data.user
        }
        
        return true
      }
      
      return false
    } catch (e: any) {
      console.error('Token refresh error:', e)
      // If refresh fails, clear auth
      token.value = null
      user.value = null
      return false
    } finally {
      isRefreshing.value = false
    }
  }

  async function logout() {
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
      navigateTo('/auth')
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
    token,
    isAuthenticated,
    isRefreshing,
    isLoading,
    login,
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
  }
})
