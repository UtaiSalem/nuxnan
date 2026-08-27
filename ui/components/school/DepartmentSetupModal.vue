<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface TemplateItem {
  name: string
  type: string
  description?: string
  children?: TemplateItem[]
}

const props = defineProps<{
  visible: boolean
  academyId: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success'): void
}>()

const api = useApi()
const template = ref<TemplateItem[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const hasExisting = ref(false)
const forceMode = ref(false)

const typeIcons: Record<string, string> = {
  office: 'heroicons:building-office',
  department: 'heroicons:briefcase',
  section: 'heroicons:clipboard-document-list',
  academic_group: 'heroicons:book-open',
}

const typeColors: Record<string, string> = {
  office: 'text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/50',
  department: 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-900/50',
  section: 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50',
  academic_group: 'text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/50',
}

const typeLabels: Record<string, string> = {
  office: 'สำนัก',
  department: 'ฝ่าย',
  section: 'งาน',
  academic_group: 'กลุ่มสาระ',
}

const totalCount = computed(() => {
  let count = 0
  const countItems = (items: TemplateItem[]) => {
    for (const item of items) {
      count++
      if (item.children) countItems(item.children)
    }
  }
  countItems(template.value)
  return count
})

watch(() => props.visible, async (val) => {
  if (val && props.academyId) {
    await fetchTemplate()
  }
})

const fetchTemplate = async () => {
  if (!props.academyId) return
  isLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/departments/template`)
    if (response.success) {
      template.value = response.data.template
    }
  } catch (err) {
    console.error('Failed to fetch template:', err)
  } finally {
    isLoading.value = false
  }
}

const setupDepartments = async () => {
  if (!props.academyId) return
  isSubmitting.value = true
  try {
    const response: any = await api.post(`/api/academies/${props.academyId}/departments/setup`, {
      force: forceMode.value,
    })
    if (response.success) {
      emit('success')
    }
  } catch (err: any) {
    if (err.response?.status === 409) {
      hasExisting.value = true
    } else {
      console.error('Setup failed:', err)
    }
  } finally {
    isSubmitting.value = false
  }
}

const confirmForce = () => {
  forceMode.value = true
  hasExisting.value = false
  setupDepartments()
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่าโครงสร้างฝ่ายงานมาตรฐาน</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">โครงสร้าง 5 ฝ่ายตามแนวทาง สพฐ. ({{ totalCount }} รายการ)</p>
          </div>
          <button @click="emit('close')" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-5">
          <div v-if="isLoading" class="flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
          </div>

          <!-- Existing warning -->
          <div v-else-if="hasExisting" class="text-center py-8 space-y-4">
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
              <Icon icon="fluent:warning-24-filled" class="w-10 h-10 text-amber-500 mx-auto mb-3" />
              <h4 class="font-medium text-gray-900 dark:text-white mb-1">มีโครงสร้างฝ่ายงานอยู่แล้ว</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">หากต้องการสร้างเพิ่มเติม ระบบจะข้ามรายการที่มีชื่อซ้ำ</p>
            </div>
            <div class="flex items-center justify-center gap-3">
              <button
                @click="hasExisting = false"
                class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                @click="confirmForce"
                :disabled="isSubmitting"
                class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 flex items-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>สร้างเพิ่มเติม</span>
              </button>
            </div>
          </div>

          <!-- Tree preview -->
          <div v-else class="space-y-2">
            <template v-for="item in template" :key="item.name">
              <!-- Office (root) -->
              <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20">
                  <div :class="['p-2 rounded-lg', typeColors[item.type]]">
                    <Icon :icon="typeIcons[item.type]" class="w-5 h-5" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ item.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ typeLabels[item.type] }}</p>
                  </div>
                </div>

                <!-- Departments -->
                <div v-if="item.children" class="divide-y divide-gray-100 dark:divide-gray-700">
                  <div v-for="dept in item.children" :key="dept.name" class="p-3 pl-8">
                    <div class="flex items-center gap-3 mb-2">
                      <div :class="['p-1.5 rounded-lg', typeColors[dept.type]]">
                        <Icon :icon="typeIcons[dept.type]" class="w-4 h-4" />
                      </div>
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ dept.name }}</p>
                        <p v-if="dept.description" class="text-xs text-gray-500 dark:text-gray-400">{{ dept.description }}</p>
                      </div>
                    </div>

                    <!-- Sections / Academic Groups -->
                    <div v-if="dept.children" class="ml-8 space-y-1">
                      <div
                        v-for="child in dept.children"
                        :key="child.name"
                        class="flex items-center gap-2 py-1.5 px-3 rounded-lg bg-gray-50 dark:bg-gray-700/30"
                      >
                        <div :class="['p-1 rounded', typeColors[child.type]]">
                          <Icon :icon="typeIcons[child.type]" class="w-3.5 h-3.5" />
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ child.name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">{{ typeLabels[child.type] }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="!isLoading && !hasExisting" class="p-5 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50">
          <button
            @click="emit('close')"
            class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            ยกเลิก
          </button>
          <button
            @click="setupDepartments"
            :disabled="isSubmitting || template.length === 0"
            class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary-500/20"
          >
            <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
            <Icon v-else icon="fluent:building-24-filled" class="w-5 h-5" />
            <span>{{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างโครงสร้างฝ่ายงาน' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
