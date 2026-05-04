import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { defineNuxtPlugin, useRuntimeConfig } from '#app'
import { useAuthStore } from '~/stores/auth'

declare global {
  interface Window {
    Pusher: any
    Echo: Echo
  }
}

export default defineNuxtPlugin((nuxtApp) => {
  const config = useRuntimeConfig()

  if (!import.meta.client) return

  const reverbEnabled = Boolean(config.public.reverbEnabled)
  const reverbAppKey = String(config.public.reverbAppKey || '')
  const reverbHost = String(config.public.reverbHost || '')
  const reverbPort = Number(config.public.reverbPort || 8080)
  const reverbScheme = String(config.public.reverbScheme || 'http')

  if (!reverbEnabled || !reverbAppKey || !reverbHost) {
    return
  }

  window.Pusher = Pusher

  const authStore = useAuthStore()

  const echo = new Echo({
    broadcaster: 'reverb',
    key: reverbAppKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${config.public.apiBase}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: authStore.token ? `Bearer ${authStore.token}` : '',
        Accept: 'application/json'
      }
    }
  })

  window.Echo = echo

  return {
    provide: {
      echo
    }
  }
})
