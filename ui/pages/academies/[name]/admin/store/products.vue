<script setup lang="ts">
/**
 * Academy Admin - Store Products Management
 * หน้าจัดการสินค้าในร้าน
 */
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const api = useApi()
const { getProducts, getCategories, createProduct, updateProduct, deleteProduct } = useSchoolStore()

// State
const academyId = ref<number | null>(null)
const products = ref<any[]>([])
const categories = ref<any[]>([])
const isLoading = ref(true)
const showForm = ref(false)
const isSubmitting = ref(false)
const editingProduct = ref<any>(null)
const meta = ref<any>({})

// Filters
const search = ref('')
const selectedCategory = ref<number | null>(null)
const stockFilter = ref('')
const currentPage = ref(1)

// Form
const form = ref({
  name: '',
  description: '',
  price: 0,
  compare_price: null as number | null,
  cost_price: null as number | null,
  sku: '',
  stock_quantity: 0,
  low_stock_threshold: 5,
  category_id: null as number | null,
  is_active: true,
  is_featured: false,
  payment_type: 'both' as 'points' | 'wallet' | 'both',
  points_price: null as number | null,
})

const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

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
      await Promise.all([fetchProducts(), fetchCategories()])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchProducts = async () => {
  if (!academyId.value) return
  try {
    const params: any = { page: currentPage.value, per_page: 20, include_inactive: true }
    if (search.value) params.search = search.value
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (stockFilter.value) params.stock_status = stockFilter.value

    const response: any = await getProducts(academyId.value, params)
    if (response.success) {
      products.value = response.data || []
      meta.value = response.meta || {}
    }
  } catch (err) {
    console.error('Failed to fetch products:', err)
  }
}

