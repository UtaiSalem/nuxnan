<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: []
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    // Get parameters from URL
    const token = route.query.token as string
    const errorParam = route.query.error as string
    const errorDescription = route.query.error_description as string
    const provider = route.query.provider as string
    const isNewUser = route.query.new_user === '1'
    
    // Handle OAuth errors (RFC 6749 compliant)
    if (errorParam) {
      const errorMessages: Record<string, string> = {
        'invalid_state': 'Security validation failed. Please try again.',
        'invalid_provider': 'Invalid authentication provider.',
        'invalid_request': errorDescription || 'Invalid request.',
        'server_error': errorDescription || 'Server error occurred.',
        'access_denied': 'You denied access to your account.',
        'missing_referral_code': errorDescription || 'Referral code is required.',
        'invalid_referral_code': errorDescription || 'Invalid referral code.',
      }
      
      error.value = errorMessages[errorParam] || errorDescription || 'Authentication failed.'
      loading.value = false
      
      // Redirect to auth page after 3 seconds
      setTimeout(() => {
        router.push('/auth?tab=login')
      }, 3000)
      return
    }
    
    // Validate token
    if (!token) {
      error.value = 'No authentication token received.'
      loading.value = false
      
      setTimeout(() => {
        router.push('/auth?tab=login')
      }, 3000)
      return
    }
    
    // Store token and fetch user data
    const success = await authStore.handleOAuthCallback(token)
    
    if (!success) {
      throw new Error('Failed to load user data')
    }
    
    // Verify user data is loaded
    if (!authStore.user) {
      throw new Error('User data not available')
    }
    
    // Small delay to ensure state is fully updated
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Redirect to newsfeed
    await router.push('/newsfeed')
    
  } catch (err: any) {
    console.error('OAuth callback error:', err)
    error.value = err.message || 'Failed to complete authentication.'
    loading.value = false
    
    setTimeout(() => {
      router.push('/auth?tab=login')
    }, 3000)
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-900 via-blue-900 to-black">
    <div class="text-center max-w-md px-4">
      <!-- Loading State -->
      <div v-if="loading && !error" class="space-y-4">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-purple-500 mx-auto"></div>
        <p class="text-white text-lg font-medium">Completing authentication...</p>
        <p class="text-gray-400 text-sm">Please wait while we sign you in</p>
      </div>
      
      <!-- Error State -->
      <div v-if="error" class="bg-red-500/20 border border-red-500 rounded-lg p-6">
        <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h2 class="text-xl font-bold text-white mb-2">Authentication Failed</h2>
        <p class="text-red-200 mb-4">{{ error }}</p>
        <p class="text-sm text-gray-400">Redirecting to login page...</p>
      </div>
    </div>
  </div>
</template>
