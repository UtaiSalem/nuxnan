<script setup lang="ts">
/**
 * Academy Admin - Settings
 * หน้าตั้งค่าโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const activeTab = ref('general')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isOwner, fetchMyRole } = useAcademyRole(academyId)

// Form
const form = ref({
  name: '',
  name_en: '',
  description: '',
  description_en: '',
  email: '',
  phone: '',
  website: '',
  address: '',
  province: '',
  country: 'Thailand',
  privacy: 'public' as 'public' | 'private',
  join_mode: 'open' as 'open' | 'approval' | 'invite_only',
  allow_student_registration: true,
  allow_parent_registration: true,
  show_member_list: true,
  show_course_list: true,
})

// Avatar/Cover
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const coverFile = ref<File | null>(null)
const coverPreview = ref<string | null>(null)

const tabs = [
  { id: 'general', label: 'ข้อมูลทั่วไป', icon: 'fluent:building-24-regular' },
  { id: 'contact', label: 'ข้อมูลติดต่อ', icon: 'fluent:mail-24-regular' },
  { id: 'privacy', label: 'ความเป็นส่วนตัว', icon: 'fluent:lock-closed-24-regular' },
  { id: 'registration', label: 'การลงทะเบียน', icon: 'fluent:person-add-24-regular' },
  { id: 'danger', label: 'โซนอันตราย', icon: 'fluent:warning-24-regular' },
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('settings.manage') && !isOwner.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      // Populate form
      populateForm()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const populateForm = () => {
  if (!academy.value) return
  
  form.value = {
    name: academy.value.name || '',
    name_en: academy.value.name_en || '',
    description: academy.value.description || '',
    description_en: academy.value.description_en || '',
    email: academy.value.email || '',
    phone: academy.value.phone || '',
    website: academy.value.website || '',
    address: academy.value.address || '',
    province: academy.value.province || '',
    country: academy.value.country || 'Thailand',
    privacy: academy.value.privacy || 'public',
    join_mode: academy.value.join_mode || 'open',
    allow_student_registration: academy.value.allow_student_registration ?? true,
    allow_parent_registration: academy.value.allow_parent_registration ?? true,
    show_member_list: academy.value.show_member_list ?? true,
    show_course_list: academy.value.show_course_list ?? true,
  }
  
  avatarPreview.value = academy.value.logo_url || academy.value.logo
  coverPreview.value = academy.value.cover_url || academy.value.cover
}

const onAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    avatarFile.value = target.files[0]
    avatarPreview.value = URL.createObjectURL(target.files[0])
  }
}

const onCoverChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    coverFile.value = target.files[0]
    coverPreview.value = URL.createObjectURL(target.files[0])
  }
}

const saveSettings = async () => {
  if (!academyId.value) return
  
  isSaving.value = true
  try {
    const formData = new FormData()
    
    // Add form fields
    Object.entries(form.value).forEach(([key, value]) => {
      formData.append(key, String(value))
    })
    
    // Add files if changed
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    if (coverFile.value) {
      formData.append('cover', coverFile.value)
    }

    const response: any = await api.post(`/api/academies/${academyId.value}/settings`, formData)
    
    if (response.success) {
      Swal.fire('สำเร็จ', 'บันทึกการตั้งค่าเรียบร้อยแล้ว', 'success')
      
      // Update academy name in URL if changed
      if (response.academy?.name_slug && response.academy.name_slug !== academyName.value) {
        navigateTo(`/academies/${response.academy.name_slug}/admin/settings`)
      }
    }
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถบันทึกได้', 'error')
  } finally {
    isSaving.value = false
  }
}

const confirmDeleteAcademy = async () => {
  const result = await Swal.fire({
    title: 'ลบโรงเรียน?',
    text: 'คุณแน่ใจหรือไม่ว่าต้องการลบโรงเรียนนี้? ข้อมูลทั้งหมดจะถูกลบถาวรและไม่สามารถกู้คืนได้',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444',
    input: 'text',
    inputLabel: `พิมพ์ "${academy.value?.name}" เพื่อยืนยัน`,
    inputPlaceholder: academy.value?.name,
    preConfirm: (value) => {
      if (value !== academy.value?.name) {
        Swal.showValidationMessage('ชื่อไม่ตรงกัน')
      }
    }
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/academies/${academyId.value}`)
      Swal.fire('สำเร็จ', 'ลบโรงเรียนเรียบร้อยแล้ว', 'success')
      navigateTo('/academies')
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}

const privacyOptions = [
  { value: 'public', label: 'สาธารณะ', description: 'ทุกคนสามารถเห็นและเข้าถึงได้' },
  { value: 'private', label: 'ส่วนตัว', description: 'เฉพาะสมาชิกเท่านั้นที่สามารถเข้าถึงได้' },
]

const joinModeOptions = [
  { value: 'open', label: 'เปิดรับสมัคร', description: 'ทุกคนสามารถสมัครเป็นสมาชิกได้ทันที' },
  { value: 'approval', label: 'ต้องอนุมัติ', description: 'ต้องรอการอนุมัติจากผู้ดูแล' },
  { value: 'invite_only', label: 'เชิญเท่านั้น', description: 'เฉพาะผู้ที่ได้รับเชิญเท่านั้น' },
]
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่าโรงเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลและการตั้งค่าของโรงเรียน</p>
        </div>
        <button 
          @click="saveSettings"
          :disabled="isSaving"
          class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2 disabled:opacity-50"
        >
          <span v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
          <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
          บันทึก
        </button>
      </div>

      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Tabs Sidebar -->
        <div class="lg:w-56 shrink-0">
          <nav class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                activeTab === tab.id
                  ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 border-l-4 border-primary-500'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-4 border-transparent',
                tab.id === 'danger' && 'text-red-600 dark:text-red-400'
              ]"
            >
              <Icon :icon="tab.icon" class="w-5 h-5" />
              <span class="text-sm font-medium">{{ tab.label }}</span>
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="flex-1">
          <!-- General Tab -->
          <div v-if="activeTab === 'general'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ข้อมูลทั่วไป</h2>

            <!-- Cover & Avatar -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปปก</label>
                <div class="relative h-32 bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden">
                  <img 
                    v-if="coverPreview" 
                    :src="coverPreview" 
                    class="w-full h-full object-cover"
                  />
                  <label class="absolute inset-0 flex items-center justify-center cursor-pointer hover:bg-black/20 transition-colors">
                    <Icon icon="fluent:camera-24-regular" class="w-8 h-8 text-gray-400" />
                    <input type="file" accept="image/*" @change="onCoverChange" class="hidden" />
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปโปรไฟล์</label>
                <div class="flex items-center gap-4">
                  <div class="relative w-20 h-20">
                    <img 
                      :src="avatarPreview || '/images/default-academy.png'"
                      class="w-20 h-20 rounded-xl object-cover"
                    />
                    <label class="absolute inset-0 flex items-center justify-center cursor-pointer rounded-xl hover:bg-black/20 transition-colors">
                      <Icon icon="fluent:camera-24-regular" class="w-6 h-6 text-white/80" />
                      <input type="file" accept="image/*" @change="onAvatarChange" class="hidden" />
                    </label>
                  </div>
                  <p class="text-sm text-gray-500">รูปภาพขนาด 200x200 พิกเซลขึ้นไป</p>
                </div>
              </div>
            </div>

            <!-- Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อโรงเรียน (ภาษาไทย) *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อโรงเรียน (ภาษาอังกฤษ)
                </label>
                <input
                  v-model="form.name_en"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
            </div>

            <!-- Description -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  คำอธิบาย (ภาษาไทย)
                </label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="อธิบายเกี่ยวกับโรงเรียน..."
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  คำอธิบาย (ภาษาอังกฤษ)
                </label>
                <textarea
                  v-model="form.description_en"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="Describe your academy..."
                />
              </div>
            </div>
          </div>

          <!-- Contact Tab -->
          <div v-if="activeTab === 'contact'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ข้อมูลติดต่อ</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล</label>
                <div class="relative">
                  <Icon icon="fluent:mail-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="form.email"
                    type="email"
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="academy@example.com"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โทรศัพท์</label>
                <div class="relative">
                  <Icon icon="fluent:call-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="form.phone"
                    type="tel"
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="02-xxx-xxxx"
                  />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เว็บไซต์</label>
              <div class="relative">
                <Icon icon="fluent:globe-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="form.website"
                  type="url"
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="https://www.example.com"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่</label>
              <textarea
                v-model="form.address"
                rows="2"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="ที่อยู่โรงเรียน..."
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จังหวัด</label>
                <input
                  v-model="form.province"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเทศ</label>
                <input
                  v-model="form.country"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
            </div>
          </div>

          <!-- Privacy Tab -->
          <div v-if="activeTab === 'privacy'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ความเป็นส่วนตัว</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ระดับความเป็นส่วนตัว</label>
              <div class="space-y-3">
                <label 
                  v-for="opt in privacyOptions"
                  :key="opt.value"
                  class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors"
                  :class="form.privacy === opt.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                >
                  <input
                    type="radio"
                    v-model="form.privacy"
                    :value="opt.value"
                    class="mt-1 text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ opt.label }}</p>
                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <div class="space-y-4">
              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">แสดงรายชื่อสมาชิก</p>
                  <p class="text-sm text-gray-500">อนุญาตให้ผู้อื่นดูรายชื่อสมาชิก</p>
                </div>
                <input type="checkbox" v-model="form.show_member_list" class="toggle" />
              </label>

              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">แสดงรายการคอร์ส</p>
                  <p class="text-sm text-gray-500">อนุญาตให้ผู้อื่นดูรายการคอร์ส</p>
                </div>
                <input type="checkbox" v-model="form.show_course_list" class="toggle" />
              </label>
            </div>
          </div>

          <!-- Registration Tab -->
          <div v-if="activeTab === 'registration'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">การลงทะเบียน</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">โหมดการเข้าร่วม</label>
              <div class="space-y-3">
                <label 
                  v-for="opt in joinModeOptions"
                  :key="opt.value"
                  class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors"
                  :class="form.join_mode === opt.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                >
                  <input
                    type="radio"
                    v-model="form.join_mode"
                    :value="opt.value"
                    class="mt-1 text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ opt.label }}</p>
                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <div class="space-y-4">
              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">อนุญาตการลงทะเบียนนักเรียน</p>
                  <p class="text-sm text-gray-500">นักเรียนสามารถสมัครเป็นสมาชิกได้</p>
                </div>
                <input type="checkbox" v-model="form.allow_student_registration" class="toggle" />
              </label>

              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">อนุญาตการลงทะเบียนผู้ปกครอง</p>
                  <p class="text-sm text-gray-500">ผู้ปกครองสามารถสมัครเป็นสมาชิกได้</p>
                </div>
                <input type="checkbox" v-model="form.allow_parent_registration" class="toggle" />
              </label>
            </div>
          </div>

          <!-- Danger Zone -->
          <div v-if="activeTab === 'danger'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 p-6 space-y-6">
            <h2 class="font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
              <Icon icon="fluent:warning-24-regular" class="w-5 h-5" />
              โซนอันตราย
            </h2>

            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <h3 class="font-medium text-red-800 dark:text-red-300 mb-2">ลบโรงเรียน</h3>
              <p class="text-sm text-red-600 dark:text-red-400 mb-4">
                การลบโรงเรียนจะลบข้อมูลทั้งหมดรวมถึงสมาชิก คอร์ส และข้อมูลที่เกี่ยวข้องทั้งหมด การกระทำนี้ไม่สามารถย้อนกลับได้
              </p>
              <button
                v-if="isOwner"
                @click="confirmDeleteAcademy"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                ลบโรงเรียนนี้
              </button>
              <p v-else class="text-sm text-gray-500 italic">
                เฉพาะเจ้าของโรงเรียนเท่านั้นที่สามารถลบโรงเรียนได้
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.toggle {
  @apply w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 appearance-none relative cursor-pointer;
  @apply after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600;
}

.toggle:focus {
  @apply outline-none ring-4 ring-primary-300 dark:ring-primary-800;
}

.toggle:checked {
  @apply bg-primary-600;
}

.toggle:checked::after {
  @apply translate-x-full border-white;
}
</style>
