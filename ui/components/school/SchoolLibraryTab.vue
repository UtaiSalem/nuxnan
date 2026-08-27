<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบห้องสมุดดิจิทัล</h2>
      <div class="flex gap-2">
        <button class="min-h-[44px] sm:min-h-0"
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="[
            activeSection === section.id
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
          ]"
        >
          {{ section.name }}
        </button>
      </div>
    </div>

    <!-- Books Catalog Section -->
    <div v-if="activeSection === 'books'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="relative w-full sm:w-64">
          <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="ค้นหาชื่อหนังสือ, ผู้เขียน, ISBN..."
            class="pl-10 w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl outline-none focus:ring-2 focus:ring-primary-500/20"
            @input="loadBooks"
          />
        </div>
        <button
          @click="showBookModal = true"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span>เพิ่มหนังสือ</span>
        </button>
      </div>

      <div v-if="loadingBooks" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <div
          v-for="book in books"
          :key="book.id"
          class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all group"
        >
          <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-900 relative overflow-hidden">
            <img 
              :src="book.cover_image || '/images/book-placeholder.png'" 
              :alt="book.title"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
            />
            <div class="absolute top-2 right-2">
              <span 
                :class="book.available_copies > 0 ? 'bg-green-500' : 'bg-red-500'"
                class="px-2 py-0.5 text-[10px] font-black text-white rounded-full uppercase shadow-sm"
              >
                {{ book.available_copies > 0 ? 'ว่าง' : 'ถูกยืม' }}
              </span>
            </div>
          </div>
          <div class="p-3">
            <h4 class="font-bold text-gray-900 dark:text-white truncate text-sm mb-0.5">{{ book.title }}</h4>
            <p class="text-[10px] text-gray-500 truncate">{{ book.author || 'ไม่ระบุผู้เขียน' }}</p>
            <div class="mt-3 flex items-center justify-between">
              <span class="text-[10px] font-bold text-gray-400">เหลือ {{ book.available_copies }}/{{ book.total_copies }} เล่ม</span>
              <button 
                @click="openBorrowModal(book)"
                :disabled="book.available_copies <= 0"
                class="text-[10px] font-black text-primary-600 hover:underline disabled:text-gray-300 uppercase tracking-tighter"
              >
                ยืมหนังสือ
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Borrowing History Section -->
    <div v-if="activeSection === 'borrowings'" class="space-y-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-left">
          <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-4">หนังสือ</th>
              <th class="px-6 py-4">ผู้ยืม</th>
              <th class="px-6 py-4">วันที่ยืม</th>
              <th class="px-6 py-4">กำหนดคืน</th>
              <th class="px-6 py-4">สถานะ</th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="item in borrowings" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
              <td class="px-6 py-4">
                <div class="font-bold text-gray-900 dark:text-white text-sm">{{ item.book?.title }}</div>
                <div class="text-[10px] text-gray-400">ISBN: {{ item.book?.isbn || '-' }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <CircleAvatar :src="item.user?.profile_photo_path" :name="item.user?.name" size="xs" />
                  <span class="text-sm font-medium">{{ item.user?.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(item.borrowed_at) }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">
                <span :class="isOverdue(item.due_at) && item.status === 'borrowed' ? 'text-red-500 font-bold' : ''">
                  {{ formatDate(item.due_at) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(item.status)" class="px-2 py-1 rounded-full text-[10px] font-black uppercase">
                  {{ getStatusLabel(item.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button 
                  v-if="item.status === 'borrowed' || item.status === 'overdue'"
                  @click="processReturn(item)"
                  class="min-h-[44px] sm:min-h-0 px-3 py-1 bg-green-500 text-white text-[10px] font-black rounded-lg hover:bg-green-600 transition-colors uppercase"
                >
                  คืนหนังสือ
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="borrowings.length === 0" class="p-12 text-center text-gray-400">
          <p>ยังไม่มีรายการยืม-คืนในช่วงนี้</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const activeSection = ref('books')
const sections = [
  { id: 'books', name: 'หนังสือทั้งหมด' },
  { id: 'borrowings', name: 'รายการยืม-คืน' },
]

const books = ref<any[]>([])
const borrowings = ref<any[]>([])
const loadingBooks = ref(false)
const loadingBorrowings = ref(false)
const searchQuery = ref('')

const showBookModal = ref(false)
const showBorrowModal = ref(false)

// Actions
const loadBooks = async () => {
  loadingBooks.value = true
  try {
    const response: any = await schoolApi.getLibraryBooks(props.academyId, { search: searchQuery.value })
    books.value = response.data?.data || []
  } catch (err) {
    console.error('Failed to load books:', err)
  } finally {
    loadingBooks.value = false
  }
}

const loadBorrowings = async () => {
  loadingBorrowings.value = true
  try {
    // Note: Reusing getLibraryBooks with extra filter or separate endpoint if existed
    // For now assuming a generic borrowings endpoint would be added to useSchoolManagement
    // const response: any = await schoolApi.getBorrowings(props.academyId)
    // borrowings.value = response.data || []
  } catch (err) {
    console.error('Failed to load borrowings:', err)
  } finally {
    loadingBorrowings.value = false
  }
}

const openBorrowModal = (_book: any) => {
  // Logic to open borrow modal
}

const processReturn = async (borrowing: any) => {
  if (!confirm(`ยืนยันการคืนหนังสือ "${borrowing.book?.title}"?`)) return
  try {
    const response: any = await schoolApi.returnBook(props.academyId, borrowing.id)
    if (response.success) {
      alert('คืนหนังสือเรียบร้อยแล้ว')
      await Promise.all([loadBooks(), loadBorrowings()])
    }
  } catch (err) {
    console.error('Failed to return book:', err)
  }
}

// Helpers
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' })
}

const isOverdue = (dueDate: string) => {
  return new Date(dueDate) < new Date()
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'borrowed': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
    case 'returned': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    case 'overdue': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    case 'lost': return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
    default: return 'bg-gray-100'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'borrowed': return 'กำลังยืม'
    case 'returned': return 'คืนแล้ว'
    case 'overdue': return 'เกินกำหนด'
    case 'lost': return 'สูญหาย'
    default: return status
  }
}

watch(activeSection, (section) => {
  if (section === 'books' && books.value.length === 0) loadBooks()
  if (section === 'borrowings' && borrowings.value.length === 0) loadBorrowings()
}, { immediate: true })

onMounted(() => {
  loadBooks()
})
</script>
