<script setup lang="ts">
/**
 * Academy Admin - Store Categories Management
 * หน้าจัดการหมวดหมู่สินค้า
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const api = useApi()
const { getCategories, createCategory, updateCategory, deleteCategory } = useSchoolStore()

// State
const academyId = ref<number | null>(null)
const categories = ref<any[]>([])
const isLoading = ref(true)
const showForm = ref(false)
const isSubmitting = ref(false)
const editingCategory = ref<any>(null)

// Form
const form = ref({
  name: '',
  description: '',
  icon: '',
  sort_order: 0,
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Popular icons for categories
const popularIcons = [
  'fluent:book-24-regular', 'fluent:food-24-regular', 'fluent:sport-24-regular',
  'fluent:tshirt-24-regular', 'fluent:pen-24-regular', 'fluent:desktop-24-regular',
  'fluent:toy-24-regular', 'fluent:gift-24-regular', 'fluent:heart-24-regular',
  'fluent:star-24-regular', 'fluent:cart-24-regular', 'fluent:music-note-2-24-regular',
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchMyRole()
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      await fetchCategories()
    }
  } catch (err) { console.error(err) }
  finally { isLoading.value = false }
})

const fetchCategories = async () => {
  if (!academyId.value) return
  try {
    categories.value = await getCategories(academyId.value)
  } catch (err) { console.error(err) }
}

const openCreateForm = () => {
  editingCategory.value = null
  form.value = { name: '', description: '', icon: '', sort_order: 0 }
  showForm.value = true
}

const openEditForm = (category: any) => {
  editingCategory.value = category
  form.value = {
    name: category.name,
    description: category.description || '',
    icon: category.icon || '',
    sort_order: category.sort_order || 0,
  }
  showForm.value = true
}

const handleSubmit = async () => {
  if (!academyId.value || isSubmitting.value) return
  isSubmitting.value = true
  try {
    let response: any
    if (editingCategory.value) {
      response = await updateCategory(academyId.value, editingCategory.value.id, form.value)
    } else {
      response = await createCategory(academyId.value, form.value)
    }
    if (response.success) {
      showForm.value = false
      await fetchCategories()
    }
  } catch (err) { console.error(err) }
  finally { isSubmitting.value = false }
}

const handleDelete = async (category: any) => {
  if (!academyId.value) return
  if (category.products_count > 0) {
    alert(`หมวดหมู่ "${category.name}" มี ${category.products_count} สินค้า ต้องย้ายสินค้าก่อน`)
    return
  }
  if (!confirm(`ต้องการลบหมวดหมู่ "${category.name}" หรือไม่?`)) return
  try {
    const response: any = await deleteCategory(academyId.value, category.id)
    if (response.success) await fetchCategories()
  } catch (err) { console.error(err) }
}

const toggleActive = async (category: any) => {
  if (!academyId.value) return
  try {
    await updateCategory(academyId.value, category.id, { is_active: !category.is_active })
    await fetchCategories()
  } catch (err) { console.error(err) }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Back -->
      <NuxtLink :to="`/academies/${academyName}/admin/store`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 transition-colors">
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับร้านค้า</span>
      </NuxtLink>

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">หมวดหมู่สินค้า</h1>
          <p class="text-gray-600 dark:text-gray-400">{{ categories.length }} หมวดหมู่</p>
        </div>
        <button @click="openCreateForm"
          class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors inline-flex items-center gap-2">
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          เพิ่มหมวดหมู่
        </button>
      </div>

      <!-- Categories List -->
      <div class="space-y-3">
        <div v-if="!categories.length" class="bg-white dark:bg-gray-800 rounded-xl p-16 text-center shadow-sm border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:tag-multiple-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีหมวดหมู่</p>
          <button @click="openCreateForm" class="text-primary-600 hover:text-primary-700 font-medium">+ สร้างหมวดหมู่แรก</button>
        </div>

        <div
          v-for="category in categories"
          :key="category.id"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                <Icon :icon="category.icon || 'fluent:tag-24-regular'" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-gray-900 dark:text-white">{{ category.name }}</p>
                  <span v-if="!category.is_active" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">ปิด</span>
                </div>
                <p class="text-sm text-gray-500">{{ category.products_count }} สินค้า</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button @click="toggleActive(category)"
                :class="category.is_active ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                class="p-2 rounded-lg transition-colors" :title="category.is_active ? 'ปิดหมวดหมู่' : 'เปิดหมวดหมู่'">
                <Icon :icon="category.is_active ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-4 h-4" />
              </button>
              <button @click="openEditForm(category)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <Icon icon="fluent:edit-24-regular" class="w-4 h-4 text-gray-500" />
              </button>
              <button @click="handleDelete(category)" class="p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                <Icon icon="fluent:delete-24-regular" class="w-4 h-4 text-red-500" />
              </button>
            </div>
          </div>

          <!-- Children -->
          <div v-if="category.children?.length" class="mt-3 ml-6 space-y-2">
            <div v-for="child in category.children" :key="child.id" class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
              <div class="flex items-center gap-2">
                <Icon :icon="child.icon || 'fluent:tag-24-regular'" class="w-4 h-4 text-gray-400" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ child.name }}</span>
                <span class="text-xs text-gray-500">({{ child.products_count }})</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showForm = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 space-y-5">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
              {{ editingCategory ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่' }}
            </h3>
            <button @click="showForm = false" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหมวดหมู่ *</label>
              <input v-model="form.name" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                placeholder="เช่น เครื่องเขียน, อุปกรณ์การเรียน" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea v-model="form.description" rows="2"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ไอคอน</label>
              <div class="grid grid-cols-6 gap-2">
                <button
                  v-for="icon in popularIcons"
                  :key="icon"
                  type="button"
                  @click="form.icon = icon"
                  :class="form.icon === icon ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'bg-gray-50 dark:bg-gray-700'"
                  class="p-2.5 rounded-lg transition-all hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                  <Icon :icon="icon" class="w-5 h-5 text-gray-700 dark:text-gray-300 mx-auto" />
                </button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับการแสดง</label>
              <input v-model.number="form.sort_order" type="number" min="0"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="showForm = false"
                class="flex-1 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                ยกเลิก
              </button>
              <button type="submit" :disabled="isSubmitting || !form.name"
                class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors">
                {{ isSubmitting ? 'กำลังบันทึก...' : (editingCategory ? 'อัพเดท' : 'สร้าง') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
