<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>
    
    <!-- Modal Panel -->
    <div class="relative w-full max-w-lg transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all dark:bg-gray-800 flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
            ช่องทางติดต่อ
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ guardian.full_name }}
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:hover:bg-gray-700 dark:hover:text-gray-300"
        >
          <Icon icon="mdi:close" class="h-6 w-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
        
        <!-- Error Message (Top Level) -->
        <div v-if="globalError" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/50 dark:text-red-400">
          <div class="flex items-center gap-2">
            <Icon icon="mdi:alert-circle" class="h-5 w-5 flex-shrink-0" />
            <p>{{ globalError }}</p>
          </div>
        </div>

        <!-- Contact List -->
        <div class="mb-6">
          <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">ช่องทางติดต่อที่มีอยู่</h4>
          
          <div v-if="isLoading" class="space-y-2">
            <div v-for="i in 2" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
          </div>
          
          <div v-else-if="contacts.length === 0" class="rounded-lg border border-dashed border-gray-300 p-6 text-center dark:border-gray-600">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูลช่องทางติดต่อ</p>
          </div>
          
          <div v-else class="space-y-2">
            <div
              v-for="contact in contacts"
              :key="contact.id"
              class="flex flex-col gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex min-w-0 items-center gap-3">
                <Icon :icon="getContactIcon(contact.contact_type)" class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="truncate font-medium text-gray-900 dark:text-gray-100">
                      {{ contact.contact_value }}
                    </span>
                    <Icon
                      v-if="contact.is_verified"
                      icon="mdi:check-decagram"
                      class="h-4 w-4 text-green-500"
                      title="ยืนยันแล้ว"
                    />
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ getContactTypeName(contact.contact_type) }}</span>
                    <span v-if="contact.is_primary" class="inline-flex rounded bg-blue-100 px-1.5 py-0.5 font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                      หลัก
                    </span>
                  </div>
                </div>
              </div>
              
              <div v-if="canManage" class="flex items-center gap-2">
                <button
                  v-if="!contact.is_primary"
                  @click="handleSetPrimary(contact.id)"
                  :disabled="isSavingContact"
                  class="flex min-h-[44px] items-center justify-center rounded px-3 text-sm font-medium text-blue-600 hover:bg-blue-50 disabled:opacity-50 dark:text-blue-400 dark:hover:bg-blue-900/20 sm:min-h-0"
                >
                  ตั้งเป็นหลัก
                </button>
                
                <div v-if="confirmDeleteId === contact.id" class="flex items-center gap-1">
                  <span class="text-xs text-red-600 dark:text-red-400">ลบแน่ไหม?</span>
                  <button
                    @click="handleDelete(contact.id)"
                    :disabled="isSavingContact"
                    class="flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-900/20"
                  >
                    <Icon icon="mdi:check" class="h-5 w-5" />
                  </button>
                  <button
                    @click="confirmDeleteId = null"
                    :disabled="isSavingContact"
                    class="flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-700"
                  >
                    <Icon icon="mdi:close" class="h-5 w-5" />
                  </button>
                </div>
                <button
                  v-else
                  @click="confirmDeleteId = contact.id"
                  :disabled="isSavingContact"
                  class="flex min-h-[44px] w-11 items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:text-gray-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 sm:min-h-0 sm:w-auto sm:px-2"
                  title="ลบ"
                >
                  <Icon icon="mdi:trash-can-outline" class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Form -->
        <div v-if="canManage" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
          <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">เพิ่มช่องทางติดต่อ</h4>
          
          <form @submit.prevent="handleAddSubmit" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div class="sm:col-span-1">
                <label class="sr-only">ประเภท</label>
                <select
                  v-model="addForm.contact_type"
                  class="min-h-[44px] sm:min-h-0 block w-full rounded-lg border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
                >
                  <option value="phone">เบอร์โทรศัพท์</option>
                  <option value="mobile">เบอร์มือถือ</option>
                  <option value="email">อีเมล</option>
                  <option value="line">LINE ID</option>
                  <option value="facebook">Facebook</option>
                </select>
              </div>
              
              <div class="sm:col-span-2">
                <label class="sr-only">ข้อมูลติดต่อ</label>
                <input
                  v-model="addForm.contact_value"
                  type="text"
                  placeholder="กรอกข้อมูลติดต่อ"
                  class="min-h-[44px] sm:min-h-0 block w-full rounded-lg border-gray-300 py-2 pl-3 pr-3 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
                />
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <input
                id="is_primary_checkbox"
                v-model="addForm.is_primary"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:ring-offset-gray-900"
              />
              <label for="is_primary_checkbox" class="text-sm text-gray-700 dark:text-gray-300">
                ตั้งเป็นช่องทางหลัก
              </label>
            </div>
            
            <div class="flex justify-end pt-2">
              <button
                type="submit"
                :disabled="!isAddValid || isSavingContact"
                class="flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:ring-offset-gray-900 sm:min-h-0 w-full sm:w-auto"
              >
                <Icon v-if="isSavingContact" icon="mdi:loading" class="mr-2 h-5 w-5 animate-spin" />
                เพิ่มข้อมูล
              </button>
            </div>
          </form>
        </div>
        
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted } from 'vue'
import { useGuardianDirectory } from '../../../composables/useGuardianDirectory'
import { errorStatus, errorMessage } from '../../../composables/useGuardianAppointment'

