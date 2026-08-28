<template>
  <div class="space-y-8">
    <div class="text-center max-w-2xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">นำเข้าข้อมูลนักเรียน</h2>
      <p class="mt-2 text-gray-500 dark:text-gray-400">อัปโหลดไฟล์ CSV ตามแบบฟอร์มเพื่อนำเข้าข้อมูลนักเรียนจำนวนมากเข้าสู่ระบบ</p>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 sm:p-6 flex flex-col md:flex-row gap-6 items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">1. ดาวน์โหลดแบบฟอร์ม (CSV Template)</h3>
        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">กรุณาใช้ไฟล์แม่แบบนี้ในการกรอกข้อมูล เพื่อให้ระบบอ่านข้อมูลได้อย่างถูกต้อง (ห้ามแก้ไขหัวตาราง)</p>
      </div>
      <button @click="handleDownloadTemplate" class="btn-primary flex items-center gap-2 whitespace-nowrap">
        <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
        ดาวน์โหลดแบบฟอร์ม
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">2. ตั้งค่าการนำเข้า</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              รูปแบบการทำงาน <span class="text-red-500">*</span>
            </label>
            <select v-model="importType" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
              <option value="new_intake">นำเข้านักเรียนใหม่ (New Intake)</option>
              <option value="roster_reconciliation">จัดห้องเรียนใหม่ (Roster Reconciliation ด้วย Student Code)</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              ปีการศึกษา <span class="text-red-500">*</span>
            </label>
            <select v-model="academicYearId" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
              <option :value="null">-- กรุณาเลือกปีการศึกษา --</option>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name || year.year }}</option>
            </select>
          </div>
          <div v-if="importType === 'new_intake'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น/ห้อง (ถ้านำเข้าทีละห้อง)</label>
            <select v-model="classroomId" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
              <option :value="null">-- ไม่ตั้งค่าเริ่มต้น --</option>
              <option v-for="room in classrooms" :key="room.id" :value="room.id">{{ room.display_name || room.name || `${room.grade_level}/${room.section}` }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">3. อัปโหลดไฟล์ข้อมูล</h3>
        
        <div 
          class="border-2 border-dashed rounded-xl p-8 text-center transition-colors relative"
          :class="dragActive ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
          @dragenter.prevent="dragActive = true"
          @dragleave.prevent="dragActive = false"
          @dragover.prevent
          @drop.prevent="handleDrop"
        >
          <input type="file" ref="fileInput" accept=".csv,.json" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="handleFileSelect" :disabled="isUploading" />
          <Icon icon="fluent:cloud-arrow-up-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
          <p class="text-sm text-gray-600 dark:text-gray-400">ลากไฟล์ CSV หรือ JSON มาวางที่นี่ หรือ <span class="text-primary-600 font-medium">คลิกเพื่อเลือกไฟล์</span></p>
          <p class="text-xs text-gray-500 mt-2" v-if="selectedFile">ไฟล์ที่เลือก: {{ selectedFile.name }}</p>
        </div>

        <div class="mt-4 flex justify-end">
          <button 
            @click="submitUpload" 
            :disabled="!selectedFile || !academicYearId || isUploading"
            class="btn-primary flex items-center gap-2"
          >
            <Icon v-if="isUploading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
            อัปโหลดและตรวจสอบ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useStudentImportService } from '../../../services/studentImportService'
import { useStudentImport } from '../../../composables/useStudentImport'
import { useApi } from '../../../composables/useApi'

const emit = defineEmits(['next'])
const route = useRoute()
const academyName = route.params.name as string
const { downloadTemplate } = useStudentImportService()
const { uploadFile, isUploading } = useStudentImport(academyName)
const api = useApi()

const academicYears = ref<any[]>([])
const classrooms = ref<any[]>([])
const academicYearId = ref<number | null>(null)
const classroomId = ref<number | null>(null)
const importType = ref('new_intake')

const dragActive = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName}/academic-years`)
    academicYears.value = res?.academicYears ?? res?.data ?? []
  } catch (error) {
    console.error('Failed to fetch academic years', error)
  }
}

const fetchClassrooms = async (yearId?: number | null) => {
  try {
    const url = yearId 
      ? `/api/academies/${academyName}/classrooms?academic_year_id=${yearId}`
      : `/api/academies/${academyName}/classrooms`
    const res: any = await api.get(url)
    classrooms.value = res?.classrooms ?? res?.data ?? []
  } catch (error) {
    console.error('Failed to fetch classrooms', error)
  }
}

watch(academicYearId, (newVal) => {
  fetchClassrooms(newVal)
})

onMounted(() => {
  fetchAcademicYears()
  fetchClassrooms()
})

const handleDrop = (e: DragEvent) => {
  dragActive.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
    const file = e.dataTransfer.files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext === 'csv' || ext === 'json') {
      selectedFile.value = file
    } else {
      alert('กรุณาอัปโหลดไฟล์ CSV หรือ JSON เท่านั้น')
    }
  }
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0]
  }
}

const handleDownloadTemplate = async () => {
  try {
    await downloadTemplate(academyName)
  } catch (error) {
    alert('ไม่สามารถดาวน์โหลดแบบฟอร์มได้')
  }
}

const submitUpload = async () => {
  if (!selectedFile.value || !academicYearId.value) return
  try {
    const defaults: Record<string, any> = {}
    if (importType.value === 'new_intake' && classroomId.value) {
      const selectedRoom = classrooms.value.find(r => r.id === classroomId.value)
      if (selectedRoom) {
        defaults.grade_level = selectedRoom.grade_level
        defaults.section = selectedRoom.section
      }
    }
    
    await uploadFile(
      selectedFile.value,
      academicYearId.value,
      Object.keys(defaults).length > 0 ? defaults : undefined,
      importType.value
    )
    emit('next')
  } catch (e) {
    alert('เกิดข้อผิดพลาดในการอัปโหลด กรุณาลองใหม่อีกครั้ง')
  }
}
</script>
