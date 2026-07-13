<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  groups: { type: Array as any, required: true },
  isProcessing: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const selectedGroupId = ref<number | null>(null)

watch(() => props.show, (val) => {
  if (val && props.groups.length > 0) {
    selectedGroupId.value = props.groups[0].id
  }
})

function confirm() {
  if (selectedGroupId.value) {
    emit('confirm', selectedGroupId.value)
  }
}
</script>

<template>
  <ClientOnly>
    <Teleport to="body">
      <div
        v-if="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300"></div>
        <div class="relative w-full max-w-md max-h-[90vh] rounded-3xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col animate-in zoom-in-95 duration-200">
          <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">เลือกกลุ่มเรียน</h3>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-1">เลือกกลุ่มที่ต้องการสมัครเข้าร่วม</p>
              </div>
              <button
                @click="$emit('close')"
                class="p-2 rounded-xl text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
              >
                <Icon icon="heroicons:x-mark" class="w-6 h-6" />
              </button>
            </div>
          </div>

          <div class="p-4 sm:p-6 space-y-3 max-h-[60vh] overflow-y-auto no-scrollbar">
            <label
              v-for="group in groups"
              :key="group.id"
              class="flex items-center gap-4 p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200"
              :class="selectedGroupId === group.id 
                ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' 
                : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-800'"
            >
              <input
                v-model="selectedGroupId"
                type="radio"
                :value="group.id"
                class="w-6 h-6 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
              >
              <div class="flex-1 min-w-0">
                <div class="font-black text-gray-900 dark:text-white text-base truncate">{{ group.name || `กลุ่ม ${group.id}` }}</div>
                <div class="text-xs font-bold text-gray-500 dark:text-gray-400">
                  {{ group.members_count || 0 }} สมาชิก
                </div>
              </div>
            </label>
          </div>

          <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row gap-3">
            <button
              @click="$emit('close')"
              class="flex-1 px-4 sm:px-6 py-3 sm:py-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-all min-h-12"
            >
              ยกเลิก
            </button>
            <button
              @click="confirm"
              :disabled="!selectedGroupId || isProcessing"
              class="flex-[2] px-4 sm:px-6 py-3 sm:py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-black hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 min-h-12"
            >
              <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
              <span>สมัครเข้าเรียนกลุ่มนี้</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </ClientOnly>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