const props = defineProps<{
  academyName: string
  guardian: any
  canManage: boolean
}>()

const emit = defineEmits(['close', 'changed'])

const {
  isLoading,
  isSavingContact,
  fetchContacts,
  addContact,
  deleteContact,
  setPrimaryContact
} = useGuardianDirectory(props.academyName)

const contacts = ref<any[]>([])
const globalError = ref<string | null>(null)
const confirmDeleteId = ref<number | null>(null)

const addForm = ref({
  contact_type: 'mobile',
  contact_value: '',
  is_primary: false
})

const isAddValid = computed(() => {
  if (!addForm.value.contact_value.trim()) return false
  if (addForm.value.contact_type === 'email') {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(addForm.value.contact_value)
  }
  return true
})

const getContactIcon = (type: string) => {
  const icons: Record<string, string> = {
    phone: 'mdi:phone',
    mobile: 'mdi:cellphone',
    email: 'mdi:email-outline',
    line: 'fa6-brands:line',
    facebook: 'fa6-brands:facebook',
  }
  return icons[type] || 'mdi:information-outline'
}

const getContactTypeName = (type: string) => {
  const names: Record<string, string> = {
    phone: 'เบอร์โทรศัพท์',
    mobile: 'เบอร์มือถือ',
    email: 'อีเมล',
    line: 'LINE',
    facebook: 'Facebook',
  }
  return names[type] || type
}

const loadData = async () => {
  globalError.value = null
  try {
    const res = await fetchContacts(props.guardian.id)
    contacts.value = res.data || []
  } catch (err) {
    globalError.value = 'ไม่สามารถโหลดข้อมูลช่องทางติดต่อได้ โปรดลองอีกครั้ง'
  }
}

const handleError = (err: any) => {
  const status = errorStatus(err)
  if (status === 409) {
    globalError.value = 'ช่องทางติดต่อนี้มีอยู่แล้ว'
  } else if (status === 422) {
    globalError.value = errorMessage(err) || 'ข้อมูลไม่ถูกต้อง'
  } else if (status === 403) {
    globalError.value = 'ไม่มีสิทธิ์แก้ไขช่องทางติดต่อของผู้ปกครอง'
  } else {
    globalError.value = errorMessage(err) || 'เกิดข้อผิดพลาด โปรดลองอีกครั้ง'
  }
}

const handleAddSubmit = async () => {
  if (!isAddValid.value) return
  globalError.value = null
  
  try {
    await addContact(props.guardian.id, { ...addForm.value })
    addForm.value.contact_value = ''
    addForm.value.is_primary = false
    emit('changed')
    await loadData()
  } catch (err) {
    handleError(err)
  }
}

const handleDelete = async (contactId: number) => {
  globalError.value = null
  try {
    await deleteContact(props.guardian.id, contactId)
    confirmDeleteId.value = null
    emit('changed')
    await loadData()
  } catch (err) {
    handleError(err)
  }
}

const handleSetPrimary = async (contactId: number) => {
  globalError.value = null
  try {
    await setPrimaryContact(props.guardian.id, contactId)
    emit('changed')
    await loadData()
  } catch (err) {
    handleError(err)
  }
}

onMounted(() => {
  loadData()
})
</script>
