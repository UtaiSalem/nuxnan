<script setup lang="ts">
/**
 * Academy Public Store Page
 * หน้าร้านค้าสำหรับสมาชิก/นักเรียนเข้ามาซื้อสินค้า
 */
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: false })

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const api = useApi()
const { getStore, getProducts, getCategories, createOrder, getMyOrders } = useSchoolStore()

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const store = ref<any>(null)
const products = ref<any[]>([])
const categories = ref<any[]>([])
const isLoading = ref(true)
const meta = ref<any>({})

// Filters
const search = ref('')
const selectedCategory = ref<number | null>(null)
const currentPage = ref(1)
const activeTab = ref<'products' | 'cart' | 'orders'>('products')

// Cart
const cart = ref<Map<number, { product: any; quantity: number }>>(new Map())
const isOrdering = ref(false)
const orderSuccess = ref(false)
const paymentMethod = ref<'wallet' | 'points'>('wallet')

// My Orders
const myOrders = ref<any[]>([])
const isLoadingOrders = ref(false)

const cartItems = computed(() => Array.from(cart.value.values()))
const cartTotal = computed(() => cartItems.value.reduce((sum, item) => sum + (item.product.price * item.quantity), 0))
const cartPointsTotal = computed(() => cartItems.value.reduce((sum, item) => sum + ((item.product.points_price || 0) * item.quantity), 0))
const cartItemCount = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0))

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchStore()
    }
  } catch (err) { console.error(err) }
  finally { isLoading.value = false }
})

const fetchStore = async () => {
  if (!academyId.value) return
  try {
    const [storeRes, catRes]: any[] = await Promise.all([
      getStore(academyId.value),
      getCategories(academyId.value),
    ])
    if (storeRes.success) store.value = storeRes.data
    categories.value = catRes || []
    await fetchProducts()
  } catch (err) { console.error(err) }
}

const fetchProducts = async () => {
  if (!academyId.value) return
  try {
    const params: any = { page: currentPage.value, per_page: 20 }
    if (search.value) params.search = search.value
    if (selectedCategory.value) params.category_id = selectedCategory.value

    const response: any = await getProducts(academyId.value, params)
    if (response.success) {
      products.value = response.data || []
      meta.value = response.meta || {}
    }
  } catch (err) { console.error(err) }
}

const fetchMyOrders = async () => {
  if (!academyId.value) return
  isLoadingOrders.value = true
  try {
    const response: any = await getMyOrders(academyId.value)
    if (response.success) {
      myOrders.value = response.data || []
    }
  } catch (err) { console.error(err) }
  finally { isLoadingOrders.value = false }
}

watch([search, selectedCategory], () => {
  currentPage.value = 1
  fetchProducts()
})

watch(activeTab, (tab) => {
  if (tab === 'orders') fetchMyOrders()
})

const addToCart = (product: any) => {
  const existing = cart.value.get(product.id)
  if (existing) {
    if (existing.quantity < product.stock_quantity) {
      existing.quantity++
    }
  } else {
    cart.value.set(product.id, { product, quantity: 1 })
  }
  cart.value = new Map(cart.value) // trigger reactivity
}

const updateQuantity = (productId: number, delta: number) => {
  const item = cart.value.get(productId)
  if (!item) return
  const newQty = item.quantity + delta
  if (newQty <= 0) {
    cart.value.delete(productId)
  } else if (newQty <= item.product.stock_quantity) {
    item.quantity = newQty
  }
  cart.value = new Map(cart.value)
}

const removeFromCart = (productId: number) => {
  cart.value.delete(productId)
  cart.value = new Map(cart.value)
}

const handleOrder = async () => {
  if (!academyId.value || isOrdering.value || !cartItems.value.length) return
  isOrdering.value = true
  orderSuccess.value = false
  try {
    const items = cartItems.value.map(item => ({
      product_id: item.product.id,
      quantity: item.quantity,
    }))
    const response: any = await createOrder(academyId.value, {
      items,
      payment_method: paymentMethod.value,
    })
    if (response.success) {
      cart.value = new Map()
      orderSuccess.value = true
      setTimeout(() => {
        activeTab.value = 'orders'
        orderSuccess.value = false
      }, 2000)
    }
  } catch (err) { console.error(err) }
  finally { isOrdering.value = false }
}

