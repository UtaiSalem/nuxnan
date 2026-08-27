<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  academyId: number | string
  group: any
}

const props = defineProps<Props>()

const { listPermissions, updatePermissions, getPermissionOptions, invalidatePostableCache } = useAcademyGroups()

const permissionOptions = ref<Array<{ key: string; label: string; default: boolean }>>([])
const selectedKeys = ref<string[]>([])
const initialKeys = ref<string[]>([])
const isLoading = ref(false)
const isSaving = ref(false)

const isDepartment = computed(() => props.group?.type === 'department')
const isDirty = computed(() => {
  const current = [...selectedKeys.value].sort().join('|')
  const initial = [...initialKeys.value].sort().join('|')
  return current !== initial
})

const loadOptions = async () => {
  const response: any = await getPermissionOptions()
  permissionOptions.value = response?.data ?? []
}

const fetchPermissions = async () => {
  if (!props.group?.id || !isDepartment.value) {
    selectedKeys.value = []
    initialKeys.value = []
    return
  }

  isLoading.value = true
  try {
    const response: any = await listPermissions(props.academyId, props.group.id)
    const keys = response?.data?.enabled_keys ?? []
    selectedKeys.value = [...keys]
    initialKeys.value = [...keys]
  } finally {
    isLoading.value = false
  }
}

watch(() => [props.group?.id, props.group?.type], async () => {
  if (permissionOptions.value.length === 0) {
    await loadOptions()
  }
  await fetchPermissions()
}, { immediate: true })

const togglePermission = (key: string) => {
  if (selectedKeys.value.includes(key)) {
    selectedKeys.value = selectedKeys.value.filter((item) => item !== key)
    return
  }

  selectedKeys.value = [...selectedKeys.value, key]
}

const save = async () => {
  if (!props.group?.id || !isDirty.value || isSaving.value) return

  isSaving.value = true
  try {
    const response: any = await updatePermissions(props.academyId, props.group.id, selectedKeys.value)
    const keys = response?.data?.enabled_keys ?? selectedKeys.value
    initialKeys.value = [...keys]
    selectedKeys.value = [...keys]

    // Invalidate cached postable groups
    invalidatePostableCache(props.academyId)

    Swal.fire({
      icon: 'success',
      title: 'บันทึกสิทธิ์แล้ว',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'บันทึกสิทธิ์ไม่สำเร็จ',
      text: error?.data?.message || '',
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div v-if="!isDepartment" class="rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-900/20 p-5 text-amber-800 dark:text-amber-200">
    สิทธิ์กลุ่มแบบละเอียดรองรับเฉพาะส่วนงานประเภท "ฝ่าย" ตาม backend ปัจจุบัน หากต้องการใช้กับประเภทอื่นต้องเปิด endpoint เพิ่มก่อน
  </div>

  <div v-else class="space-y-4">
    <div v-if="isLoading" class="py-10 flex items-center justify-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple" />
    </div>

    <template v-else>
      <button
        v-for="permission in permissionOptions"
        :key="permission.key"
        type="button"
        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between gap-4 text-left"
        @click="togglePermission(permission.key)"
      >
        <div>
          <div class="font-medium text-gray-900 dark:text-white">{{ permission.label }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ permission.key }}</div>
        </div>
        <div
          :class="[
            'w-12 h-7 rounded-full transition-colors relative',
            selectedKeys.includes(permission.key) ? 'bg-vikinger-purple' : 'bg-gray-300 dark:bg-gray-600',
          ]"
        >
          <span
            :class="[
              'absolute top-1 w-5 h-5 rounded-full bg-white transition-transform',
              selectedKeys.includes(permission.key) ? 'translate-x-6 left-0' : 'translate-x-1 left-0',
            ]"
          ></span>
        </div>
      </button>

      <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ isDirty ? 'มีการเปลี่ยนแปลงสิทธิ์ที่ยังไม่บันทึก' : 'สิทธิ์ตรงกับข้อมูลล่าสุดแล้ว' }}
        </p>
        <button
          type="button"
          :disabled="!isDirty || isSaving"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
          @click="save"
        >
          <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-4 h-4" />
          <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
          บันทึกสิทธิ์
        </button>
      </div>
    </template>
  </div>
</template>
