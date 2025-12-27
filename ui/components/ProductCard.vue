<template>
  <NuxtLink
    :to="`/product/${product.id}`"
    class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all hover:-translate-y-1 duration-200"
  >
    <!-- Product Image -->
    <div class="relative aspect-square">
      <img :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
      <span
        v-if="product.condition"
        class="absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full"
        :class="product.condition === 'New' ? 'bg-green-500 text-white' : 'bg-amber-500 text-white'"
      >
        {{ product.condition }}
      </span>
    </div>

    <!-- Product Info -->
    <div class="p-3">
      <h3 class="font-semibold text-secondary-900 mb-1 line-clamp-2">
        {{ product.name }}
      </h3>

      <p class="text-sm text-secondary-600 mb-2">{{ product.seller }}</p>

      <div class="flex items-center justify-between">
        <span class="text-lg font-bold text-primary-600"> ${{ product.price.toFixed(2) }} </span>

        <button
          @click.prevent="addToCart"
          class="p-2 hover:bg-primary-50 rounded-full transition-colors"
        >
          <i class="pi pi-shopping-cart text-xl text-primary-600"></i>
        </button>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
interface Props {
  product: {
    id: number
    name: string
    price: number
    image: string
    seller: string
    condition: string
  }
}

defineProps<Props>()

const addToCart = () => {
  console.log('Add to cart')
}
</script>
