<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

// Settings sections
const sections = [
  { id: 'general', name: 'ทั่วไป', icon: 'fluent:settings-24-regular' },
  { id: 'appearance', name: 'การแสดงผล', icon: 'fluent:paint-brush-24-regular' },
  { id: 'email', name: 'อีเมล', icon: 'fluent:mail-24-regular' },
  { id: 'payment', name: 'การชำระเงิน', icon: 'fluent:payment-24-regular' },
  { id: 'security', name: 'ความปลอดภัย', icon: 'fluent:shield-24-regular' },
  { id: 'api', name: 'API', icon: 'fluent:code-24-regular' }
]

const activeSection = ref('general')
const isSaving = ref(false)

// General settings
const generalSettings = ref({
  siteName: 'Nuxnan',
  siteDescription: 'เรียนให้สนุก เล่นให้ได้ความรู้ สู่การสร้างรายได้',
  contactEmail: 'support@nuxnan.com',
  supportPhone: '02-xxx-xxxx',
  timezone: 'Asia/Bangkok',
  language: 'th'
})

// Appearance settings
const appearanceSettings = ref({
  primaryColor: '#6366f1',
  logoUrl: '/images/plearnd-logo.png',
  faviconUrl: '/favicon.ico',
  enableDarkMode: true,
  showFooter: true
})

// Email settings
const emailSettings = ref({
  smtpHost: 'smtp.example.com',
  smtpPort: '587',
  smtpUsername: '',
  smtpPassword: '',
  senderName: 'Nuxnan',
  senderEmail: 'noreply@nuxnan.com'
})

// Payment settings
const paymentSettings = ref({
  enablePayments: true,
  currency: 'THB',
  stripeEnabled: false,
  stripePublicKey: '',
  stripeSecretKey: '',
  omiseEnabled: true,
  omisePublicKey: '',
  omiseSecretKey: ''
})

// Save settings
const saveSettings = async () => {
  isSaving.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    // Show success message
    alert('บันทึกการตั้งค่าเรียบร้อย')
  } catch (error) {
    console.error('Failed to save settings:', error)
    alert('เกิดข้อผิดพลาดในการบันทึก')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">ตั้งค่าระบบ</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          จัดการการตั้งค่าทั่วไปของระบบ
        </p>
      </div>
      <button
        @click="saveSettings"
        :disabled="isSaving"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors disabled:opacity-50"
      >
        <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
        {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
      </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Sidebar -->
      <div class="lg:w-64 flex-shrink-0">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <nav class="space-y-1">
            <button class="min-h-[44px] sm:min-h-0"
              v-for="section in sections"
              :key="section.id"
              @click="activeSection = section.id"
              :class="[
                'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-colors',
                activeSection === section.id
                  ? 'bg-hopeui-primary-100 dark:bg-hopeui-primary-900 text-hopeui-primary-600 dark:text-hopeui-primary-400'
                  : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'
              ]"
            >
              <Icon :icon="section.icon" class="w-5 h-5" />
              <span class="font-medium">{{ section.name }}</span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <!-- General Settings -->
          <div v-if="activeSection === 'general'" class="space-y-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">การตั้งค่าทั่วไป</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  ชื่อเว็บไซต์
                </label>
                <input
                  v-model="generalSettings.siteName"
                  type="text"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  อีเมลติดต่อ
                </label>
                <input
                  v-model="generalSettings.contactEmail"
                  type="email"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  คำอธิบายเว็บไซต์
                </label>
                <textarea
                  v-model="generalSettings.siteDescription"
                  rows="3"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  เขตเวลา
                </label>
                <select
                  v-model="generalSettings.timezone"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                >
                  <option value="Asia/Bangkok">Asia/Bangkok (GMT+7)</option>
                  <option value="UTC">UTC (GMT+0)</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  ภาษาเริ่มต้น
                </label>
                <select
                  v-model="generalSettings.language"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                >
                  <option value="th">ไทย</option>
                  <option value="en">English</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Appearance Settings -->
          <div v-else-if="activeSection === 'appearance'" class="space-y-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">การตั้งค่าการแสดงผล</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  สีหลัก
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="appearanceSettings.primaryColor"
                    type="color"
                    class="w-12 h-12 flex-shrink-0 rounded-xl border border-slate-200 dark:border-slate-600 cursor-pointer"
                  />
                  <input
                    v-model="appearanceSettings.primaryColor"
                    type="text"
                    class="min-w-0 flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  URL โลโก้
                </label>
                <input
                  v-model="appearanceSettings.logoUrl"
                  type="text"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                />
              </div>

              <div class="md:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input
                    v-model="appearanceSettings.enableDarkMode"
                    type="checkbox"
                    class="w-5 h-5 rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                  />
                  <span class="text-slate-700 dark:text-slate-300">เปิดใช้งาน Dark Mode</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Payment Settings -->
          <div v-else-if="activeSection === 'payment'" class="space-y-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">การตั้งค่าการชำระเงิน</h2>

            <div class="space-y-4">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  v-model="paymentSettings.enablePayments"
                  type="checkbox"
                  class="w-5 h-5 rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                />
                <span class="text-slate-700 dark:text-slate-300">เปิดใช้งานระบบชำระเงิน</span>
              </label>

              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  สกุลเงิน
                </label>
                <select
                  v-model="paymentSettings.currency"
                  class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                >
                  <option value="THB">THB - บาทไทย</option>
                  <option value="USD">USD - ดอลลาร์สหรัฐ</option>
                </select>
              </div>

              <!-- Omise Settings -->
              <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input
                    v-model="paymentSettings.omiseEnabled"
                    type="checkbox"
                    class="w-5 h-5 rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                  />
                  <span class="font-medium text-slate-800 dark:text-white">Omise</span>
                </label>

                <div v-if="paymentSettings.omiseEnabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Public Key</label>
                    <input
                      v-model="paymentSettings.omisePublicKey"
                      type="text"
                      placeholder="pkey_xxx"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 rounded-lg text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Secret Key</label>
                    <input
                      v-model="paymentSettings.omiseSecretKey"
                      type="password"
                      placeholder="skey_xxx"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 rounded-lg text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Other sections placeholder -->
          <div v-else class="text-center py-12">
            <Icon icon="fluent:settings-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
            <p class="text-slate-500 mt-4">การตั้งค่าส่วนนี้กำลังพัฒนา</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
