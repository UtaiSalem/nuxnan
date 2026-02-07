<script setup lang="ts">
import { ref, computed, watch, reactive } from 'vue'
import { Icon } from '@iconify/vue'
import Modal from '~/components/Modal.vue'
import RichTextEditor from '~/components/RichTextEditor.vue'

interface Props {
  show: boolean
  topic?: any
  isSubmitting?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  topic: null,
  isSubmitting: false
})

const emit = defineEmits<{
  'close': []
  'submit': [formData: any]
  'delete-image': [imageId: number]
}>()

// Form State
const form = reactive({
  title: '',
  content: '',
  youtube_url: '',
  min_read: 5
})

// Image State
const newImages = ref<File[]>([])
const imageInput = ref<HTMLInputElement | null>(null)

// Computed Image Previews
const imagePreviews = computed(() => {
  return newImages.value.map(file => ({
    file,
    url: URL.createObjectURL(file)
  }))
})

// Define resetForm before watchers to avoid "Cannot access before initialization" error
const resetForm = () => {
  form.title = ''
  form.content = ''
  form.youtube_url = ''
  form.min_read = 5
  newImages.value = []
}

// Watch for topic changes to populate form
watch(() => props.topic, (newTopic) => {
  if (newTopic) {
    form.title = newTopic.title || ''
    form.content = newTopic.content || ''
    form.youtube_url = newTopic.youtube_url || ''
    form.min_read = newTopic.min_read || 5
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => props.show, (isShow) => {
    if (!isShow) {
        // defined reset logic if needed when closing?
        // usually better to reset when opening "Create" mode
        if (!props.topic) resetForm()
    }
})

const closeModal = () => {
  emit('close')
}

const handleSubmit = () => {
  emit('submit', {
    ...form,
    images: newImages.value
  })
}

// Image Handling
const triggerImageInput = () => {
  imageInput.value?.click()
}

const handleImageSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    const files = Array.from(input.files)
    newImages.value = [...newImages.value, ...files]
  }
  // Reset input so same files can be selected again if cleared
  input.value = ''
}

const removeNewImage = (index: number) => {
  newImages.value.splice(index, 1)
}

const handleDeleteExistingImage = (imageId: number) => {
    emit('delete-image', imageId)
}

const isEditMode = computed(() => !!props.topic)
</script>

<template>
  <Modal :show="show" @close="closeModal" maxWidth="2xl">
    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon :icon="isEditMode ? 'fluent:edit-24-filled' : 'fluent:add-circle-24-filled'" class="w-6 h-6 text-blue-600" />
          {{ isEditMode ? 'แก้ไขหัวข้อย่อย' : 'เพิ่มหัวข้อย่อยใหม่' }}
        </h3>
        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          
          <!-- Title Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหัวข้อ <span class="text-red-500">*</span></label>
            <input 
              v-model="form.title" 
              type="text" 
              required
              placeholder="เช่น บทนำ, การติดตั้งเครื่องมือ" 
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            />
          </div>

          <!-- YouTube URL -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">YouTube URL (วิดีโอประกอบ)</label>
            <div class="relative">
              <Icon icon="fluent:video-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input 
                v-model="form.youtube_url" 
                type="url" 
                placeholder="https://www.youtube.com/watch?v=..." 
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              />
            </div>
          </div>

          <!-- Content Editor -->
          <div>
             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เนื้อหาบทเรียน</label>
             <RichTextEditor v-model="form.content" class="min-h-[200px]" />
          </div>

          <!-- Min Read -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลาที่ใช้ในการอ่าน (นาที)</label>
            <input 
              v-model="form.min_read" 
              type="number" 
              min="1"
              class="w-32 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            />
          </div>

          <!-- Images Section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพประกอบ</label>
            
            <!-- Existing Images (Edit Mode) -->
            <div v-if="topic && topic.images && topic.images.length > 0" class="mb-4">
                <p class="text-xs text-gray-500 mb-2 uppercase tracking-wide">รูปภาพเดิม</p>
                <div class="grid grid-cols-4 gap-3">
                    <div v-for="img in topic.images" :key="img.id" class="relative group aspect-square rounded-lg overflow-hidden border border-gray-200">
                        <img :src="img.full_url || img.url" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                             <button type="button" @click="handleDeleteExistingImage(img.id)" class="p-1.5 bg-red-600 text-white rounded-full hover:bg-red-700">
                                <Icon icon="fluent:delete-20-regular" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Images Preview -->
             <div v-if="newImages.length > 0" class="mb-4">
                <p class="text-xs text-green-600 mb-2 uppercase tracking-wide">รูปภาพใหม่ที่กำลังจะอัปโหลด</p>
                <div class="grid grid-cols-4 gap-3">
                    <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group aspect-square rounded-lg overflow-hidden border border-green-200 ring-2 ring-green-500/20">
                        <img :src="preview.url" class="w-full h-full object-cover">
                        <button type="button" @click="removeNewImage(index)" class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full shadow-sm hover:bg-red-600">
                            <Icon icon="fluent:dismiss-16-regular" class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload Button -->
            <input type="file" ref="imageInput" multiple accept="image/*" class="hidden" @change="handleImageSelect">
            <button 
                type="button" 
                @click="triggerImageInput"
                class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl flex items-center justify-center gap-2 text-gray-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-gray-700 transition-all"
            >
                <Icon icon="fluent:image-add-24-regular" class="w-6 h-6" />
                <span>คลิกเพื่อเพิ่มรูปภาพ</span>
            </button>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
        <button 
          @click="closeModal" 
          type="button"
          class="px-5 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 font-medium transition-colors"
          :disabled="isSubmitting"
        >
          ยกเลิก
        </button>
        <button 
          @click="handleSubmit" 
          type="button"
          class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-bold shadow-lg shadow-blue-500/30 flex items-center gap-2 transition-all"
          :class="{ 'opacity-75 cursor-not-allowed': isSubmitting }"
          :disabled="isSubmitting"
        >
          <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 20px;
}
</style>
