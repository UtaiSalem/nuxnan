<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface Product {
  id: number
  title: string
  slug: string
  price: number
  currency: string
  image: string
  category: string
  status: 'active' | 'sold' | 'inactive'
  likes_count: number
  created_at: string
}

// State
const products = ref<Product[]>([])
const isLoading = ref(false)
const selectedFilter = ref<'selling' | 'sold'>('selling')

// Computed
const filteredProducts = computed(() => {
  if (selectedFilter.value === 'selling') {
    return products.value.filter(p => p.status === 'active')
  }
  return products.value.filter(p => p.status === 'sold')
})

// Fetch products
const fetchProducts = async () => {
  isLoading.value = true
  
  try {
    const endpoint = '/api/posts'
    const params = {
      post_type: 'PRODUCT',
      user_id: props.userId,
      sort: selectedFilter.value === 'sold' ? 'sold' : 'selling'
    }
    
    const response = await api.get(endpoint, { params }).catch(() => ({ success: false })) as any
    
    if (response && response.data) {
      const rawPosts = Array.isArray(response.data) ? response.data : (response.data.data || [])
      
      // Map Post resource to Product interface
      products.value = rawPosts.map((post: any) => ({
        id: post.id,
        title: post.content ? (post.content.length > 50 ? post.content.substring(0, 50) + '...' : post.content) : 'No Title',
        slug: `product-${post.id}`, // Placeholder slug
        price: post.price || 0,
        currency: 'THB',
        image: post.images && post.images.length > 0 ? post.images[0].url : 'https://picsum.photos/400/400?random=' + post.id,
        category: 'General', // Placeholder category
        status: post.status === 'sold' ? 'sold' : 'active',
        likes_count: post.likes_count || 0,
        created_at: post.created_at || new Date().toISOString()
      }))

    } else {
      products.value = []
    }
  } catch (error) {
    console.error('Error fetching products:', error)
    products.value = []
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockProducts = (): Product[] => [
  {
    id: 1,
    title: 'Gaming Keyboard RGB',
    slug: 'gaming-keyboard-rgb',
    price: 1500,
    currency: 'THB',
    image: 'https://picsum.photos/400/400?random=30',
    category: 'Electronics',
    status: 'active',
    likes_count: 24,
    created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 2,
    title: 'Vue.js Course E-Book',
    slug: 'vuejs-ebook',
    price: 499,
    currency: 'THB',
    image: 'https://picsum.photos/400/400?random=31',
    category: 'Digital',
    status: 'active',
    likes_count: 156,
    created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 3,
    title: 'Used Monitor 24"',
    slug: 'monitor-24',
    price: 2500,
    currency: 'THB',
    image: 'https://picsum.photos/400/400?random=32',
    category: 'Electronics',
    status: 'sold',
    likes_count: 12,
    created_at: new Date(Date.now() - 15 * 24 * 60 * 60 * 1000).toISOString()
  }
]

// Navigate
const goToProduct = (product: Product) => {
  navigateTo(`/marketplace/product/${product.slug}`)
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:building-shop-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            Marketplace
            <span class="text-vikinger-cyan font-normal ml-1">({{ products.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">สินค้าที่ลงขาย</p>
        </div>
        
        <!-- Create Product Button (Own Profile) -->
        <NuxtLink
          v-if="isOwnProfile"
          to="/marketplace/create"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          ลงขายสินค้า
        </NuxtLink>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button
          v-for="filter in [
            { key: 'selling', label: 'กำลังขาย', icon: 'fluent:tag-24-regular' },
            { key: 'sold', label: 'ขายแล้ว', icon: 'fluent:checkmark-circle-24-regular' },
          ]"
          :key="filter.key"
          @click="selectedFilter = filter.key as typeof selectedFilter"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
            selectedFilter === filter.key
              ? 'bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30'
              : 'text-gray-400 hover:text-white hover:bg-gray-700'
          ]"
        >
          <Icon :icon="filter.icon" class="w-4 h-4" />
          {{ filter.label }}
        </button>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
          <div class="aspect-square bg-gray-700"></div>
          <div class="p-3 space-y-2">
            <div class="h-4 bg-gray-700 rounded w-full"></div>
            <div class="h-3 bg-gray-700 rounded w-1/2"></div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Products Grid -->
    <div v-else-if="filteredProducts.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <BaseCard 
        v-for="product in filteredProducts" 
        :key="product.id"
        class="bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group hover:-translate-y-1"
        @click="goToProduct(product)"
      >
        <!-- Image -->
        <div class="aspect-square relative overflow-hidden bg-gray-700">
          <img 
            :src="product.image" 
            :alt="product.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          
          <button class="absolute top-2 right-2 p-1.5 bg-black/50 hover:bg-red-500 text-white rounded-full transition-colors opacity-0 group-hover:opacity-100">
            <Icon icon="fluent:heart-24-regular" class="w-4 h-4" />
          </button>
          
          <div v-if="product.status === 'sold'" class="absolute inset-0 bg-black/60 flex items-center justify-center">
            <span class="px-3 py-1 bg-red-500 text-white font-bold rounded-lg transform -rotate-12 border-2 border-white">SOLD OUT</span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-3">
          <h4 class="font-bold text-white truncate text-sm mb-1 group-hover:text-vikinger-cyan">
            {{ product.title }}
          </h4>
          
          <div class="flex items-center justify-between mt-2">
            <span class="text-vikinger-cyan font-black">
              ฿{{ product.price.toLocaleString() }}
            </span>
            <span class="text-[10px] text-gray-500 bg-gray-700 px-1.5 py-0.5 rounded">
              {{ product.category }}
            </span>
          </div>
          
          <div class="flex items-center gap-1 mt-2 text-xs text-gray-500">
            <Icon icon="fluent:heart-16-filled" class="w-3 h-3 text-red-500" />
            {{ product.likes_count }}
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="col-span-full bg-gray-800 border-gray-700 text-center py-16">
      <div class="w-20 h-20 mx-auto bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
        <Icon icon="fluent:shopping-bag-24-regular" class="w-10 h-10 text-gray-500" />
      </div>
      <h3 class="text-lg font-bold text-white mb-2">ไม่มีสินค้า</h3>
      <p class="text-gray-400 max-w-sm mx-auto">
        {{ isOwnProfile ? 'นำสินค้าของคุณมาลงขายเพื่อสร้างรายได้' : 'ผู้ใช้นี้ยังไม่มีสินค้าลงขาย' }}
      </p>
      
      <NuxtLink 
        v-if="isOwnProfile" 
        to="/marketplace/create"
        class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:shadow-lg hover:shadow-vikinger-purple/20 transition-all font-bold"
      >
        <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
        ลงขายสินค้า
      </NuxtLink>
    </BaseCard>
  </div>
</template>
