<template>
  <article class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
    <div class="flex gap-3 mb-3">
      <CommonSkeletonBox width="40px" height="40px" rounded="full" />
      <div class="flex-1 flex flex-col gap-1.5">
        <CommonSkeletonBox width="120px" height="14px" />
        <CommonSkeletonBox width="80px" height="11px" />
      </div>
    </div>
    <div class="space-y-2">
      <CommonSkeletonBox width="100%" height="14px" />
      <CommonSkeletonBox width="92%" height="14px" />
      <CommonSkeletonBox width="78%" height="14px" />
    </div>
    <div class="flex gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
      <CommonSkeletonBox width="40px" height="20px" />
      <CommonSkeletonBox width="40px" height="20px" />
      <CommonSkeletonBox width="40px" height="20px" />
    </div>
  </article>
</template>