const statusColors: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
  confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  processing: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
  ready: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
  completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="!store || !store.is_active" class="flex items-center justify-center py-20">
      <div class="text-center">
        <Icon icon="fluent:store-microsoft-24-regular" class="w-20 h-20 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ร้านค้ายังไม่เปิดให้บริการ</h2>
        <p class="text-gray-500">โรงเรียนยังไม่ได้เปิดร้านค้าในขณะนี้</p>
        <NuxtLink :to="`/academies/${academyName}`"
          class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">
          ← กลับหน้าโรงเรียน
        </NuxtLink>
      </div>
    </div>

    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
      <!-- Back -->
      <NuxtLink :to="`/academies/${academyName}`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 transition-colors">
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับ {{ academy?.name }}</span>
      </NuxtLink>

      <!-- Store Header -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center shrink-0">
            <Icon icon="fluent:store-microsoft-24-filled" class="w-8 h-8 text-primary-600 dark:text-primary-400" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ store.name }}</h1>
            <p v-if="store.description" class="text-gray-600 dark:text-gray-400 mt-1">{{ store.description }}</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <button
            @click="activeTab = 'products'"
            :class="activeTab === 'products' ? 'text-primary-600 border-primary-600 bg-primary-50 dark:bg-primary-900/10' : 'text-gray-500 border-transparent hover:text-gray-700'"
            class="flex-1 sm:flex-initial px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2"
          >
            <Icon icon="fluent:box-24-regular" class="w-5 h-5" />
            สินค้า
          </button>
          <button
            @click="activeTab = 'cart'"
            :class="activeTab === 'cart' ? 'text-primary-600 border-primary-600 bg-primary-50 dark:bg-primary-900/10' : 'text-gray-500 border-transparent hover:text-gray-700'"
            class="flex-1 sm:flex-initial px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2 relative"
          >
            <Icon icon="fluent:cart-24-regular" class="w-5 h-5" />
            ตะกร้า
            <span v-if="cartItemCount > 0" class="absolute -top-1 -right-1 sm:static sm:ml-1 bg-primary-600 text-white text-xs rounded-full h-5 min-w-5 flex items-center justify-center px-1.5">
              {{ cartItemCount }}
            </span>
          </button>
          <button
            @click="activeTab = 'orders'"
            :class="activeTab === 'orders' ? 'text-primary-600 border-primary-600 bg-primary-50 dark:bg-primary-900/10' : 'text-gray-500 border-transparent hover:text-gray-700'"
            class="flex-1 sm:flex-initial px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2"
          >
            <Icon icon="fluent:receipt-24-regular" class="w-5 h-5" />
            คำสั่งซื้อ
          </button>
        </div>

        <!-- Products Tab -->
        <div v-if="activeTab === 'products'" class="p-4 space-y-4">
          <!-- Search & Filters -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input v-model="search" type="text" placeholder="ค้นหาสินค้า..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
            </div>
            <select v-model="selectedCategory"
              class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
              <option :value="null">ทุกหมวดหมู่</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>

          <!-- Products Grid -->
          <div v-if="!products.length" class="text-center py-12">
            <Icon icon="fluent:box-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
            <p class="text-gray-500">ไม่พบสินค้า</p>
          </div>

          <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="product in products"
              :key="product.id"
              class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden group hover:shadow-md transition-shadow"
            >
              <!-- Product Image -->
              <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <img v-if="product.first_image" :src="product.first_image" :alt="product.name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <Icon icon="fluent:box-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                </div>

                <!-- Featured Badge -->
                <div v-if="product.is_featured" class="absolute top-2 left-2">
                  <span class="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full font-medium">แนะนำ</span>
                </div>
              </div>

              <!-- Product Info -->
              <div class="p-3">
                <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2 mb-2">{{ product.name }}</h4>

                <div class="flex items-center gap-2 mb-3">
                  <span class="text-lg font-bold text-primary-600 dark:text-primary-400">฿{{ Number(product.price).toLocaleString() }}</span>
                  <span v-if="product.compare_price && product.compare_price > product.price"
                    class="text-xs text-gray-400 line-through">
                    ฿{{ Number(product.compare_price).toLocaleString() }}
                  </span>
                </div>

                <div v-if="product.points_price" class="text-xs text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-1">
                  <Icon icon="fluent:star-24-filled" class="w-3.5 h-3.5" />
                  {{ product.points_price }} พ้อยท์
                </div>

                <button
                  v-if="product.stock_quantity > 0"
                  @click="addToCart(product)"
                  class="min-h-[44px] sm:min-h-0 w-full py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center justify-center gap-1"
                >
                  <Icon icon="fluent:cart-24-regular" class="w-4 h-4" />
                  เพิ่มลงตะกร้า
                </button>
                <div v-else class="w-full py-2 text-center text-sm text-red-500 font-medium">สินค้าหมด</div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
            <button @click="currentPage > 1 && (currentPage--, fetchProducts())" :disabled="currentPage <= 1"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50">ก่อนหน้า</button>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ currentPage }} / {{ meta.last_page }}</span>
            <button @click="currentPage < meta.last_page && (currentPage++, fetchProducts())" :disabled="currentPage >= meta.last_page"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50">ถัดไป</button>
          </div>
        </div>

        <!-- Cart Tab -->
        <div v-if="activeTab === 'cart'" class="p-4">
          <!-- Success Message -->
          <div v-if="orderSuccess" class="text-center py-8">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-10 h-10 text-green-600" />
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">สั่งซื้อสำเร็จ!</h3>
            <p class="text-gray-500 mt-2">กำลังไปยังหน้าคำสั่งซื้อ...</p>
          </div>

          <!-- Empty Cart -->
          <div v-else-if="!cartItems.length" class="text-center py-12">
            <Icon icon="fluent:cart-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
            <p class="text-gray-500 mb-4">ตะกร้าว่าง</p>
            <button @click="activeTab = 'products'" class="text-primary-600 hover:text-primary-700 font-medium">เลือกสินค้า →</button>
          </div>

          <!-- Cart Items -->
          <div v-else class="space-y-4">
            <div v-for="item in cartItems" :key="item.product.id"
              class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
              <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden shrink-0">
                <img v-if="item.product.first_image" :src="item.product.first_image" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <Icon icon="fluent:box-24-regular" class="w-8 h-8 text-gray-400" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ item.product.name }}</p>
                <p class="text-sm text-primary-600 font-semibold">฿{{ Number(item.product.price).toLocaleString() }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="updateQuantity(item.product.id, -1)"
                  class="w-8 h-8 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center">
                  <Icon icon="fluent:subtract-24-regular" class="w-4 h-4" />
                </button>
                <span class="w-8 text-center text-sm font-medium text-gray-900 dark:text-white">{{ item.quantity }}</span>
                <button @click="updateQuantity(item.product.id, 1)"
                  class="w-8 h-8 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center">
                  <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
                </button>
                <button @click="removeFromCart(item.product.id)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg ml-1">
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4 text-red-500" />
                </button>
              </div>
            </div>

            <!-- Cart Summary & Checkout -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">ยอดรวม</span>
                <span class="text-xl font-bold text-gray-900 dark:text-white">฿{{ cartTotal.toLocaleString() }}</span>
              </div>
              <div v-if="cartPointsTotal > 0" class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">หรือ</span>
                <span class="text-xl font-bold text-amber-600">{{ cartPointsTotal.toLocaleString() }} พ้อยท์</span>
              </div>

              <!-- Payment Method -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วิธีชำระเงิน</label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    v-if="store.allow_wallet_payment"
                    @click="paymentMethod = 'wallet'"
                    :class="paymentMethod === 'wallet' ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20 border-primary-300' : 'border-gray-300 dark:border-gray-600'"
                    class="p-3 border rounded-xl text-center transition-all"
                  >
                    <Icon icon="fluent:wallet-24-filled" class="w-6 h-6 mx-auto text-green-600 mb-1" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white block">กระเป๋าเงิน</span>
                  </button>
                  <button
                    v-if="store.allow_points_payment"
                    @click="paymentMethod = 'points'"
                    :class="paymentMethod === 'points' ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20 border-primary-300' : 'border-gray-300 dark:border-gray-600'"
                    class="p-3 border rounded-xl text-center transition-all"
                  >
                    <Icon icon="fluent:star-24-filled" class="w-6 h-6 mx-auto text-amber-500 mb-1" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white block">พ้อยท์</span>
                  </button>
                </div>
              </div>

              <button @click="handleOrder" :disabled="isOrdering || !cartItems.length"
                class="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2">
                <div v-if="isOrdering" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <Icon v-else icon="fluent:checkmark-circle-24-regular" class="w-5 h-5" />
                {{ isOrdering ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Orders Tab -->
        <div v-if="activeTab === 'orders'" class="p-4">
          <div v-if="isLoadingOrders" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
          </div>

          <div v-else-if="!myOrders.length" class="text-center py-12">
            <Icon icon="fluent:receipt-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
            <p class="text-gray-500">ยังไม่มีคำสั่งซื้อ</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="order in myOrders" :key="order.id"
              class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-xs text-gray-500">{{ order.order_number }}</span>
                  <span class="text-xs text-gray-400 ml-2">{{ formatDate(order.created_at) }}</span>
                </div>
                <span :class="[statusColors[order.status] || 'bg-gray-100 text-gray-700', 'px-3 py-1 rounded-full text-xs font-medium']">
                  {{ order.status_label }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <div v-for="item in order.items" :key="item.id"
                  class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <span class="text-xs bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded">x{{ item.quantity }}</span>
                  {{ item.product_name }}
                </div>
              </div>
              <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500">
                  {{ order.payment_method === 'points' ? 'พ้อยท์' : 'กระเป๋าเงิน' }}
                </span>
                <span class="font-bold text-gray-900 dark:text-white">
                  {{ order.payment_method === 'points' ? `${order.points_spent} พ้อยท์` : `฿${Number(order.total).toLocaleString()}` }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
