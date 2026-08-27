<script setup lang="ts">
import { ref, watch, onMounted, inject } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()

const settingsData = inject<any>('settingsData')
const reloadSettings = inject<() => Promise<void>>('reloadSettings', async () => {})
const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoading = ref(false)

type ProfileTabId = 'identity' | 'contact' | 'career'

const profileTabs: { id: ProfileTabId; label: string; icon: string }[] = [
  { id: 'identity', label: 'ข้อมูลตัวตน', icon: 'fluent:person-24-regular' },
  { id: 'contact', label: 'ข้อมูลติดต่อ', icon: 'fluent:location-24-regular' },
  { id: 'career', label: 'ข้อมูลอาชีพ', icon: 'fluent:briefcase-24-regular' },
]

const activeProfileTab = ref<ProfileTabId>('identity')

const profileForm = ref({
  first_name: '',
  last_name: '',
  bio: '',
  location: '',
  website: '',
  birthdate: '',
  gender: 'male',
  address: '',
  city: '',
  country: '',
  postal_code: '',
  job_title: '',
  company: '',
  industry: '',
  experience_years: '',
})

const skillsList = ref<string[]>([])
const skillInput = ref('')

let watcherActive = false
watch([profileForm, skillsList], () => {
  if (watcherActive) markDirty()
}, { deep: true })

function hydrateForm(data: any) {
  if (!data) return
  const p = data.profile || {}

  profileForm.value = {
    first_name: p.first_name || '',
    last_name: p.last_name || '',
    bio: p.bio || '',
    location: p.location || '',
    website: p.website || '',
    birthdate: p.birthdate ? p.birthdate.split('T')[0] : '',
    gender: p.gender || 'male',
    address: p.address || '',
    city: p.city || '',
    country: p.country || '',
    postal_code: p.postal_code || '',
    job_title: p.job_title || '',
    company: p.company || '',
    industry: p.industry || '',
    experience_years: p.experience_years || '',
  }

  skillsList.value = Array.isArray(p.skills)
    ? p.skills
    : (p.skills ? p.skills.split(',').map((s: string) => s.trim()).filter(Boolean) : [])

  // start watching after initial load so form hydration doesn't trigger dirty
  setTimeout(() => {
    watcherActive = true
  }, 100)
}

onMounted(() => {
  if (settingsData?.value) {
    hydrateForm(settingsData.value)
  }
})

watch(() => settingsData?.value, (newData) => {
  if (newData && !watcherActive) {
    hydrateForm(newData)
  }
}, { immediate: true })

function addSkill() {
  const val = skillInput.value.trim().replace(/,$/, '')
  if (!val || skillsList.value.includes(val)) return
  skillsList.value.push(val)
  skillInput.value = ''
}

function removeSkill(index: number) {
  skillsList.value.splice(index, 1)
}

function onSkillKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addSkill()
  }
}

