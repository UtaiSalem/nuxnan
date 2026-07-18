<template>
  <div class="max-w-6xl mx-auto p-6">
    <div class="text-center py-8">
      <h1 class="text-3xl font-bold mb-2">{{ $t('discoverySchools.title') }}</h1>
      <p class="text-gray-500">{{ $t('discoverySchools.subtitle') }}</p>
    </div>

    <div class="flex flex-wrap gap-3 mb-6 items-end">
      <div class="flex-1 min-w-[200px]">
        <InputText v-model="filters.q" :placeholder="$t('discoverySchools.search_placeholder')" class="w-full" @input="onSearchDebounced" />
      </div>
      <Dropdown v-model="filters.sort" :options="sortOptions" option-label="label" option-value="value" @change="reload" class="w-52" />
    </div>

    <div v-if="pending && !schools?.length" class="text-center py-16 text-gray-400">
      {{ $t('discoverySchools.loading') }}
    </div>

    <div v-else-if="!schools?.length" class="text-center py-16">
      <div class="text-6xl mb-3">🏫</div>
      <div class="text-gray-500">{{ $t('discoverySchools.empty') }}</div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <SchoolSupportCard v-for="s in schools" :key="s.id" :school="s" />
    </div>

    <Paginator
      v-if="meta && meta.total > (meta.per_page || 12)"
      :first="((meta.current_page || 1) - 1) * (meta.per_page || 12)"
      :rows="meta.per_page || 12"
      :total-records="meta.total"
      @page="onPage"
      class="mt-6"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { usePublicSchools } from '~/composables/usePublicSchools'
import SchoolSupportCard from '~/components/discovery/SchoolSupportCard.vue'

const { t } = useI18n()
useHead({ title: () => t('discoverySchools.title') })

const filters = reactive({ q: '', sort: 'recent', page: 1 })
const sortOptions = [
  { label: t('discoverySchools.sort.recent'), value: 'recent' },
  { label: t('discoverySchools.sort.most_supported'), value: 'most_supported' },
  { label: t('discoverySchools.sort.most_courses'), value: 'most_courses' },
]

const schools = ref<any[]>([])
const meta = ref<any>(null)
const pending = ref(false)
const { list } = usePublicSchools()

async function reload () {
  pending.value = true
  try {
    const res: any = await list({ q: filters.q || undefined, sort: filters.sort, page: filters.page })
    schools.value = res?.data || []
    meta.value = res?.meta || null
  } finally {
    pending.value = false
  }
}

let searchTimer: any
function onSearchDebounced () {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { filters.page = 1; reload() }, 300)
}

function onPage (e: any) { filters.page = (e.page || 0) + 1; reload() }

await reload()
</script>
