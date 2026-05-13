<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 flex flex-col">
    <!-- Cover -->
    <div class="h-40 skeleton dark:skeleton-dark shrink-0"></div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-1">
      <!-- Instructor row -->
      <div class="flex items-center gap-2 mb-2">
        <div class="w-5 h-5 rounded-full skeleton dark:skeleton-dark shrink-0"></div>
        <div class="w-24 h-3 rounded skeleton dark:skeleton-dark"></div>
      </div>

      <!-- Title -->
      <div class="mb-3 space-y-1.5">
        <div class="w-full h-4 rounded skeleton dark:skeleton-dark"></div>
        <div class="w-3/4 h-4 rounded skeleton dark:skeleton-dark"></div>
      </div>

      <!-- Stats -->
      <div class="flex items-center gap-3 mb-3">
        <div class="w-20 h-3 rounded skeleton dark:skeleton-dark"></div>
        <div class="w-28 h-3 rounded skeleton dark:skeleton-dark"></div>
      </div>

      <!-- Action section: two-column split -->
      <div class="mt-auto border-t border-slate-100 dark:border-slate-700 pt-3 grid grid-cols-2 gap-2">
        <div class="flex flex-col gap-1.5">
          <div class="w-12 h-3 rounded skeleton dark:skeleton-dark"></div>
          <div class="w-full h-5 rounded skeleton dark:skeleton-dark"></div>
          <div class="w-full h-7 rounded-lg skeleton dark:skeleton-dark"></div>
        </div>
        <div class="flex flex-col gap-1.5">
          <div class="w-12 h-3 rounded skeleton dark:skeleton-dark"></div>
          <div class="w-full h-5 rounded skeleton dark:skeleton-dark"></div>
          <div class="w-full h-7 rounded-lg skeleton dark:skeleton-dark"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.skeleton {
  background: linear-gradient(90deg, #e5e7eb 25%, #f9fafb 50%, #e5e7eb 75%);
  background-size: 400px 100%;
  animation: shimmer 1.4s ease infinite;
}
.skeleton-dark {
  background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
  background-size: 400px 100%;
  animation: shimmer 1.4s ease infinite;
}
@keyframes shimmer {
  0%   { background-position: 400px 0; }
  100% { background-position: -400px 0; }
}
</style>
