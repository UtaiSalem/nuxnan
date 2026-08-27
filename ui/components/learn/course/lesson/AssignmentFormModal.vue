<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Icon } from '@iconify/vue'

const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

interface Props {
  show: boolean
  lessonId: number
  assignment?: any // If editing
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  submit: [assignment: any]
}>()

// API & Utilities
const api = useApi()
const swal = useSweetAlert()

// Form State
const form = ref<any>({
  title: '',
  description: '',
  points: 10,
  start_date: '',
  due_date: '',
  images: [] as File[],
  existingImages: [] as any[]
})

const isSubmitting = ref(false)
const imagePreviews = ref<string[]>([])

const getCurrentDateTime = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

const getTomorrowDateTime = () => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const year = tomorrow.getFullYear()
  const month = String(tomorrow.getMonth() + 1).padStart(2, '0')
  const day = String(tomorrow.getDate()).padStart(2, '0')
  const hours = String(tomorrow.getHours()).padStart(2, '0')
  const minutes = String(tomorrow.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

const resetForm = () => {
  form.value = {
    title: '',
    description: '',
    points: 10,
    start_date: getCurrentDateTime(),
    due_date: getTomorrowDateTime(),
    images: [],
    existingImages: []
  }
  imagePreviews.value = []
}

// Initialize form when assignment prop changes or modal opens
watch(() => props.assignment, (newVal) => {
  if (newVal) {
    // Edit mode
    form.value = {
      title: newVal.title,
      description: newVal.description || '',
      points: newVal.points || 10,
      start_date: newVal.start_date ? new Date(newVal.start_date) : '',
      due_date: newVal.due_date ? new Date(newVal.due_date) : '',
      images: [],
      existingImages: newVal.images || []
    }
  } else {
    // Create mode
    resetForm()
  }
}, { immediate: true })

// File Handling
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    const files = Array.from(input.files)
    form.value.images = [...form.value.images, ...files]
    
    // Create previews
    files.forEach(file => {
      const reader = new FileReader()
      reader.onload = (e) => {
        if (e.target?.result) {
          imagePreviews.value.push(e.target.result as string)
        }
      }
      reader.readAsDataURL(file)
    })
  }
}

const removeNewFile = (index: number) => {
  form.value.images.splice(index, 1)
  imagePreviews.value.splice(index, 1)
}

const removeExistingImage = (id: number) => {
  // In a real app, you might want to call an API to delete the image immediately
  // or track deleted IDs and send them on submit.
  // For now, let's just remove from the view list.
  form.value.existingImages = form.value.existingImages.filter(img => img.id !== id)
}

// Submit
const handleSubmit = async () => {
  if (!form.value.title) {
    swal.toast('กรุณาระบุหัวข้อแบบฝึกหัด', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('title', form.value.title)
    if (form.value.description) formData.append('description', form.value.description)
    formData.append('points', form.value.points.toString())
    if (form.value.start_date) formData.append('start_date', new Date(form.value.start_date).toISOString())
    if (form.value.due_date) formData.append('due_date', new Date(form.value.due_date).toISOString())

    // Append new images
    form.value.images.forEach((file) => {
      formData.append('images[]', file)
    })
    
    // Handle existing images deletion if needed (logic depends on backend)
    // Here we assume backend handles updates. For simplified update logic without image deletion support on update yet.

    let response
    if (props.assignment) {
      // Update
      // Note: put/patch with FormData can be tricky in some setups, using _method=PUT is safer
      formData.append('_method', 'PUT')
      response = await api.post(`/api/lessons/${props.lessonId}/assignments/${props.assignment.id}`, formData) as any
    } else {
      // Create
      response = await api.post(`/api/lessons/${props.lessonId}/assignments`, formData) as any
    }

    if (response.assignment) {
      emit('submit', response.assignment)
      swal.toast(props.assignment ? 'แก้ไขแบบฝึกหัดสำเร็จ' : 'สร้างแบบฝึกหัดสำเร็จ', 'success')
      emit('close')
    }
  } catch (error: any) {
    console.error('Failed to save assignment:', error)
    swal.error(error?.data?.message || 'ไม่สามารถบันทึกข้อมูลได้')
  } finally {
    isSubmitting.value = false
  }
}

// Watch 'show' prop to reset form if opening fresh
watch(() => props.show, (val) => {
  if (val && !props.assignment) {
    resetForm()
  }
})

</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:clipboard-task-edit-24-filled" class="text-green-600" />
            {{ assignment ? 'แก้ไขแบบฝึกหัด' : 'สร้างแบบฝึกหัดใหม่' }}
          </h3>
          <button @click="emit('close')" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
            <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-500" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              หัวข้อแบบฝึกหัด <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all"
              placeholder="Ex. แบบฝึกหัดทบทวนบทที่ 1"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              คำอธิบาย / โจทย์
            </label>
            <textarea
              v-model="form.description"
              rows="4"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all resize-none"
              placeholder="ระบุรายละเอียดของงาน..."
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                วันที่เริ่มต้น (Start Date)
              </label>
              <div class="relative">
                  <VueDatePicker 
                    v-model="form.start_date"
                    auto-apply
                    :enable-time-picker="true"
                    :dark="true" 
                  />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                กำหนดส่ง (Due Date)
              </label>
                  <VueDatePicker 
                    v-model="form.due_date"
                    auto-apply
                    :enable-time-picker="true"
                    :dark="true"
                  />
            </div>
          </div>

          <!-- Points -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              คะแนนเต็ม
            </label>
            <div class="relative">
              <input
                v-model="form.points"
                type="number"
                min="0"
                class="w-full px-4 py-2 pl-10 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all"
              />
              <Icon icon="fluent:star-24-regular" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
            </div>
          </div>

          <!-- Images -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              รูปภาพประกอบ
            </label>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <!-- Existing Images -->
              <div v-for="img in form.existingImages" :key="img.id" class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden">
                <img :src="img.full_url || img.image_url" class="w-full h-full object-cover" />
                <button
                  @click="removeExistingImage(img.id)"
                  class="absolute top-1 right-1 p-1bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <Icon icon="fluent:delete-16-filled" class="w-4 h-4" />
                </button>
              </div>

              <!-- New Previews -->
              <div v-for="(preview, idx) in imagePreviews" :key="idx" class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden">
                <img :src="preview" class="w-full h-full object-cover" />
                <button
                  @click="removeNewFile(idx)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full shadow-sm hover:bg-red-600 transition-colors"
                >
                  <Icon icon="fluent:dismiss-12-regular" class="w-3 h-3" />
                </button>
              </div>

              <!-- Upload Button -->
              <label class="aspect-video flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-green-500 hover:text-green-500 transition-colors bg-gray-50 dark:bg-gray-700/50">
                <Icon icon="fluent:image-add-24-regular" class="w-8 h-8 mb-2" />
                <span class="text-xs">เพิ่มรูปภาพ</span>
                <input type="file" multiple accept="image/*" class="hidden" @change="handleFileSelect" />
              </label>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
          <button
            @click="emit('close')"
            class="min-h-[44px] sm:min-h-0 px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors"
          >
            ยกเลิก
          </button>
          <button
            @click="handleSubmit"
            :disabled="isSubmitting"
            class="min-h-[44px] sm:min-h-0 px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 hover:shadow-green-500/40 hover:from-green-600 hover:to-emerald-700 active:scale-[0.98] transition-all flex items-center gap-2"
          >
            <Icon v-if="isSubmitting" icon="eos-icons:loading" class="w-5 h-5" />
            <span v-else>{{ assignment ? 'บันทึกการแก้ไข' : 'สร้างแบบฝึกหัด' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
