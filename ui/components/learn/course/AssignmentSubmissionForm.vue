<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  assignment: any
  courseId: string | number
  existingAnswer?: any
  isEditing?: boolean
  showCancel?: boolean
}>()

const emit = defineEmits<{
  (e: 'submitted'): void
  (e: 'cancel'): void
}>()

const api = useApi()
const swal = useSweetAlert()

const answerContent = ref('')
const answerFiles = ref<File[]>([])
const existingImages = ref<any[]>([])
const deletedImageIds = ref<number[]>([])
const isSubmitting = ref(false)

// Initialize form
watch(() => props.existingAnswer, (newVal) => {
    if (newVal) {
        answerContent.value = newVal.content || ''
        existingImages.value = [...(newVal.images || [])]
    } else {
        answerContent.value = ''
        existingImages.value = []
    }
}, { immediate: true, deep: true })

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    answerFiles.value = [...answerFiles.value, ...Array.from(input.files)]
  }
}

const removeFile = (index: number) => {
  answerFiles.value.splice(index, 1)
}

const removeExistingImage = (imageId: number) => {
  const index = existingImages.value.findIndex(img => img.id === imageId)
  if (index !== -1) {
    existingImages.value.splice(index, 1)
    deletedImageIds.value.push(imageId)
  }
}

const submitAnswer = async () => {
    if (!answerContent.value.trim() && answerFiles.value.length === 0 && existingImages.value.length === 0) return

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('content', answerContent.value)
        formData.append('course_id', props.courseId as string)

        answerFiles.value.forEach((file, index) => {
            formData.append(`images[${index}]`, file)
        })

        if (deletedImageIds.value.length > 0) {
           deletedImageIds.value.forEach((id, index) => {
               formData.append(`deleted_images[${index}]`, id.toString())
           })
        }
        
        await api.post(`/api/assignments/${props.assignment.id}/answers`, formData)
        
        swal.toast('ส่งคำตอบเรียบร้อยแล้ว', 'success')
        emit('submitted') // Parent should refresh data
        
        // Reset form
        answerContent.value = ''
        answerFiles.value = []
        deletedImageIds.value = []
    } catch (error) {
        console.error('Submission error:', error)
        swal.toast('ส่งคำตอบไม่สำเร็จ', 'error')
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
       <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:form-new-24-regular" class="w-6 h-6 text-orange-500" />
          {{ isEditing ? 'แก้ไขการส่งงาน' : 'ส่งงาน' }}
       </h3>
       
       <div class="space-y-4">
          <textarea 
             v-model="answerContent"
             rows="4"
             class="w-full p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all outline-none"
             placeholder="พิมพ์คำตอบของคุณ..."
          ></textarea>

          <div>
              <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">แนบรูปภาพผลงาน</label>
              <div class="flex items-center justify-center w-full">
                  <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-800 transition-colors">
                      <div class="flex flex-col items-center justify-center pt-5 pb-6">
                          <Icon icon="fluent:image-add-24-regular" class="w-8 h-8 text-gray-400 mb-1" />
                          <p class="text-sm text-gray-500 dark:text-gray-400">คลิกเพื่ออัพโหลดรูปภาพ</p>
                      </div>
                      <input type="file" multiple accept="image/*" class="hidden" @change="handleFileSelect" />
                  </label>
              </div>
          </div>

           <!-- Selected Files -->
          <div v-if="answerFiles.length" class="flex flex-wrap gap-2">
             <div v-for="(file, i) in answerFiles" :key="i" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm flex items-center gap-2">
                <span class="truncate max-w-xs">{{ file.name }}</span>
                <button @click="removeFile(i)" class="text-red-500 hover:bg-red-50 rounded-full p-0.5"><Icon icon="fluent:dismiss-16-regular" /></button>
             </div>
          </div>

           <div v-if="existingImages.length" class="flex flex-wrap gap-2">
             <div v-for="img in existingImages" :key="img.id" class="relative group w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <img :src="img.full_url || img.image_url" class="w-full h-full object-cover" />
                <button @click="removeExistingImage(img.id)" class="absolute top-1 right-1 bg-red-500/80 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"><Icon icon="fluent:delete-16-regular" /></button>
             </div>
          </div>
       </div>
       
       <div class="mt-6 flex gap-3 justify-end">
          <button 
             v-if="isEditing && showCancel !== false" 
             @click="emit('cancel')"
             class="px-6 py-2.5 rounded-xl font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
          >
             ยกเลิก
          </button>
          <button 
             @click="submitAnswer"
             :disabled="isSubmitting"
             class="px-8 py-2.5 rounded-xl font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg hover:shadow-orange-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
          >
             <Icon v-if="isSubmitting" icon="eos-icons:loading" class="w-5 h-5" />
             {{ isSubmitting ? 'กำลังส่ง...' : (isEditing ? 'บันทึกการแก้ไข' : 'ส่งงาน') }}
          </button>
       </div>
    </div>
</template>
