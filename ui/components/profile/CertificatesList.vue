<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  userId: number | string
}>()

const certificates = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const api = useApi()

const fetchCertificates = async () => {
  if (loading.value) return

  loading.value = true
  try {
    const response = await api.get(`/api/users/${props.userId}/membered-courses`, {
      page: page.value,
      status: 'completed',
      per_page: 8
    })

    if (response.success) {
      if (page.value === 1) {
        certificates.value = response.courses || response.data?.courses || []
      } else {
        const newCourses = response.courses || response.data?.courses || []
        certificates.value = [...certificates.value, ...newCourses]
      }
      
      const pagination = response.pagination || response.data?.pagination || { last_page: 1, total: 0 }
      lastPage.value = pagination.last_page
      total.value = pagination.total
    }
  } catch (error) {
    console.error('Failed to fetch certificates:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCertificates()
})

const loadMore = () => {
  if (page.value < lastPage.value) {
    page.value++
    fetchCertificates()
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Mock Download (since we don't have PDF generation yet)
const downloadCertificate = (courseName: string) => {
  alert(`กำลังดาวน์โหลดเกียรติบัตร: ${courseName}\n(ฟีเจอร์นี้อยู่ระหว่างการพัฒนา)`)
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="loading && certificates.length === 0" class="flex justify-center py-12">
       <Icon icon="svg-spinners:3-dots-fade" class="w-12 h-12 text-gray-400" />
    </div>

    <div v-else-if="certificates.length > 0">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div 
            v-for="cert in certificates" 
            :key="cert.id"
            class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row group"
        >
            <!-- Left: Icon/Image -->
            <div class="w-full md:w-32 bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/30 dark:to-amber-800/30 flex items-center justify-center p-6 relative overflow-hidden group-hover:scale-105 transition-transform duration-500">
                <!-- Watermark -->
                <Icon icon="fluent:certificate-24-filled" class="absolute -bottom-4 -right-4 w-24 h-24 text-amber-500/10 rotate-[-15deg]" />
                
                <Icon icon="fluent:certificate-24-filled" class="w-12 h-12 text-amber-500 dark:text-amber-400 shadow-sm" />
            </div>

            <!-- Right: Content -->
            <div class="p-4 flex-grow flex flex-col justify-between">
                <div>
                   <h4 class="font-bold text-gray-800 dark:text-white mb-1 line-clamp-2" :title="cert.name">
                       {{ cert.name }}
                   </h4>
                   <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                       Instructor: {{ cert.user?.name || 'Unknown' }}
                   </p>
                   
                   <div class="flex items-center gap-3 text-xs mb-3">
                        <div class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-0.5 rounded flex items-center gap-1">
                             <Icon icon="fluent:checkmark-circle-16-filled" />
                             Completed
                        </div>
                        <div v-if="cert.grade" class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded font-bold">
                             Grade: {{ cert.grade }}
                        </div>
                   </div>
                   
                   <p class="text-[10px] text-gray-400">
                       ได้รับเมื่อ: {{ formatDate(cert.completion_date) }}
                   </p>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <button 
                        @click="downloadCertificate(cert.name)"
                        class="text-xs font-bold text-vikinger-cyan hover:text-cyan-400 flex items-center gap-1 transition-colors"
                    >
                        <Icon icon="fluent:arrow-download-16-filled" />
                        ดาวน์โหลด
                    </button>
                </div>
            </div>
        </div>
      </div>

       <!-- Load More -->
      <div v-if="page < lastPage" class="mt-8 text-center">
        <button 
           @click="loadMore"
           :disabled="loading"
           class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-full text-sm font-bold transition-colors disabled:opacity-50"
        >
           <span v-if="loading" class="flex items-center gap-2">
             <Icon icon="svg-spinners:ring-resize" /> Loading...
           </span>
           <span v-else>Load More</span>
        </button>
      </div>
    </div>

    <div v-else class="text-center py-16 bg-gray-800/30 rounded-xl border border-gray-700/50">
      <div class="w-16 h-16 bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="fluent:certificate-off-24-regular" class="w-8 h-8 text-gray-500" />
      </div>
      <h3 class="text-white font-bold text-lg mb-1">ไม่มีเกียรติบัตร</h3>
      <p class="text-gray-400 text-sm">
        เมื่อเรียนจบคอร์ส คุณจะได้รับเกียรติบัตรที่นี่
      </p>
    </div>
  </div>
</template>
