<script setup lang="ts">
definePageMeta({
  layout: false,
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const ready = ref(false)

const resolveAcademy = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (res.success) {
      academyId.value = res.academy.id
    }
  } catch (e) {
    console.error('Failed to resolve academy', e)
  } finally {
    ready.value = true
  }
}

provide('academyId', academyId)
provide('academyName', academyName)

onMounted(() => resolveAcademy())
</script>

<template>
  <NuxtPage v-if="ready" />
  <div v-else class="flex items-center justify-center min-h-[200px]">
    <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary-500 border-t-transparent"></div>
  </div>
</template>
