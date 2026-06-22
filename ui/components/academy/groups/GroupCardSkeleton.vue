<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
    <!-- Gradient header strip replacement -->
    <div class="h-14 bg-gray-100 dark:bg-gray-800 animate-pulse motion-reduce:animate-none"></div>

    <!-- Body -->
    <div class="p-4 flex flex-col gap-3 flex-1">
      <div class="flex items-start gap-3">
        <!-- Icon medallion placeholder -->
        <div class="w-12 h-12 -mt-8 rounded-lg bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-vikinger-dark-200 shadow-md flex-shrink-0 animate-pulse motion-reduce:animate-none"></div>

        <!-- Title + type badge placeholder -->
        <div class="flex-1 pt-0 min-w-0 flex flex-col gap-2">
          <div class="flex items-center gap-2 flex-wrap">
            <CommonSkeletonBox width="100px" height="16px" />
            <CommonSkeletonBox width="50px" height="14px" rounded="full" />
          </div>
          <CommonSkeletonBox width="140px" height="12px" />
        </div>
      </div>

      <!-- Stats + actions placeholder -->
      <div class="mt-6 flex items-center justify-between text-sm">
        <div class="flex items-center gap-3">
          <CommonSkeletonBox width="60px" height="12px" />
          <CommonSkeletonBox width="60px" height="12px" />
        </div>
        <CommonSkeletonBox width="70px" height="14px" />
      </div>
    </div>
  </div>
</template>
