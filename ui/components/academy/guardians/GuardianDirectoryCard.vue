<template>
  <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 sm:p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
      <div class="flex items-center gap-3 min-w-0 flex-1">
        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-xl text-white" :class="typeBg">
          {{ typeIcon }}
        </div>
        <div class="min-w-0 flex-1 break-words">
          <p class="break-words text-sm font-semibold text-gray-900 dark:text-gray-100 sm:text-base">
            {{ guardian.full_name }}
          </p>
          <p v-if="guardian.occupation" class="break-words text-xs text-gray-500 dark:text-gray-400">
            {{ guardian.occupation }}
          </p>
        </div>
      </div>
      <!-- Action button on its own row on mobile -->
      <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
        <button
          @click="$emit('manage-contacts', guardian)"
          class="flex min-h-[44px] sm:min-h-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors w-full sm:w-auto"
        >
          <Icon icon="mdi:card-account-phone-outline" class="h-5 w-5" />
          จัดการช่องทางติดต่อ
        </button>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      <!-- Children Section -->
      <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
        <p class="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
          นักเรียนที่ดูแล ({{ guardian.children_count || 0 }})
        </p>
        
        <div v-if="guardian.children_count === 0" class="text-sm text-gray-500 dark:text-gray-400">
          ยังไม่ได้ผูกกับนักเรียนคนไหน
        </div>
        <div v-else class="space-y-2">
          <NuxtLink
            v-for="child in displayedChildren"
            :key="child.id"
            :to="`/academies/${academyName}/admin/students/${child.id}`"
            class="block rounded-lg border border-gray-200 bg-white p-2 hover:border-blue-300 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-blue-700 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ child.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ child.student_id }}
                </p>
              </div>
              <span class="ml-2 inline-flex flex-shrink-0 items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                {{ getTypeLabel(child.guardian_type) }}
              </span>
            </div>
          </NuxtLink>
          
          <button
            v-if="guardian.children?.length > 3 && !showAllChildren"
            @click="showAllChildren = true"
            class="min-h-[44px] w-full rounded border border-dashed border-gray-300 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600 sm:min-h-0"
          >
            ดูทั้งหมด ({{ guardian.children.length }})
          </button>
        </div>
      </div>

      <!-- Contacts Section -->
      <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
        <p class="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
          ช่องทางติดต่อ
        </p>
        
        <div v-if="!guardian.contacts || guardian.contacts.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
          ยังไม่มีช่องทางติดต่อ
        </div>
        <div v-else class="space-y-2">
          <component
            :is="getContactLink(contact) ? 'a' : 'div'"
            v-for="contact in guardian.contacts"
            :key="contact.id"
            :href="getContactLink(contact)"
            class="flex min-h-[44px] items-center justify-between rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-600 dark:bg-gray-800 sm:min-h-0"
            :class="{ 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors': !!getContactLink(contact) }"
          >
            <div class="flex min-w-0 items-center gap-2">
              <Icon :icon="getContactIcon(contact.contact_type)" class="h-4 w-4 flex-shrink-0 text-gray-400 dark:text-gray-500" />
              <span class="truncate text-sm text-gray-900 dark:text-gray-100">
                {{ contact.contact_value }}
              </span>
              <Icon
                v-if="contact.is_verified"
                icon="mdi:check-decagram"
                class="h-4 w-4 flex-shrink-0 text-green-500"
                title="ยืนยันแล้ว"
              />
            </div>
            <span v-if="contact.is_primary" class="ml-2 inline-flex flex-shrink-0 items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
              หลัก
            </span>
          </component>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'
import { NuxtLink } from '#components'

const props = defineProps<{
  guardian: any
  academyName: string
}>()

defineEmits(['manage-contacts'])

const showAllChildren = ref(false)

const displayedChildren = computed(() => {
  const children = props.guardian.children || []
  if (showAllChildren.value) return children
  return children.slice(0, 3)
})

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    father: 'บิดา',
    mother: 'มารดา',
    grandfather: 'ปู่/ตา',
    grandmother: 'ย่า/ยาย',
    uncle: 'ลุง/อา',
    aunt: 'ป้า/น้า',
    sibling: 'พี่/น้อง',
    other: 'อื่นๆ',
  }
  return labels[type] || type
}

// Ensure we have a default for parent type in case we need to guess from children
const bestType = computed(() => {
  // If the guardian person itself doesn't have a direct type, try to infer from first child's relation
  const childTypes = props.guardian.children?.map((c: any) => c.guardian_type) || []
  if (childTypes.length > 0) return childTypes[0]
  return 'other'
})

const typeBg = computed(() => {
  const type = bestType.value
  const colors: Record<string, string> = {
    father: 'bg-blue-500',
    mother: 'bg-pink-500',
    grandfather: 'bg-gray-500',
    grandmother: 'bg-purple-500',
    uncle: 'bg-indigo-500',
    aunt: 'bg-rose-500',
    sibling: 'bg-teal-500',
    other: 'bg-gray-400',
  }
  return colors[type] || 'bg-gray-400'
})

const typeIcon = computed(() => {
  const type = bestType.value
  const icons: Record<string, string> = {
    father: '👨',
    mother: '👩',
    grandfather: '👴',
    grandmother: '👵',
    uncle: '👨',
    aunt: '👩',
    sibling: '🧑',
    other: '👤',
  }
  return icons[type] || '👤'
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

const getContactLink = (contact: any) => {
  if (contact.contact_type === 'phone' || contact.contact_type === 'mobile') {
    return `tel:${contact.contact_value}`
  }
  if (contact.contact_type === 'email') {
    return `mailto:${contact.contact_value}`
  }
  return null
}
</script>
