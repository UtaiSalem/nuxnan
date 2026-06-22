<template>
  <div class="flex flex-col gap-3">
    <div
      v-for="i in 3"
      :key="i"
      class="flex items-center gap-3"
    >
      <!-- Date chip skeleton -->
      <div class="w-12 h-11 flex-shrink-0 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-vikinger-dark-100 flex flex-col justify-center items-center gap-1">
        <div class="w-6 h-4 bg-gray-200 dark:bg-gray-700 rounded" />
        <div class="w-8 h-2 bg-gray-200 dark:bg-gray-700 rounded" />
      </div>

      <!-- Title + badge skeleton -->
      <div class="flex-1 min-w-0 flex flex-col gap-1.5">
        <CommonSkeletonBox width="140px" height="14px" />
        <CommonSkeletonBox width="60px" height="12px" />
      </div>
    </div>
  </div>
</template>
