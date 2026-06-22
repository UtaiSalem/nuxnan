<template>
  <div class="grid grid-cols-2 gap-3">
    <div
      v-for="i in 4"
      :key="i"
      class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 flex flex-col gap-2"
    >
      <div class="w-9 h-9 rounded-lg bg-gray-200 dark:bg-gray-700" />
      <div class="flex flex-col gap-1.5 mt-1">
        <CommonSkeletonBox width="50px" height="18px" />
        <CommonSkeletonBox width="80px" height="11px" />
      </div>
    </div>
  </div>
</template>
