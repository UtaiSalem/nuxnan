<template>
  <form @submit.prevent="handleSubmit" class="space-y-6 w-full min-w-[400px]">
    <!-- Step 1: Referral Code Input -->
    <div v-if="!referralCodeValidated" class="space-y-4">
      <div class="text-center mb-4">
        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $t('auth.referralCodeRequired') }}</h3>
        <p class="text-sm text-gray-600">{{ $t('auth.enterReferralCode') }}</p>
      </div>

      <InputText
        v-model="referralCode"
        :placeholder="$t('auth.referralCodePlaceholder')"
        :label="$t('auth.referralCode')"
        id="referral-code"
        name="referral_code"
        autocomplete="off"
        :disabled="validatingReferralCode"
        maxlength="8"
      />

      <div v-if="referralError" class="text-red-500 text-sm text-center font-medium">
        {{ referralError }}
      </div>

      <div v-if="referralSuccess" class="text-green-500 text-sm text-center font-medium">
        {{ referralSuccess }}
      </div>

      <!-- Display referrer information when found -->
      <div v-if="referrerInfo && !referralCodeValidated" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center space-x-3">
          <img 
            :src="getAvatarUrl(referrerInfo?.avatar)" 
            :alt="referrerInfo?.username"
            class="w-12 h-12 rounded-full object-cover"
          />
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800">Referrer Found!</p>
            <p class="text-sm text-gray-600">{{ referrerInfo.username }}</p>
            <p class="text-xs text-gray-500">Code: {{ referrerInfo.personal_code }}</p>
          </div>
        </div>
      </div>

      <AppButton
        type="button"
        :text="validatingReferralCode ? $t('common.loading') : $t('common.next')"
        variant="secondary"
        :disabled="!referralCode || validatingReferralCode || !!referralError || !referralSuccess"
        @click="continueToRegistration"
      />

      <div class="relative my-4">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-2 bg-white text-gray-500 font-bold">{{ $t('auth.or').toUpperCase() }}</span>
        </div>
      </div>

      <AppButton
        type="button"
        :text="$t('auth.noReferrer')"
        variant="warning"
        @click="useAdminReferralCode"
        :disabled="validatingReferralCode"
      />
    </div>

    <!-- Step 2: Registration Form (shown after referral code is validated) -->
    <div v-else class="space-y-4">
      <!-- Display referrer information in registration form -->
      <div v-if="referrerInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <img 
              :src="getAvatarUrl(referrerInfo?.avatar)" 
              :alt="referrerInfo?.username"
              class="w-12 h-12 rounded-full object-cover"
            />
            <div class="flex-1">
              <p class="text-sm font-bold text-gray-800">Referrer Found!</p>
              <p class="text-sm text-gray-600">{{ referrerInfo.username }}</p>
              <p class="text-xs text-gray-500">Code: {{ referrerInfo.personal_code }}</p>
            </div>
          </div>
          <button
            type="button"
            @click="resetReferralCode"
            class="text-xs text-blue-600 hover:text-blue-800 underline whitespace-nowrap"
          >
            {{ $t('common.edit') }}
          </button>
        </div>
      </div>
      
      <!-- Admin code verified message (when no referrer info) -->
      <div v-else class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
        <div class="flex items-center justify-between">
          <p class="text-sm text-green-800">
            <span class="font-bold">✓ Admin Referral Code Verified</span>
          </p>
          <button
            type="button"
            @click="resetReferralCode"
            class="text-xs text-green-600 hover:text-green-800 underline"
          >
            Change
          </button>
        </div>
      </div>

      <InputText
        v-model="form.email"
        type="email"
        :placeholder="$t('auth.email')"
        :label="$t('auth.email')"
        id="register-email"
        name="email"
        autocomplete="email"
      />
      <InputText
        v-model="form.username"
        :placeholder="$t('auth.username')"
        :label="$t('auth.username')"
        id="register-username"
        name="username"
        autocomplete="username"
      />
      <InputText
        v-model="form.password"
        :type="showPassword ? 'text' : 'password'"
        :placeholder="$t('auth.password')"
        :label="$t('auth.password')"
        :show-toggle="false"
        id="register-password"
        name="password"
        autocomplete="new-password"
      />
      <InputText
        v-model="form.password_confirmation"
        :type="showPassword ? 'text' : 'password'"
        :placeholder="$t('auth.confirmPassword')"
        :label="$t('auth.confirmPassword')"
        :show-toggle="false"
        id="register-password-confirmation"
        name="password_confirmation"
        autocomplete="new-password"
      />

      <div class="flex items-center">
        <input
          type="checkbox"
          id="show-password-register"
          v-model="showPassword"
          class="w-4 h-4 text-vikinger-purple border-gray-300 rounded focus:ring-vikinger-purple cursor-pointer"
        />
        <label
          for="show-password-register"
          class="ml-2 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer select-none"
          >{{ $t('auth.showPassword') }}</label
        >
      </div>

      <Checkbox v-model="form.newsletter" label="Send me news and updates via email" />

      <div v-if="error" class="text-red-500 text-sm text-center font-medium">{{ error }}</div>
      <div v-if="success" class="text-green-500 text-sm text-center font-medium">{{ success }}</div>

      <AppButton
        type="submit"
        :text="loading ? $t('common.loading') : $t('auth.registerTitle')"
        variant="gradient"
        :disabled="loading"
      />

      <div class="relative my-4">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-2 bg-white text-gray-500 font-bold">{{ $t('auth.or').toUpperCase() }}</span>
        </div>
      </div>

      <AppButton
        type="button"
        :text="$t('auth.signInWithGoogle')"
        variant="google"
        @click="handleGoogleRegister"
      />

      <p class="text-xs text-center text-gray-500 font-medium leading-relaxed">
        You'll receive a confirmation email in your inbox with a link to activate your account. If you
        have any problems, <a href="#" class="text-vikinger-blue font-bold">contact us</a>!
      </p>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import InputText from '../atoms/InputText.vue'
