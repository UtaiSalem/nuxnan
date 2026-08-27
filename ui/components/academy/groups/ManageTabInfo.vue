<script setup lang="ts">
import { computed, onMounted, ref, watch, toRef } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useFieldValidation, validationRules } from '~/composables/useFieldValidation'

interface Props {
  group: any
}

const props = defineProps<Props>()
const emit = defineEmits<{
  updated: [group: any]
}>()

const { updateGroup } = useAcademyGroups()
const { types, fetch } = useAcademyGroupTypes()

const form = ref({
  name: '',
  description: '',
  type: 'department',
})
const isSaving = ref(false)

const nameRef = toRef(form.value, 'name')
const { error: nameError, isValid: isNameValid } = useFieldValidation(nameRef, [
  validationRules.required('กรุณากรอกชื่อส่วนงาน'),
  validationRules.minLen(3)('ชื่อส่วนงานต้องมีความยาวอย่างน้อย 3 ตัวอักษร'),
  validationRules.maxLen(50)('ชื่อส่วนงานต้องไม่เกิน 50 ตัวอักษร')
])

const resetForm = () => {
  form.value = {
    name: props.group?.name ?? '',
    description: props.group?.description ?? '',
    type: props.group?.type ?? 'department',
  }
}

watch(() => props.group, resetForm, { immediate: true, deep: true })
onMounted(() => {
  void fetch()
})

const isDirty = computed(() => {
  return form.value.name.trim() !== (props.group?.name ?? '')
    || form.value.description !== (props.group?.description ?? '')
    || form.value.type !== (props.group?.type ?? 'department')
})

const submit = async () => {
  if (!props.group?.id || !isNameValid.value || !isDirty.value || isSaving.value) return

  isSaving.value = true
  try {
    const response: any = await updateGroup(props.group.id, {
      name: form.value.name.trim(),
      description: form.value.description.trim() || null,
      type: form.value.type,
    })

    const updatedGroup = response?.group ?? {
      ...props.group,
      ...form.value,
      description: form.value.description.trim() || null,
    }

    emit('updated', updatedGroup)

    Swal.fire({
      icon: 'success',
      title: 'บันทึกข้อมูลส่วนงานแล้ว',
      timer: 1600,
      showConfirmButton: false,
    })
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'บันทึกไม่สำเร็จ',
      text: error?.data?.message || 'ไม่สามารถอัปเดตข้อมูลส่วนงานได้',
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <form class="space-y-5" @submit.prevent="submit">
    <CommonFormField label="ชื่อส่วนงาน" :error="nameError" required hint="ระบุชื่อที่ต้องการเปลี่ยนสำหรับส่วนงานนี้">
      <template #default="{ id, describedBy }">
        <input
          :id="id"
          v-model="form.name"
          type="text"
          :aria-describedby="describedBy"
          :aria-invalid="!!nameError"
          class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
        />
      </template>
    </CommonFormField>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        ประเภท
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <button
          v-for="type in types"
          :key="type.key"
          type="button"
          :class="[
            'p-3 rounded-xl border-2 flex flex-col items-center gap-1.5 transition-all text-center',
            form.type === type.key
              ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
              : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/40',
          ]"
          @click="form.type = type.key"
        >
          <Icon :icon="type.icon" class="w-5 h-5" />
          <span class="text-[11px] font-semibold leading-tight">{{ type.label }}</span>
        </button>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        รายละเอียด
      </label>
      <textarea
        v-model="form.description"
        rows="4"
        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none"
      ></textarea>
    </div>

    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ isDirty ? 'มีการเปลี่ยนแปลงที่ยังไม่บันทึก' : 'ข้อมูลตรงกับค่าปัจจุบันแล้ว' }}
      </p>
      <button
        type="submit"
        :disabled="!isDirty || !isNameValid || isSaving"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
        บันทึก
      </button>
    </div>
  </form>
</template>
