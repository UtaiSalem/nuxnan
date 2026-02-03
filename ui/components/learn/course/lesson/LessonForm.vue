<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useObjectUrl } from '@vueuse/core'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'

interface Props {
  lesson?: any
  courseId: string | number
  isEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isEdit: false,
})

const emit = defineEmits<{
  submit: [data: any]
  cancel: []
}>()

const api = useApi()
const swal = useSweetAlert()
const config = useRuntimeConfig()

// Helper function to get image URL
const getImageUrl = (image: any): string => {
  if (image.full_url) return image.full_url
  if (image.url) return image.url
  if (image.filename) return `${config.public.apiBase}/storage/images/courses/lessons/${image.filename}`
  return ''
}

// Handle image load error
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = '/images/placeholder-image.png'
}

// Form state
const form = ref({
  title: '',
  description: '',
  content: '',
  youtube_url: '',
  min_read: 0,
  point_tuition_fee: 0,
  order: 0,
  status: 1,
})

// Temp images for upload
const tempImages = ref<{ file: File; url: string }[]>([])
const existingImages = ref<any[]>([])
const isDragActive = ref(false)
const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})

// Initialize form with lesson data if editing
watch(
  () => props.lesson,
  (lesson) => {
    if (lesson) {
      form.value = {
        title: lesson.title || '',
        description: lesson.description || '',
        content: lesson.content || '',
        youtube_url: lesson.youtube_url || '',
        min_read: lesson.min_read || 0,
        point_tuition_fee: lesson.point_tuition_fee || 0,
        order: lesson.order || 0,
        status: lesson.status ?? 1,
      }
      existingImages.value = lesson.images || []
    }
  },
  { immediate: true }
)

// Form title
const formTitle = computed(() => (props.isEdit ? 'แก้ไขบทเรียน' : 'สร้างบทเรียนใหม่'))

// Drag & Drop handlers
const toggleActive = () => {
  isDragActive.value = !isDragActive.value
}

const handleDrop = (e: DragEvent) => {
  isDragActive.value = false
  const files = e.dataTransfer?.files
  if (files) {
    addFiles(files)
  }
}

const handleFileChange = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (input.files) {
    addFiles(input.files)
  }
}

const addFiles = (files: FileList) => {
  Array.from(files).forEach((file) => {
    if (file.type.startsWith('image/')) {
      const url = URL.createObjectURL(file)
      tempImages.value.push({ file, url })
    }
  })
}

const deleteTempImage = (index: number) => {
  URL.revokeObjectURL(tempImages.value[index].url)
  tempImages.value.splice(index, 1)
}

const deleteExistingImage = async (index: number, imageId: number) => {
  const confirmed = await swal.confirmDelete('รูปภาพนี้')
  if (!confirmed) return

  try {
    await api.delete(`/api/lessons/${props.lesson.id}/images/${imageId}`)
    existingImages.value.splice(index, 1)
    swal.toast('ลบรูปภาพสำเร็จ', 'success')
  } catch (err) {
    console.error('Failed to delete image:', err)
    swal.error('ไม่สามารถลบรูปภาพได้')
  }
}

// Validation
const validateForm = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = 'กรุณากรอกชื่อบทเรียน'
  }

  return Object.keys(errors.value).length === 0
}

