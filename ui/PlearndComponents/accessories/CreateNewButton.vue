<script setup>
import { Icon } from '@iconify/vue';
</script>

<template>
    <div>
    <button
      class="inline-flex items-center justify-center py-1.5 gap-1 px-3 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
        <span class="order-2">
            <slot></slot>
        </span>
        <span class="relative only:-mx-3">
          <!-- <Icon icon="heroicons-outline:plus-circle" class="w-6 h-6"/> -->
          <Icon icon="heroicons:document-plus" class="w-6 h-6"/>
        </span>
      </button>
    </div>
</template>

