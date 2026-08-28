<script setup lang="ts">
import { Icon } from '@iconify/vue'
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { $api } = useNuxtApp()

// Table state
const words      = ref<any[]>([])
const meta       = ref<any>(null)
const loading    = ref(true)
const search     = ref('')
const langFilter = ref('all')
const diffFilter = ref('all')
const page       = ref(1)

// Form state
const showForm  = ref(false)
const editing   = ref<any>(null)
const form = reactive({
  text: '', language: 'en', difficulty: 'easy',
  category: 'general', meaning: '', is_active: true
})

async function loadWords() {
  loading.value = true
  try {
    const res = await $api.get('/admin/typing/words', {
      params: {
        search: search.value || undefined,
        language:   langFilter.value !== 'all' ? langFilter.value : undefined,
        difficulty: diffFilter.value !== 'all' ? diffFilter.value : undefined,
        page: page.value, per_page: 25
      }
    })
    words.value = res.data.data.data
    meta.value  = res.data.data.meta ?? res.data.data
  } catch (error) {
    console.error('Failed to load words', error)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  Object.assign(form, { text:'', language:'en', difficulty:'easy', category:'general', meaning:'', is_active:true })
  showForm.value = true
}

function openEdit(word: any) {
  editing.value = word
  Object.assign(form, { text: word.text, language: word.language, difficulty: word.difficulty,
    category: word.category, meaning: word.meaning ?? '', is_active: word.is_active })
  showForm.value = true
}

async function save() {
  try {
    if (editing.value) {
      await $api.put(`/admin/typing/words/${editing.value.id}`, form)
    } else {
      await $api.post('/admin/typing/words', form)
    }
    showForm.value = false
    await loadWords()
  } catch (error) {
    console.error('Save failed', error)
    alert('Failed to save word')
  }
}

async function deleteWord(word: any) {
  if (!confirm(`ลบคำ "${word.text}" ใช่ไหม?`)) return
  try {
    await $api.delete(`/admin/typing/words/${word.id}`)
    await loadWords()
  } catch (error) {
    console.error('Delete failed', error)
  }
}

async function toggleActive(word: any) {
  try {
    await $api.patch(`/admin/typing/words/${word.id}`, { is_active: !word.is_active })
    word.is_active = !word.is_active
  } catch (error) {
    console.error('Toggle active failed', error)
  }
}

// Debounce search
let searchTimeout: any
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => { page.value = 1; loadWords() }, 400)
})

watch([langFilter, diffFilter, page], loadWords)
onMounted(loadWords)
</script>

