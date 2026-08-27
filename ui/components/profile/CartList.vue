<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

// Cart should only be visible to own profile usually, but we implement it as requested.
const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface CartItem {
  id: number
  product_id: number
  title: string
  price: number
  quantity: number
  image: string
  seller_name: string
}

// State
const cartItems = ref<CartItem[]>([])
const isLoading = ref(false)

// Computed
const totalAmount = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const totalItems = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
})

// Fetch cart
const fetchCart = async () => {
  if (!props.isOwnProfile) return 
  
  isLoading.value = true
  
  try {
    const response = await api.get('/api/cart').catch(() => ({ success: false })) as any
    
    if (response && response.success) {
      cartItems.value = response.data || []
    } else {
      cartItems.value = []
    }
  } catch (error) {
    console.error('Error fetching cart:', error)
    cartItems.value = []
  } finally {
    isLoading.value = false
  }
}

// Actions
const updateQuantity = async (item: CartItem, delta: number) => {
  const newQty = item.quantity + delta
  if (newQty <= 0) return

  try {
    const response = await api.post('/api/cart/update', {
      item_id: item.id,
      quantity: newQty
    }) as any

    if (response && response.success) {
      item.quantity = newQty
    }
  } catch (error) {
    console.error('Error updating cart item:', error)
  }
}

const removeItem = async (itemId: number) => {
  try {
    const response = await api.post('/api/cart/remove', {
      item_id: itemId
    }) as any

    if (response && response.success) {
      cartItems.value = cartItems.value.filter(item => item.id !== itemId)
    }
  } catch (error) {
    console.error('Error removing cart item:', error)
  }
}

const checkout = () => {
  alert('Proceeding to checkout...')
  // navigateTo('/checkout')
}

onMounted(() => {
  fetchCart()
})
</script>

<template>
  <div class="space-y-6">
    <template v-if="!isOwnProfile">
      <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
        <Icon icon="fluent:lock-closed-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
        <p class="text-gray-400">Private Cart Area</p>
      </BaseCard>
    </template>
    
    <template v-else>
      <!-- Header -->
      <BaseCard class="bg-gray-800 border-gray-700 p-5">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:cart-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            Shopping Cart
            <span class="text-vikinger-cyan font-normal ml-1">({{ totalItems }})</span>
          </h3>
          
          <div v-if="cartItems.length > 0" class="text-right">
             <span class="text-gray-400 text-sm">Total:</span>
             <span class="text-xl font-black text-white ml-2">฿{{ totalAmount.toLocaleString() }}</span>
          </div>
        </div>
      </BaseCard>
      
      <div v-if="cartItems.length > 0" class="flex flex-col lg:flex-row gap-6">
        <!-- Cart Items List -->
        <div class="flex-1 space-y-4">
          <BaseCard 
            v-for="item in cartItems" 
            :key="item.id"
            class="bg-gray-800 border-gray-700 p-4 flex gap-4"
          >
            <!-- Image -->
            <div class="w-24 h-24 bg-gray-700 rounded-lg overflow-hidden shrink-0">
              <img :src="item.image" :alt="item.title" class="w-full h-full object-cover" />
            </div>
            
            <!-- Details -->
            <div class="flex-1 min-w-0 flex flex-col justify-between">
              <div>
                <h4 class="font-bold text-white truncate">{{ item.title }}</h4>
                <p class="text-xs text-gray-400">Seller: {{ item.seller_name }}</p>
              </div>
              
              <div class="flex items-end justify-between mt-2">
                <div class="font-black text-vikinger-cyan">฿{{ item.price.toLocaleString() }}</div>
                
                <!-- Quantity Controls -->
                <div class="flex items-center bg-gray-900 rounded-lg border border-gray-700">
                  <button 
                    @click="updateQuantity(item, -1)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 hover:text-white text-gray-400 transition-colors"
                    :disabled="item.quantity <= 1"
                  >
                    <Icon icon="fluent:subtract-16-regular" class="w-4 h-4" />
                  </button>
                  <span class="w-8 text-center text-sm font-bold text-white">{{ item.quantity }}</span>
                  <button 
                    @click="updateQuantity(item, 1)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 hover:text-white text-gray-400 transition-colors"
                  >
                    <Icon icon="fluent:add-16-regular" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Remove Button -->
            <button 
              @click="removeItem(item.id)"
              class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg self-start transition-all"
              title="Remove"
            >
              <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            </button>
          </BaseCard>
        </div>
        
        <!-- Summary Sidebar -->
        <div class="lg:w-80">
          <BaseCard class="bg-gray-800 border-gray-700 p-5 sticky top-24">
            <h4 class="font-bold text-white mb-4">Order Summary</h4>
            
            <div class="space-y-3 border-b border-gray-700 pb-4 mb-4">
              <div class="flex justify-between text-sm text-gray-400">
                <span>Subtotal</span>
                <span class="text-white">฿{{ totalAmount.toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-sm text-gray-400">
                <span>Tax</span>
                <span class="text-white">฿0</span>
              </div>
            </div>
            
            <div class="flex justify-between text-lg font-bold text-white mb-6">
              <span>Total</span>
              <span class="text-vikinger-cyan">฿{{ totalAmount.toLocaleString() }}</span>
            </div>
            
            <button 
              @click="checkout"
              class="w-full py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold shadow-lg shadow-vikinger-purple/20 hover:shadow-vikinger-cyan/40 hover:scale-[1.02] transition-all"
            >
              Checkout Now
            </button>
          </BaseCard>
        </div>
      </div>
      
      <!-- Empty Cart -->
      <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-16">
        <div class="w-20 h-20 mx-auto bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
          <Icon icon="fluent:cart-24-regular" class="w-10 h-10 text-gray-500" />
        </div>
        <h3 class="text-lg font-bold text-white mb-2">ตะกร้าสินค้าว่างเปล่า</h3>
        <p class="text-gray-400 mb-6">เลือกซื้อสินค้าจาก Marketplace หรือหลักสูตรเรียนรู้</p>
        
        <NuxtLink 
          to="/marketplace"
          class="inline-flex items-center gap-2 px-6 py-3 bg-gray-700 text-white rounded-xl hover:bg-gray-600 transition-colors font-bold"
        >
          ไปที่ Marketplace
        </NuxtLink>
      </BaseCard>
    </template>
  </div>
</template>