async function saveProfile() {
  isLoading.value = true
  try {
    const res = await api.post<any>('/api/settings/profile', {
      ...profileForm.value,
      skills: skillsList.value,
    })

    if (res.success) {
      markClean()
      await reloadSettings()
      Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ!',
        text: 'ข้อมูลโปรไฟล์ถูกอัปเดตแล้ว',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
      })
    }
  } catch (error: any) {
    Swal.fire('Error', error.data?.message || 'Update failed', 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
<div class="space-y-6">
  <!-- Profile Form Card -->
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/40">
      <div class="grid grid-cols-3">
          <button
            v-for="tab in profileTabs"
            :key="tab.id"
            type="button"
            @click="activeProfileTab = tab.id"
            class="flex min-h-14 min-w-0 items-center justify-center gap-2 border-b-2 px-0 py-4 text-xs font-semibold transition-all sm:text-sm"
            :class="activeProfileTab === tab.id
              ? 'border-blue-500 bg-white text-blue-600 dark:bg-gray-800 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:bg-white/70 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/70 dark:hover:text-white'"
          >
            <Icon :icon="tab.icon" class="w-4 h-4 flex-shrink-0" />
            <span class="truncate">{{ tab.label }}</span>
          </button>
        </div>
      </div>

    <div class="p-6 space-y-10">

      <!-- Section: ข้อมูลตัวตน -->
      <section v-if="activeProfileTab === 'identity'" class="space-y-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">ข้อมูลตัวตน</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลที่แสดงบนโปรไฟล์ของคุณ</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อจริง</label>
            <input v-model="profileForm.first_name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ชื่อจริง" />
          </div>
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">นามสกุล</label>
            <input v-model="profileForm.last_name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="นามสกุล" />
          </div>

          <div class="md:col-span-2 space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เกี่ยวกับฉัน (Bio)</label>
            <textarea v-model="profileForm.bio" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="บอกเล่าเกี่ยวกับตัวคุณ..."></textarea>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">วันเกิด</label>
            <input v-model="profileForm.birthdate" type="date" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เพศ</label>
            <select v-model="profileForm.gender" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
              <option value="male">ชาย</option>
              <option value="female">หญิง</option>
              <option value="other">อื่นๆ</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Section: ข้อมูลติดต่อ -->
      <section v-else-if="activeProfileTab === 'contact'" class="space-y-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <Icon icon="fluent:location-24-regular" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">ข้อมูลติดต่อ</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">ที่อยู่และช่องทางติดต่อของคุณ</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">สถานที่ (City, Country)</label>
            <div class="relative">
              <Icon icon="fluent:location-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input v-model="profileForm.location" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="กรุงเทพมหานคร, ประเทศไทย" />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เว็บไซต์</label>
            <div class="relative">
              <Icon icon="fluent:globe-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input v-model="profileForm.website" type="url" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="https://yourwebsite.com" />
            </div>
          </div>

          <div class="md:col-span-2 space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ที่อยู่ละเอียด</label>
            <div class="relative">
              <Icon icon="fluent:home-24-regular" class="absolute left-3 top-3 text-gray-400 w-5 h-5" />
              <textarea v-model="profileForm.address" rows="2" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล"></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 md:col-span-2 gap-6">
            <div class="space-y-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เมือง/จังหวัด</label>
              <input v-model="profileForm.city" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="เมือง/จังหวัด" />
            </div>
            <div class="space-y-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ประเทศ</label>
              <input v-model="profileForm.country" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ประเทศ" />
            </div>
            <div class="space-y-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">รหัสไปรษณีย์</label>
              <input v-model="profileForm.postal_code" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="รหัสไปรษณีย์" />
            </div>
          </div>
        </div>
      </section>

      <!-- Section: ข้อมูลอาชีพ -->
      <section v-else class="space-y-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <Icon icon="fluent:briefcase-24-regular" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">ข้อมูลอาชีพ</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลการทำงานและทักษะของคุณ</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ตำแหน่งงาน</label>
            <div class="relative">
              <Icon icon="fluent:briefcase-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input v-model="profileForm.job_title" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ตำแหน่งงาน" />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">บริษัท/องค์กร</label>
            <div class="relative">
              <Icon icon="fluent:building-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input v-model="profileForm.company" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ชื่อบริษัท/องค์กร" />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">อุตสาหกรรม</label>
            <input v-model="profileForm.industry" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="อุตสาหกรรม" />
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ประสบการณ์ (ปี)</label>
            <input v-model="profileForm.experience_years" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="จำนวนปี" />
          </div>

          <!-- Skills Tag Input -->
          <div class="md:col-span-2 space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ทักษะ</label>

            <div class="min-h-[44px] flex flex-wrap gap-2 p-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
              <span
                v-for="(skill, i) in skillsList"
                :key="i"
                class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ skill }}
                <button
                  type="button"
                  @click="removeSkill(i)"
                  class="ml-0.5 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                >
                  <Icon icon="fluent:dismiss-16-regular" class="w-3.5 h-3.5" />
                </button>
              </span>

              <input
                v-model="skillInput"
                type="text"
                @keydown="onSkillKeydown"
                @blur="addSkill"
                class="flex-1 min-w-[120px] bg-transparent outline-none text-sm text-gray-800 dark:text-white placeholder-gray-400"
                placeholder="พิมพ์ทักษะแล้วกด Enter หรือ ,"
              />
            </div>
            <p class="text-xs text-gray-500">กด Enter หรือ , เพื่อเพิ่มทักษะ</p>
          </div>
        </div>
      </section>

      <!-- Save Button -->
      <div class="pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-end">
        <button
          @click="saveProfile"
          :disabled="isLoading"
          class="min-h-[44px] sm:min-h-0 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
        >
          <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
          <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
          บันทึกโปรไฟล์
        </button>
      </div>
    </div>
  </div>
</div>
</template>
