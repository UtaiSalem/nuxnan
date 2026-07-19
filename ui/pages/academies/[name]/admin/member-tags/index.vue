<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
              <NuxtLink :to="`/academies/${academyName}/admin`" class="hover:text-indigo-600">
                จัดการโรงเรียน
              </NuxtLink>
              <Icon icon="mdi:chevron-right" class="w-4 h-4 mx-1" />
              <span class="text-gray-900 dark:text-white">แท็กสมาชิก</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="mdi:tag-multiple" class="w-7 h-7" />
              จัดการแท็กสมาชิก
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              สร้างและจัดการแท็กเพื่อจัดกลุ่มสมาชิก
            </p>
          </div>
          <button
            v-if="canManage"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <Icon icon="mdi:plus" class="w-5 h-5" />
            สร้างแท็กใหม่
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <Icon icon="mdi:loading" class="w-8 h-8 text-indigo-600 animate-spin" />
      </div>

      <!-- Empty State -->
      <div v-else-if="tags.length === 0" class="text-center py-12">
        <Icon icon="mdi:tag-off-outline" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          ยังไม่มีแท็ก
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">
          สร้างแท็กเพื่อจัดกลุ่มและจัดระเบียบสมาชิกของคุณ
        </p>
        <button
          v-if="canManage"
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
        >
          <Icon icon="mdi:plus" class="w-5 h-5" />
          สร้างแท็กแรก
        </button>
      </div>

      <!-- Tag Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="tag in tags"
          :key="tag.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center"
                :style="{ backgroundColor: tag.color + '20' }"
              >
                <Icon icon="mdi:tag" class="w-5 h-5" :style="{ color: tag.color }" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">
                  {{ tag.name }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ tag.member_count }} สมาชิก
                </p>
              </div>
            </div>
            <div v-if="canManage" class="flex items-center gap-1">
              <button
                @click="openEditModal(tag)"
                class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                title="แก้ไข"
              >
                <Icon icon="mdi:pencil" class="w-4 h-4" />
              </button>
              <button
                @click="confirmDelete(tag)"
                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                title="ลบ"
              >
                <Icon icon="mdi:delete" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <p v-if="tag.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ tag.description }}
          </p>

          <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
            <span
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
              :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color }"
            >
              <Icon icon="mdi:tag" class="w-3 h-3" />
              {{ tag.name }}
            </span>
            <NuxtLink
              :to="`/academies/${academyName}/admin/member-tags/${tag.id}/members`"
              class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
            >
              ดูสมาชิก
              <Icon icon="mdi:chevron-right" class="w-4 h-4" />
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <MemberTagFormModal
      :is-open="showFormModal"
      :mode="formMode"
      :tag="selectedTag"
      @close="showFormModal = false"
      @submit="handleFormSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import MemberTagFormModal from '~/components/learn/member/MemberTagFormModal.vue'

definePageMeta({
  layout: 'main',
})

interface Tag {
  id: number
  name: string
  slug: string
  color: string
  description: string | null
  is_active: boolean
  member_count: number
  sort_order: number
}

const route = useRoute()
const { $api } = useNuxtApp()
const { can } = useAcademyRole()

const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)

const loading = ref(true)
const tags = ref<Tag[]>([])

const showFormModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedTag = ref<Tag | null>(null)

const canManage = computed(() => can('members.manage'))

onMounted(async () => {
  await fetchAcademyId()
  await fetchTags()
})

async function fetchAcademyId() {
  try {
    const response = await $api.get(`/academies/${academyName.value}`)
    if (response.data?.academy) {
      academyId.value = response.data.academy.id
    }
  } catch (error) {
    console.error('Failed to fetch academy:', error)
  }
}

async function fetchTags() {
  if (!academyId.value) return
  loading.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/member-tags`)
    if (response.data?.success) {
      tags.value = response.data.tags
    }
  } catch (error) {
    console.error('Failed to fetch tags:', error)
    Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดข้อมูลแท็กได้', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  formMode.value = 'create'
  selectedTag.value = null
  showFormModal.value = true
}

function openEditModal(tag: Tag) {
  formMode.value = 'edit'
  selectedTag.value = tag
  showFormModal.value = true
}

async function handleFormSubmit(data: { name: string; color: string; description?: string | null }) {
  if (!academyId.value) return
  
  try {
    if (formMode.value === 'create') {
      const response = await $api.post(`/academies/${academyId.value}/member-tags`, data)
      if (response.data?.success) {
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ',
          text: 'สร้างแท็กเรียบร้อยแล้ว',
          timer: 1500,
          showConfirmButton: false,
        })
        await fetchTags()
      }
    } else if (selectedTag.value) {
      const response = await $api.patch(`/academies/${academyId.value}/member-tags/${selectedTag.value.id}`, data)
      if (response.data?.success) {
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ',
          text: 'อัพเดทแท็กเรียบร้อยแล้ว',
          timer: 1500,
          showConfirmButton: false,
        })
        await fetchTags()
      }
    }
    showFormModal.value = false
  } catch (error: any) {
    Swal.fire('ผิดพลาด', error.response?.data?.message || 'ไม่สามารถบันทึกได้', 'error')
  }
}

async function confirmDelete(tag: Tag) {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    html: `คุณต้องการลบแท็ก <strong>${tag.name}</strong> หรือไม่?<br><small class="text-gray-500">แท็กจะถูกนำออกจากสมาชิกทั้งหมด</small>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed && academyId.value) {
    try {
      const response = await $api.delete(`/academies/${academyId.value}/member-tags/${tag.id}`)
      if (response.data?.success) {
        Swal.fire({
          icon: 'success',
          title: 'ลบแล้ว',
          text: 'ลบแท็กเรียบร้อยแล้ว',
          timer: 1500,
          showConfirmButton: false,
        })
        await fetchTags()
      }
    } catch (error: any) {
      Swal.fire('ผิดพลาด', error.response?.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}
</script>
