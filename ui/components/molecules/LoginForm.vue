<template>
  <form @submit.prevent="handleSubmit" class="space-y-5 w-full min-w-[400px]">
    <div class="space-y-4">
      <InputText
        v-model="form.login"
        :placeholder="$t('auth.loginFieldPlaceholder')"
        :label="$t('auth.loginField')"
        id="login-field"
        name="login"
        autocomplete="username"
      />
      <InputText
        v-model="form.password"
        type="password"
        :placeholder="$t('auth.password')"
        :label="$t('auth.password')"
        id="login-password"
        name="password"
        autocomplete="current-password"
      />
    </div>

    <div v-if="error" class="text-red-500 text-sm text-center font-medium">{{ error }}</div>

    <div class="space-y-4">
      <AppButton
        type="submit"
        :text="loading ? $t('common.loading') : $t('auth.loginTitle')"
        variant="gradient"
        class="w-full text-sm"
        :disabled="loading"
      />
      <div class="text-center">
        <a href="#" class="text-xs font-bold text-vikinger-purple hover:underline">
          {{ $t('auth.forgotPassword') }}
        </a>
      </div>
    </div>

    <div class="relative my-4">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-200"></div>
      </div>
      <div class="relative flex justify-center text-sm">
        <span class="px-2 bg-white text-gray-500 font-bold">{{ $t('auth.or').toUpperCase() }}</span>
      </div>
    </div>

    <AppButton type="button" :text="$t('auth.signInWithGoogle')" variant="google" @click="handleGoogleLogin" />
  </form>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import InputText from '../atoms/InputText.vue'
import AppButton from '../atoms/Button.vue'

const { t } = useI18n()
const authStore = useAuthStore()
const router = useRouter()

const form = reactive({
  login: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

const handleSubmit = async () => {
  // Validation
  if (!form.login || !form.password) {
    error.value = t('validation.required', { field: t('auth.loginField') + ' / ' + t('auth.password') })
    return
  }

  if (form.password.length < 6) {
    error.value = t('validation.minLength', { field: t('auth.password'), min: 6 })
    return
  }

  loading.value = true
  error.value = ''

  try {
    // Login with multi-field support: email, phone, username, or member ID
    await authStore.login({
      login: form.login,
      password: form.password,
    })

    // Redirect to newsfeed after successful login
    router.push('/newsfeed')
  } catch (e: any) {
    console.error('Login failed', e)
    error.value = e.message || t('validation.invalidCredentials')
  } finally {
    loading.value = false
  }
}

const handleGoogleLogin = () => {
  const config = useRuntimeConfig()
  window.location.href = `${config.public.apiBase}/auth/google/redirect`
}
</script>