import AppButton from '../atoms/Button.vue'
import Checkbox from '../atoms/Checkbox.vue'

const authStore = useAuthStore()
const router = useRouter()
const config = useRuntimeConfig()

// Referral code state
const referralCode = ref('')
const referralCodeValidated = ref(false)
const validatingReferralCode = ref(false)
const referralError = ref('')
const referralSuccess = ref('')
const referrerInfo = ref<{
  username: string
  personal_code: string
  avatar: string
} | null>(null)

// Helper function to get full avatar URL
const getAvatarUrl = (avatar: string | undefined) => {
  if (!avatar) {
    // Return default UI Avatars if no avatar provided
    return 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'
  }
  
  if (avatar.startsWith('http')) {
    return avatar // Already a full URL (e.g., UI Avatars or Google profile)
  }
  
  // Prepend backend URL for Laravel storage paths
  return `http://localhost:8000${avatar}`
}

// Registration form state
const form = reactive({
  name: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
  newsletter: true,
})

const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')

// Watch referral code input - auto-validate when 8 digits entered
watch(referralCode, (newValue) => {
  // Clear previous states when user types
  referralError.value = ''
  referrerInfo.value = null
  
  // Auto-validate when exactly 8 digits are entered
  if (newValue.length === 8 && /^\d{8}$/.test(newValue)) {
    validateReferralCode()
  } else if (newValue.length > 8) {
    referralError.value = 'Referral code must be exactly 8 digits'
  }
})

// Validate referral code
const validateReferralCode = async () => {
  if (!referralCode.value) {
    referralError.value = 'Please enter a referral code'
    return
  }

  if (referralCode.value.length !== 8) {
    referralError.value = 'Referral code must be exactly 8 digits'
    return
  }

  validatingReferralCode.value = true
  referralError.value = ''
  referralSuccess.value = ''
  referrerInfo.value = null

  try {
    const response = await $fetch<{
      success: boolean
      message: string
      is_admin?: boolean
      referrer?: {
        username: string
        personal_code: string
        avatar?: string
      }
    }>('/api/validate-referral-code', {
      method: 'POST',
      body: {
        reference_code: referralCode.value,
      },
    })

    if (response.success) {
      if (response.is_admin) {
        referralSuccess.value = 'Admin referral code verified!'
      } else if (response.referrer) {
        // Store referrer information
        referrerInfo.value = {
          username: response.referrer.username,
          personal_code: response.referrer.personal_code,
          avatar: response.referrer.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(response.referrer.username)}&color=7F9CF5&background=EBF4FF`,
        }
        referralSuccess.value = `Referral code verified! Referred by: ${response.referrer.username}`
      }
      
      // Auto-advance to registration form after successful validation
      setTimeout(() => {
        referralCodeValidated.value = true
      }, 800) // Small delay to show success message briefly
    }
  } catch (e: any) {
    console.error('Referral code validation failed', e)
    referralError.value = e.data?.message || 'ผู้ใช้รหัสนี้ไม่มีอยู่ กรุณาตรวจสอบรหัสอ้างอิงและผู้แนะนำ'
    referrerInfo.value = null
  } finally {
    validatingReferralCode.value = false
  }
}

// Manual continue after validation
const continueToRegistration = () => {
  if (referralSuccess.value) {
    referralCodeValidated.value = true
  }
}

// Use admin referral code (no referrer)
const useAdminReferralCode = () => {
  referralCode.value = '11111111' // Padded to 8 digits to trigger auto-validation
  // Auto-validation will trigger via the watcher
}

// Reset referral code (go back to step 1)
const resetReferralCode = () => {
  referralCodeValidated.value = false
  referralCode.value = ''
  referralError.value = ''
  referralSuccess.value = ''
  referrerInfo.value = null
}

// Handle regular registration
const handleSubmit = async () => {
  // Validation
  if (!form.email || !form.username || !form.password || !form.password_confirmation) {
    error.value = 'Please fill in all required fields'
    return
  }

  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.email)) {
    error.value = 'Please enter a valid email address'
    return
  }

  // Username validation
  if (form.username.length < 3) {
    error.value = 'Username must be at least 3 characters'
    return
  }

  // Password validation
  if (form.password.length < 8) {
    error.value = 'Password must be at least 8 characters'
    return
  }

  // Validate password confirmation
  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    // Register with the new JWT auth system including reference_code
    await authStore.register({
      name: form.username,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      reference_code: referralCode.value,
    })

    success.value = 'Registration successful! Redirecting...'

    // Redirect to newsfeed after successful registration
    setTimeout(() => {
      router.push('/newsfeed')
    }, 1500)
  } catch (e: any) {
    console.error('Registration failed', e)
    error.value = e.message || e.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}

// Handle Google registration
const handleGoogleRegister = () => {
  // Pass referral code to OAuth flow
  window.location.href = `${config.public.apiBase}/auth/google/redirect?reference_code=${referralCode.value}`
}
</script>