<template>
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-slate-900 dark:text-white">⌨️ จัดการคำศัพท์</h1>
      <p class="text-slate-500 text-sm">{{ meta?.total ?? meta?.total_items ?? 0 }} คำ</p>
    </div>
    <button @click="openCreate"
      class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition-all hover:scale-105 flex items-center gap-2">
      <Icon icon="heroicons:plus" /> เพิ่มคำใหม่
    </button>
  </div>

  <!-- Filters -->
  <div class="flex flex-wrap gap-3">
    <div class="flex-1 min-w-[200px] relative">
      <Icon icon="heroicons:magnifying-glass" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
      <input v-model="search" placeholder="ค้นหาคำ..." 
        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:border-primary-500 focus:outline-none" />
    </div>
    <select v-model="langFilter" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
      <option value="all">ทุกภาษา</option>
      <option value="en">🇬🇧 English</option>
      <option value="th">🇹🇭 Thai</option>
      <option value="ar">🇸🇦 Arabic</option>
    </select>
    <select v-model="diffFilter" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
      <option value="all">ทุกระดับ</option>
      <option v-for="d in ['beginner','easy','normal','hard','expert']" :key="d" :value="d">{{ d }}</option>
    </select>
  </div>

  <!-- Table -->
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
      <Icon icon="eos-icons:loading" class="animate-spin text-3xl mr-3" /> กำลังโหลด...
    </div>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800">
          <tr>
            <th class="px-6 py-4 text-left font-black text-slate-500 uppercase text-xs tracking-widest">คำ</th>
            <th class="px-4 py-4 text-left font-black text-slate-500 uppercase text-xs tracking-widest">ภาษา</th>
            <th class="px-4 py-4 text-left font-black text-slate-500 uppercase text-xs tracking-widest">ระดับ</th>
            <th class="px-4 py-4 text-left font-black text-slate-500 uppercase text-xs tracking-widest">หมวด</th>
            <th class="px-4 py-4 text-center font-black text-slate-500 uppercase text-xs tracking-widest">สถานะ</th>
            <th class="px-4 py-4 text-right font-black text-slate-500 uppercase text-xs tracking-widest">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="word in words" :key="word.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <td class="px-6 py-4">
              <div>
                <span class="font-bold text-slate-900 dark:text-white" :dir="word.language === 'ar' ? 'rtl' : 'ltr'">{{ word.text }}</span>
                <p v-if="word.meaning" class="text-slate-400 text-xs">{{ word.meaning }}</p>
              </div>
            </td>
            <td class="px-4 py-4">
              <span class="text-lg">{{ { en:'🇬🇧', th:'🇹🇭', ar:'🇸🇦' }[word.language as 'en'|'th'|'ar'] }}</span>
            </td>
            <td class="px-4 py-4">
              <span class="text-xs font-bold px-2.5 py-1 rounded-full capitalize"
                :class="{
                  'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': word.difficulty === 'beginner',
                  'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': word.difficulty === 'easy',
                  'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': word.difficulty === 'normal',
                  'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400': word.difficulty === 'hard',
                  'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': word.difficulty === 'expert',
                }">{{ word.difficulty }}</span>
            </td>
            <td class="px-4 py-4 text-slate-500 text-xs">{{ word.category }}</td>
            <td class="px-4 py-4 text-center">
              <button @click="toggleActive(word)"
                :class="word.is_active ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600'"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="word.is_active ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block h-4 w-4 bg-white rounded-full transition-transform"></span>
              </button>
            </td>
            <td class="px-4 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(word)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-400 hover:text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition-colors">
                  <Icon icon="heroicons:pencil" class="w-4 h-4" />
                </button>
                <button @click="deleteWord(word)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                  <Icon icon="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="words.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">ไม่พบคำศัพท์</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form Modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-8 w-full max-w-md border border-slate-200 dark:border-slate-700 shadow-2xl space-y-6">
          <h2 class="text-xl font-black text-slate-900 dark:text-white">
            {{ editing ? 'แก้ไขคำ' : 'เพิ่มคำใหม่' }}
          </h2>
          <div class="space-y-4">
            <div>
              <label class="text-xs font-bold text-slate-500 uppercase block mb-2">คำ *</label>
              <input v-model="form.text" :dir="form.language === 'ar' ? 'rtl' : 'ltr'"
                class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-transparent focus:border-primary-500 focus:outline-none font-bold text-slate-900 dark:text-white text-xl" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-bold text-slate-500 uppercase block mb-2">ภาษา</label>
                <select v-model="form.language" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary-500 focus:outline-none">
                  <option value="en">🇬🇧 English</option>
                  <option value="th">🇹🇭 Thai</option>
                  <option value="ar">🇸🇦 Arabic</option>
                </select>
              </div>
              <div>
                <label class="text-xs font-bold text-slate-500 uppercase block mb-2">ระดับ</label>
                <select v-model="form.difficulty" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary-500 focus:outline-none">
                  <option v-for="d in ['beginner','easy','normal','hard','expert']" :key="d" :value="d">{{ d }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-500 uppercase block mb-2">หมวดหมู่</label>
              <input v-model="form.category"
                class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-transparent focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-500 uppercase block mb-2">ความหมาย (ไม่บังคับ)</label>
              <input v-model="form.meaning"
                class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-transparent focus:border-primary-500 focus:outline-none" />
            </div>
          </div>
          <div class="flex gap-3">
            <button @click="showForm = false"
              class="flex-1 py-3 border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-bold rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
              ยกเลิก
            </button>
            <button @click="save"
              class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition-all hover:scale-105">
              {{ editing ? 'บันทึก' : 'เพิ่ม' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
