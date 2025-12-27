<template>
  <div class="fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="pointer-events-auto w-96 max-w-full"
      >
        <div
          :class="[
            'flex items-start gap-3 p-4 rounded-xl shadow-lg backdrop-blur-sm border transition-all duration-300 hover:scale-105',
            toastClasses[notification.type]
          ]"
        >
          <!-- Icon -->
          <div :class="['flex-shrink-0 w-6 h-6', iconColors[notification.type]]">
            <Icon :icon="notification.icon || defaultIcons[notification.type]" class="w-6 h-6" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h4 
              v-if="notification.title" 
              class="font-semibold text-gray-900 dark:text-white mb-0.5"
            >
              {{ notification.title }}
            </h4>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              {{ notification.message }}
            </p>
          </div>

          <!-- Close Button -->
          <button
            @click="removeNotification(notification.id)"
            class="flex-shrink-0 p-1 hover:bg-black/5 dark:hover:bg-white/5 rounded-lg transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { useNotificationStore } from '~/stores/notification'
import { storeToRefs } from 'pinia'

const notificationStore = useNotificationStore()
const { notifications } = storeToRefs(notificationStore)

const removeNotification = (id) => {
  notificationStore.remove(id)
}

const toastClasses = {
  success: 'bg-green-50/95 dark:bg-green-900/30 border-green-200 dark:border-green-800',
  error: 'bg-red-50/95 dark:bg-red-900/30 border-red-200 dark:border-red-800',
  warning: 'bg-yellow-50/95 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800',
  info: 'bg-blue-50/95 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800'
}

const iconColors = {
  success: 'text-green-500',
  error: 'text-red-500',
  warning: 'text-yellow-500',
  info: 'text-blue-500'
}

const defaultIcons = {
  success: 'fluent:checkmark-circle-24-filled',
  error: 'fluent:dismiss-circle-24-filled',
  warning: 'fluent:warning-24-filled',
  info: 'fluent:info-24-filled'
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(2rem);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(2rem) scale(0.95);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