// Submit handler
const handleSubmit = async () => {
  if (!validateForm()) return

  isSubmitting.value = true

  try {
    const formData = new FormData()

    // Add form fields
    formData.append('title', form.value.title)
    formData.append('description', form.value.description)
    formData.append('content', form.value.content)
    formData.append('youtube_url', form.value.youtube_url)
    formData.append('min_read', String(form.value.min_read))
    formData.append('point_tuition_fee', String(form.value.point_tuition_fee))
    formData.append('order', String(form.value.order))
    formData.append('status', String(form.value.status))

    // Add images
    tempImages.value.forEach((img, index) => {
      formData.append(`images[${index}]`, img.file)
    })

    let response
    if (props.isEdit && props.lesson) {
      // For PUT/PATCH with FormData, use POST with _method
      formData.append('_method', 'PUT')
      response = await api.post(
        `/api/courses/${props.courseId}/lessons/${props.lesson.id}`,
        formData
      )
    } else {
      response = await api.post(`/api/courses/${props.courseId}/lessons`, formData)
    }

    // Update existingImages with newly uploaded images from response
    const updatedLesson = response.lesson || response.newLesson || response
    if (updatedLesson?.images) {
      existingImages.value = updatedLesson.images
    }
    
    // Clear temp images after successful upload
    tempImages.value.forEach((img) => URL.revokeObjectURL(img.url))
    tempImages.value = []

    swal.success(props.isEdit ? 'แก้ไขบทเรียนสำเร็จ' : 'สร้างบทเรียนสำเร็จ')
    emit('submit', response)
  } catch (err: any) {
    console.error('Failed to save lesson:', err)
    if (err.data?.errors) {
      errors.value = err.data.errors
    }
    swal.error(err.data?.message || 'ไม่สามารถบันทึกบทเรียนได้')
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = () => {
  emit('cancel')
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 p-6">
      <h2 class="text-2xl font-bold text-white flex items-center gap-3">
        <Icon
          :icon="isEdit ? 'fluent:edit-24-filled' : 'fluent:add-circle-24-filled'"
          class="w-7 h-7"
        />
        {{ formTitle }}
      </h2>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
      <!-- Title -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          ชื่อบทเรียน <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.title"
          type="text"
          placeholder="กรอกชื่อบทเรียน..."
          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          :class="{ 'border-red-500': errors.title }"
        />
        <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          คำอธิบายบทเรียน
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="อธิบายเกี่ยวกับบทเรียนนี้..."
          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all resize-none"
        />
      </div>

      <!-- Content (Rich Text Editor) -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          เนื้อหาบทเรียน
        </label>
        <ClientOnly>
          <RichTextEditor
            v-model="form.content"
            placeholder="พิมพ์เนื้อหาบทเรียน... (รองรับ การจัดรูปแบบตัวอักษร, รูปภาพ, ตาราง, โค้ด และอื่นๆ)"
            min-height="300px"
          />
          <template #fallback>
            <div
              class="w-full h-[300px] bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center"
            >
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
          </template>
        </ClientOnly>
      </div>

      <!-- Image Upload -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          รูปภาพประกอบ
        </label>

        <!-- Existing Images (Edit mode) -->
        <div v-if="existingImages.length > 0" class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(image, index) in existingImages" :key="image.id" class="relative group">
            <img
              :src="getImageUrl(image)"
              :alt="`Image ${index + 1}`"
              class="w-full h-32 object-cover rounded-lg"
              @error="handleImageError"
            />
            <button
              type="button"
              @click="deleteExistingImage(index, image.id)"
              class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
            >
              <Icon icon="fluent:delete-24-filled" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Temp Images Preview -->
        <div v-if="tempImages.length > 0" class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(image, index) in tempImages" :key="index" class="relative group">
            <img
              :src="image.url"
              :alt="`New Image ${index + 1}`"
              class="w-full h-32 object-cover rounded-lg"
            />
            <button
              type="button"
              @click="deleteTempImage(index)"
              class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
            >
              <Icon icon="fluent:delete-24-filled" class="w-4 h-4" />
            </button>
            <div
              class="absolute bottom-2 left-2 px-2 py-0.5 bg-green-500 text-white text-xs rounded"
            >
              ใหม่
            </div>
          </div>
        </div>

        <!-- Dropzone -->
        <div
          class="relative w-full p-8 border-2 border-dashed rounded-xl transition-all cursor-pointer"
          :class="
            isDragActive
              ? 'bg-blue-50 border-blue-400 dark:bg-blue-900/20'
              : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700'
          "
          @dragenter.prevent="toggleActive"
          @dragleave.prevent="toggleActive"
          @dragover.prevent
          @drop.prevent="handleDrop"
          @click=";($refs.fileInput as HTMLInputElement).click()"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            accept="image/*"
            class="hidden"
            @change="handleFileChange"
          />
          <div class="text-center">
            <Icon
              icon="fluent:cloud-arrow-up-24-regular"
              class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3"
            />
            <p class="text-gray-600 dark:text-gray-400 font-medium">
              ลากไฟล์มาวางที่นี่ หรือ
              <span class="text-blue-600 dark:text-blue-400">คลิกเพื่อเลือก</span>
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">PNG, JPG, GIF สูงสุด 4MB</p>
          </div>
        </div>
      </div>

      <!-- YouTube URL -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          ลิงก์ YouTube (ถ้ามี)
        </label>
        <div class="relative">
          <Icon
            icon="logos:youtube-icon"
            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5"
          />
          <input
            v-model="form.youtube_url"
            type="text"
            placeholder="https://www.youtube.com/watch?v=..."
            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          />
        </div>
      </div>

      <!-- Settings Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Point Fee -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            แต้มค่าธรรมเนียม
          </label>
          <input
            v-model.number="form.point_tuition_fee"
            type="number"
            min="0"
            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          />
        </div>

        <!-- Order -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            ลำดับบทเรียน
          </label>
          <input
            v-model.number="form.order"
            type="number"
            min="0"
            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          />
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            สถานะ
          </label>
          <select
            v-model.number="form.status"
            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          >
            <option :value="1">เผยแพร่</option>
            <option :value="0">แบบร่าง</option>
          </select>
        </div>
      </div>

      <!-- Action Buttons -->
      <div
        class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700"
      >
        <button
          type="button"
          @click="handleCancel"
          class="px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
        >
          ยกเลิก
        </button>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg hover:shadow-xl font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <Icon
            v-if="isSubmitting"
            icon="fluent:spinner-ios-20-regular"
            class="w-5 h-5 animate-spin"
          />
          <Icon
            v-else
            :icon="isEdit ? 'fluent:save-24-filled' : 'fluent:add-circle-24-filled'"
            class="w-5 h-5"
          />
          {{ isEdit ? 'บันทึกการแก้ไข' : 'สร้างบทเรียน' }}
        </button>
      </div>
    </form>
  </div>
</template>
