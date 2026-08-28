<script setup lang="ts">
interface Participant {
  user_id: number; 
  name: string; 
  avatar?: string;
  progress: number; 
  wpm: number; 
  status: string;
}

defineProps<{ 
  participants: Participant[]; 
  myUserId?: number; 
}>()
</script>

<template>
<div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-6 border border-slate-200 dark:border-slate-800 space-y-4 shadow-sm">
  <div class="flex items-center justify-between mb-2">
    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Race Progress</h3>
    <div class="flex gap-2">
      <span class="w-3 h-3 rounded-full bg-primary-500"></span>
      <span class="text-[10px] font-bold text-slate-400 uppercase">You</span>
    </div>
  </div>
  
  <div class="space-y-4">
    <div v-for="p in participants.filter(x => x.status !== 'left')"
      :key="p.user_id" class="space-y-1">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex-shrink-0 ring-2 ring-white dark:ring-slate-800">
            <img v-if="p.avatar" :src="p.avatar" class="w-full h-full object-cover" />
            <span v-else class="text-[10px] font-bold flex items-center justify-center h-full text-slate-500">{{ p.name?.charAt(0) }}</span>
          </div>
          <span class="text-sm font-bold truncate max-w-[120px]" :class="p.user_id === myUserId ? 'text-primary-500' : 'text-slate-700 dark:text-slate-300'">
            {{ p.name }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="p.status === 'finished'" class="text-[10px] font-black bg-green-100 text-green-600 px-2 py-0.5 rounded-full">FINISHED</span>
          <span v-else class="text-xs font-black text-slate-400">{{ p.wpm }} WPM</span>
        </div>
      </div>
      <div class="relative h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div
          class="absolute top-0 left-0 h-full rounded-full transition-all duration-500 ease-out"
          :class="p.user_id === myUserId ? 'bg-primary-500' : p.status === 'finished' ? 'bg-green-500' : 'bg-slate-400 dark:bg-slate-600'"
          :style="{ width: p.progress + '%' }"
        >
          <div v-if="p.progress > 0" class="absolute right-0 top-0 h-full w-4 bg-white/20 blur-[2px]"></div>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<style scoped>
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 500ms;
}
</style>
