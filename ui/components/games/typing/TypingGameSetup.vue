<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import type { GameMode, Lang, Difficulty } from '~/stores/typing'
import {
  LESSON_INFO_BY_LANG,
  LESSON_ORDER,
  type KeyLesson,
} from '~/composables/useKeyTraining'

// Shared pre-game setup screen shown on the play page for EVERY mode,
// so the setup → play flow is identical across modes.

const props = defineProps<{ mode: GameMode }>()
const emit = defineEmits<{ (e: 'start'): void }>()

const store = useTypingStore()

const isKeyTraining = computed(() => props.mode === 'key_training')

const MODE_META: Record<string, { name: string; icon: string; desc: string; color: string }> = {
  key_training:    { name: 'Key Training',   icon: 'heroicons:cpu-chip',                        desc: 'ฝึกจำตำแหน่งคีย์ทีละตัว สร้าง muscle memory', color: 'bg-green-600' },
  word_typing:     { name: 'Word Typing',    icon: 'heroicons:document-text',                   desc: 'ฝึกพิมพ์ทีละคำ ในจังหวะของตัวเอง',          color: 'bg-primary-500' },
  time_attack:     { name: 'Time Attack',    icon: 'heroicons:clock',                           desc: 'พิมพ์ให้ได้มากที่สุดใน 60 วินาที',           color: 'bg-orange-500' },
  sentence_typing: { name: 'Sentences',      icon: 'heroicons:chat-bubble-bottom-center-text',  desc: 'ฝึกประโยคยาว เครื่องหมายวรรคตอน',          color: 'bg-blue-500' },
  monster_battle:  { name: 'Monster Battle', icon: 'heroicons:fire',                            desc: 'โจมตีมอนสเตอร์ด้วยการพิมพ์!',               color: 'bg-red-500' },
  falling_words:   { name: 'Falling Words',  icon: 'heroicons:arrow-down-circle',               desc: 'จับคำที่ตกลงมาก่อนถึงพื้น',                color: 'bg-purple-500' },
  letter_runner:   { name: 'Letter Runner',  icon: 'heroicons:arrow-right-circle',              desc: 'วิ่งเก็บเหรียญ พิมพ์ตัวอักษรทำลายอุปสรรค!',  color: 'bg-cyan-500' },
}
const meta = computed(() => MODE_META[props.mode] ?? MODE_META.word_typing)

const languages = [
  { id: 'en', name: 'English', flag: '🇬🇧' },
  { id: 'th', name: 'Thai',    flag: '🇹🇭' },
  { id: 'ar', name: 'Arabic',  flag: '🇸🇦' },
]

const difficulties = [
  { id: 'beginner', name: 'Beginner', desc: 'คำสั้น ง่ายมาก' },
  { id: 'easy',     name: 'Easy',     desc: 'คำพื้นฐาน'      },
  { id: 'normal',   name: 'Normal',   desc: 'มีประโยค'       },
  { id: 'hard',     name: 'Hard',     desc: 'คำยาว สัญลักษณ์' },
  { id: 'expert',   name: 'Expert',   desc: 'ทุกภาษา จับเวลา' },
]

const keyLessons = computed(() =>
  LESSON_ORDER.map(id => ({ id, ...LESSON_INFO_BY_LANG[store.selectedLang as Lang][id] })),
)

const currentKeys = computed(() =>
  isKeyTraining.value
    ? LESSON_INFO_BY_LANG[store.selectedLang as Lang][store.selectedKeyLesson as KeyLesson].keys
    : [],
)

const isRTL = computed(() => store.selectedLang === 'ar')
</script>

<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-0 space-y-6 sm:space-y-8">
    <!-- Mode header -->
    <div class="flex items-center gap-4">
      <div class="shrink-0 w-14 h-14 flex items-center justify-center rounded-2xl" :class="meta.color">
        <Icon :icon="meta.icon" class="text-3xl text-white" />
      </div>
      <div class="min-w-0">
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wide break-words">
          {{ meta.name }}
        </h2>
        <p class="text-sm text-slate-500 break-words">{{ meta.desc }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-7 border border-slate-100 dark:border-slate-800 shadow-sm space-y-6">
      <!-- Language (all modes) -->
      <div class="space-y-3">
        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Language</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="lang in languages"
            :key="lang.id"
            @click="store.selectedLang = lang.id as Lang"
            class="min-h-[44px] py-2.5 px-2 rounded-xl border-2 font-bold transition-all whitespace-nowrap overflow-hidden text-xs sm:text-sm"
            :class="store.selectedLang === lang.id
              ? 'border-primary-500 bg-primary-500 text-white shadow-lg shadow-primary-500/30'
              : 'border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-200 dark:hover:border-slate-700'"
          >
            {{ lang.flag }} {{ lang.name }}
          </button>
        </div>
      </div>

      <!-- Lesson (key_training only) -->
      <div v-if="isKeyTraining" class="space-y-3">
        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Lesson</label>
        <div class="grid sm:grid-cols-2 gap-2">
          <button
            v-for="lesson in keyLessons"
            :key="lesson.id"
            @click="store.selectedKeyLesson = lesson.id as KeyLesson"
            class="min-h-[44px] p-3 rounded-xl border-2 text-left transition-all"
            :class="store.selectedKeyLesson === lesson.id
              ? 'border-primary-500 bg-primary-500 text-white shadow-lg shadow-primary-500/30'
              : 'border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-200 dark:hover:border-slate-700'"
          >
            <span class="font-bold block break-words">{{ lesson.emoji }} {{ lesson.name }}</span>
            <span class="block text-xs opacity-70 mt-0.5 break-words">{{ lesson.desc }}</span>
          </button>
        </div>
      </div>

      <!-- Keys preview (key_training only) -->
      <div v-if="isKeyTraining" class="space-y-3">
        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Keys in this lesson</label>
        <div class="flex flex-wrap gap-2" :dir="isRTL ? 'rtl' : 'ltr'">
          <span
            v-for="key in currentKeys"
            :key="key"
            class="px-2.5 py-1.5 rounded-lg font-mono font-black text-base border border-primary-500 bg-primary-500/10 text-primary-600 dark:text-primary-300"
          >
            {{ key }}
          </span>
        </div>
      </div>

      <!-- Difficulty (non-key-training) -->
      <div v-if="!isKeyTraining" class="space-y-3">
        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Difficulty</label>
        <div class="grid gap-2 grid-cols-[repeat(auto-fill,minmax(80px,1fr))]">
          <button
            v-for="diff in difficulties"
            :key="diff.id"
            @click="store.selectedDifficulty = diff.id as Difficulty"
            class="min-h-[58px] py-2.5 px-1.5 rounded-xl border-2 font-bold transition-all flex flex-col items-center justify-center text-center"
            :class="store.selectedDifficulty === diff.id
              ? 'border-primary-500 bg-primary-500 text-white shadow-lg shadow-primary-500/30'
              : 'border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-200 dark:hover:border-slate-700'"
          >
            <span class="text-xs font-bold leading-tight">{{ diff.name }}</span>
            <span class="text-[9px] opacity-60 font-medium leading-tight mt-1 w-full text-center break-words">{{ diff.desc }}</span>
          </button>
        </div>
      </div>

      <!-- Start -->
      <button
        @click="emit('start')"
        class="w-full py-4 sm:py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-base sm:text-xl rounded-2xl shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all"
      >
        {{ isKeyTraining ? '⌨️ START TRAINING' : 'PLAY NOW' }}
      </button>
    </div>
  </div>
</template>