const fetchCategories = async () => {
  if (!academyId.value) return
  try {
    categories.value = await getCategories(academyId.value)
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

watch([search, selectedCategory, stockFilter], () => {
  currentPage.value = 1
  fetchProducts()
})

const openCreateForm = () => {
  editingProduct.value = null
  form.value = {
    name: '', description: '', price: 0, compare_price: null, cost_price: null,
    sku: '', stock_quantity: 0, low_stock_threshold: 5, category_id: null,
    is_active: true, is_featured: false, payment_type: 'both', points_price: null,
  }
  showForm.value = true
}

const openEditForm = (product: any) => {
  editingProduct.value = product
  form.value = {
    name: product.name,
    description: product.description || '',
    price: product.price,
    compare_price: product.compare_price,
    cost_price: product.cost_price,
    sku: product.sku || '',
    stock_quantity: product.stock_quantity,
    low_stock_threshold: product.low_stock_threshold || 5,
    category_id: product.category?.id || null,
    is_active: product.is_active,
    is_featured: product.is_featured,
    payment_type: product.payment_type,
    points_price: product.points_price,
  }
  showForm.value = true
}

const handleSubmit = async () => {
  if (!academyId.value || isSubmitting.value) return
  isSubmitting.value = true
  try {
    let response: any
    if (editingProduct.value) {
      response = await updateProduct(academyId.value, editingProduct.value.id, form.value)
    } else {
      response = await createProduct(academyId.value, form.value)
    }
    if (response.success) {
      showForm.value = false
      await fetchProducts()
    }
  } catch (err) {
    console.error('Failed to save product:', err)
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (product: any) => {
  if (!academyId.value || !confirm(`ต้องการลบสินค้า "${product.name}" หรือไม่?`)) return
  try {
    const response: any = await deleteProduct(academyId.value, product.id)
    if (response.success) {
      await fetchProducts()
    }
  } catch (err) {
    console.error('Failed to delete product:', err)
  }
}

const getStockBadge = (product: any) => {
  if (product.stock_quantity <= 0) return { text: 'หมด', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }
  if (product.stock_quantity <= (product.low_stock_threshold || 5)) return { text: 'ใกล้หมด', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }
  return { text: 'มีสต็อก', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Back -->
      <NuxtLink
        :to="`/academies/${academyName}/admin/store`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับร้านค้า</span>
      </NuxtLink>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการสินค้า</h1>
          <p class="text-gray-600 dark:text-gray-400">{{ meta.total || 0 }} สินค้า</p>
        </div>
        <button
          @click="openCreateForm"
          class="min-h-[44px] sm:min-h-0 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors inline-flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          เพิ่มสินค้า
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="relative">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="search"
              type="text"
              placeholder="ค้นหาสินค้า..."
              class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <select
            v-model="selectedCategory"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
          >
            <option :value="null">ทุกหมวดหมู่</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
          <select
            v-model="stockFilter"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
          >
            <option value="">ทุกสถานะสต็อก</option>
            <option value="in_stock">มีสต็อก</option>
            <option value="low_stock">ใกล้หมด</option>
            <option value="out_of_stock">หมดสต็อก</option>
          </select>
        </div>
      </div>

      <!-- Products List -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Empty State -->
        <div v-if="!products.length" class="text-center py-16">
          <Icon icon="fluent:box-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีสินค้า</p>
          <button @click="openCreateForm" class="text-primary-600 hover:text-primary-700 font-medium">
            + เพิ่มสินค้าตัวแรก
          </button>
        </div>

        <!-- Product Table (Desktop) -->
        <div v-else class="hidden md:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สินค้า</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ราคา</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สต็อก</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมวดหมู่</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden shrink-0">
                      <img
                        v-if="product.first_image"
                        :src="product.first_image"
                        :alt="product.name"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center">
                        <Icon icon="fluent:box-24-regular" class="w-6 h-6 text-gray-400" />
                      </div>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                      <p class="text-xs text-gray-500">{{ product.sku || '-' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div>
                    <p class="font-semibold text-gray-900 dark:text-white">฿{{ Number(product.price).toLocaleString() }}</p>
                    <p v-if="product.points_price" class="text-xs text-amber-600">{{ product.points_price }} พ้อยท์</p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-900 dark:text-white font-medium">{{ product.stock_quantity }}</span>
                    <span :class="[getStockBadge(product).class, 'px-2 py-0.5 rounded-full text-xs font-medium']">
                      {{ getStockBadge(product).text }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ product.category?.name || '-' }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="product.is_active
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ product.is_active ? 'เปิดขาย' : 'ปิด' }}
                  </span>
                  <span v-if="product.is_featured" class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    แนะนำ
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openEditForm(product)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                      title="แก้ไข"
                    >
                      <Icon icon="fluent:edit-24-regular" class="w-4 h-4 text-gray-500" />
                    </button>
                    <button
                      @click="handleDelete(product)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                      title="ลบ"
                    >
                      <Icon icon="fluent:delete-24-regular" class="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Product Cards (Mobile) -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
          <div v-for="product in products" :key="product.id" class="p-4">
            <div class="flex items-start gap-3">
              <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden shrink-0">
                <img v-if="product.first_image" :src="product.first_image" :alt="product.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <Icon icon="fluent:box-24-regular" class="w-8 h-8 text-gray-400" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                    <p class="text-sm font-semibold text-primary-600 mt-1">฿{{ Number(product.price).toLocaleString() }}</p>
                  </div>
                  <div class="flex items-center gap-1">
                    <button @click="openEditForm(product)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                      <Icon icon="fluent:edit-24-regular" class="w-4 h-4 text-gray-500" />
                    </button>
                    <button @click="handleDelete(product)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                      <Icon icon="fluent:delete-24-regular" class="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </div>
                <div class="flex items-center gap-2 mt-2">
                  <span :class="[getStockBadge(product).class, 'px-2 py-0.5 rounded-full text-xs font-medium']">
                    สต็อก: {{ product.stock_quantity }}
                  </span>
                  <span v-if="!product.is_active" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">ปิด</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="currentPage > 1 && (currentPage--, fetchProducts())"
            :disabled="currentPage <= 1"
            class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ก่อนหน้า
          </button>
          <span class="text-sm text-gray-600 dark:text-gray-400">{{ currentPage }} / {{ meta.last_page }}</span>
          <button
            @click="currentPage < meta.last_page && (currentPage++, fetchProducts())"
            :disabled="currentPage >= meta.last_page"
            class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>

    <!-- Product Form Modal -->
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showForm = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-5">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
              {{ editingProduct ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}
            </h3>
            <button @click="showForm = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อสินค้า *</label>
              <input v-model="form.name" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                placeholder="ชื่อสินค้า" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียด</label>
              <textarea v-model="form.description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                placeholder="รายละเอียดสินค้า..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา (บาท) *</label>
                <input v-model.number="form.price" type="number" step="0.01" min="0" required
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาเปรียบเทียบ</label>
                <input v-model.number="form.compare_price" type="number" step="0.01" min="0"
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนสต็อก *</label>
                <input v-model.number="form.stock_quantity" type="number" min="0" required
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                <input v-model="form.sku" type="text"
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                  placeholder="รหัสสินค้า" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
              <select v-model="form.category_id"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                <option :value="null">ไม่ระบุ</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วิธีชำระ</label>
                <select v-model="form.payment_type"
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                  <option value="both">บาท + พ้อยท์</option>
                  <option value="wallet">บาทเท่านั้น</option>
                  <option value="points">พ้อยท์เท่านั้น</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาพ้อยท์</label>
                <input v-model.number="form.points_price" type="number" min="0"
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                  placeholder="จำนวนพ้อยท์" />
              </div>
            </div>

            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_active" type="checkbox"
                  class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">เปิดขาย</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_featured" type="checkbox"
                  class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">สินค้าแนะนำ</span>
              </label>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="showForm = false"
                class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                ยกเลิก
              </button>
              <button type="submit" :disabled="isSubmitting || !form.name"
                class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors">
                {{ isSubmitting ? 'กำลังบันทึก...' : (editingProduct ? 'อัพเดท' : 'สร้างสินค้า') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
