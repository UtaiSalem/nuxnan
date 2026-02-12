<template>
  <div class="space-y-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4">
        <i class="fas fa-cog mr-2 text-purple-600"></i>
        การตั้งค่าระบบ
      </h2>
      <p class="text-gray-600 mb-6">
        จัดการการตั้งค่าและกำหนดค่าต่างๆ ของระบบการเยี่ยมบ้าน
      </p>

      <!-- Settings Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeSettingsTab = 'zones'"
            :class="[
              activeSettingsTab === 'zones'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            <i class="fas fa-map-marked-alt mr-2"></i>
            จัดการโซน
          </button>
          <!-- <button
            @click="activeSettingsTab = 'general'"
            :class="[
              activeSettingsTab === 'general'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            <i class="fas fa-sliders-h mr-2"></i>
            ตั้งค่าทั่วไป
          </button> -->
          <!-- <button
            @click="activeSettingsTab = 'notifications'"
            :class="[
              activeSettingsTab === 'notifications'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            <i class="fas fa-bell mr-2"></i>
            การแจ้งเตือน
          </button> -->
        </nav>
      </div>

      <!-- Zone Management Tab -->
      <div v-if="activeSettingsTab === 'zones'">
        <ZoneManagement />
      </div>

      <!-- General Settings Tab -->
      <div v-if="activeSettingsTab === 'general'">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
            <i class="fas fa-sliders-h text-blue-600 text-2xl"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">ตั้งค่าทั่วไป</h3>
          <p class="text-gray-600 mb-4">ฟีเจอร์นี้กำลังอยู่ระหว่างการพัฒนา</p>
          <div class="text-sm text-gray-500">
            <p class="mb-2">ฟีเจอร์ที่กำลังพัฒนา:</p>
            <ul class="inline-block text-left space-y-1">
              <li>• ตั้งค่าระยะเวลาการเยี่ยมบ้าน</li>
              <li>• กำหนดเทมเพลตรายงาน</li>
              <li>• ตั้งค่าสิทธิ์การเข้าถึง</li>
              <li>• จัดการประเภทการเยี่ยมบ้าน</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Notifications Tab -->
      <div v-if="activeSettingsTab === 'notifications'">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
            <i class="fas fa-bell text-yellow-600 text-2xl"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">การแจ้งเตือน</h3>
          <p class="text-gray-600 mb-4">ฟีเจอร์นี้กำลังอยู่ระหว่างการพัฒนา</p>
          <div class="text-sm text-gray-500">
            <p class="mb-2">ฟีเจอร์ที่กำลังพัฒนา:</p>
            <ul class="inline-block text-left space-y-1">
              <li>• แจ้งเตือนการเยี่ยมบ้านที่กำลังจะถึง</li>
              <li>• แจ้งเตือนการเยี่ยมบ้านที่เกินกำหนด</li>
              <li>• แจ้งเตือนผ่าน Email/LINE</li>
              <li>• ตั้งค่าความถี่การแจ้งเตือน</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ZoneManagement from './ZoneManagement.vue'

const activeSettingsTab = ref('zones')
</script>
