<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'

interface Props {
  show: boolean
  lessonId: number
  question?: any // If editing
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'submit'])

const api = useApi()
const swal = useSweetAlert()

const isLoading = ref(false)

// Form State
const form = ref({
  text: '',
  points: 1,
  images: [] as File[],
  options: [
    { text: '', is_correct: false, id: null, image: null, imageFile: null },
    { text: '', is_correct: false, id: null, image: null, imageFile: null },
    { text: '', is_correct: false, id: null, image: null, imageFile: null },
    { text: '', is_correct: false, id: null, image: null, imageFile: null }
  ],
  deleted_images: [] as number[]
})

const existingImages = ref<any[]>([])
const previewImages = ref<string[]>([])

// Initialize form
const initForm = () => {
  if (props.question) {
    // Edit mode
    form.value.text = props.question.text || ''
    form.value.points = props.question.points || 1
    form.value.images = [] // New images
    form.value.deleted_images = []
    existingImages.value = [...(props.question.images || [])] 
    
    // Options
    if (props.question.options && props.question.options.length > 0) {
        form.value.options = props.question.options.map((opt: any) => ({
            id: opt.id,
            text: opt.text,
            is_correct: !!opt.is_correct,
            image: opt.images && opt.images.length > 0 ? (opt.images[0].full_url || opt.images[0].image_url) : null,
            imageFile: null
        }))
    } else {
        // Default options if none found
        form.value.options = [
            { text: '', is_correct: false, id: null, image: null, imageFile: null },
            { text: '', is_correct: false, id: null, image: null, imageFile: null },
            { text: '', is_correct: false, id: null, image: null, imageFile: null },
            { text: '', is_correct: false, id: null, image: null, imageFile: null }
        ]
    }
  } else {
    // Create mode
    resetForm()
  }
}

// Watchers
watch(() => props.question, initForm, { immediate: true })

watch(() => props.show, (isOpen) => {
    if (isOpen) initForm()
})

function resetForm() {
  form.value = {
    text: '',
    points: 1,
    images: [],
    options: [
        { text: '', is_correct: false, id: null, image: null, imageFile: null },
        { text: '', is_correct: false, id: null, image: null, imageFile: null },
        { text: '', is_correct: false, id: null, image: null, imageFile: null },
        { text: '', is_correct: false, id: null, image: null, imageFile: null }
    ],
    deleted_images: []
  }
  existingImages.value = []
  previewImages.value = []
}

// Methods
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    const newFiles = Array.from(input.files)
    form.value.images = [...form.value.images, ...newFiles]
    
    // Generate previews
    newFiles.forEach(file => {
      previewImages.value.push(URL.createObjectURL(file))
    })
  }
}

const removeFile = (index: number) => {
  URL.revokeObjectURL(previewImages.value[index])
  previewImages.value.splice(index, 1)
  form.value.images.splice(index, 1)
}

const removeExistingImage = (imageId: number) => {
  const index = existingImages.value.findIndex(img => img.id === imageId)
  if (index !== -1) {
    existingImages.value.splice(index, 1)
    form.value.deleted_images.push(imageId)
  }
}

const addOption = () => {
    form.value.options.push({ text: '', is_correct: false, id: null, image: null, imageFile: null })
}

const removeOption = (index: number) => {
    form.value.options.splice(index, 1)
}

// setCorrectOption removed for multiple choice support

const handleOptionFileSelect = (event: Event, index: number) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    form.value.options[index].imageFile = file
    form.value.options[index].image = URL.createObjectURL(file)
  }
}

const removeOptionImage = (index: number) => {
    if (form.value.options[index].imageFile) {
        URL.revokeObjectURL(form.value.options[index].image)
    }
    form.value.options[index].image = null
    form.value.options[index].imageFile = null
}

const onSubmit = async () => {
  if (!form.value.text) {
    swal.error('กรุณาระบุคำถาม')
    return
  }
  
  if (!form.value.options.some(o => o.is_correct)) {
    swal.error('กรุณาเลือกคำตอบที่ถูกต้องอย่างน้อย 1 ข้อ')
    return
  }
  
  if (form.value.options.some(o => !o.text?.trim() && !o.image)) {
      swal.error('กรุณากรอกตัวเลือกให้ครบถ้วน (ต้องมีข้อความหรือรูปภาพ)')
      return;
  }

  isLoading.value = true
  try {
    const formData = new FormData()
    formData.append('text', form.value.text)
    formData.append('points', form.value.points.toString())
    
    // Append Options
    form.value.options.forEach((opt, index) => {
        if(opt.id) formData.append(`options[${index}][id]`, opt.id.toString())
        formData.append(`options[${index}][text]`, opt.text || '') // Allow empty text if image exists? Let's check validation later
        formData.append(`options[${index}][is_correct]`, opt.is_correct ? 'true' : 'false')
        
        if (opt.imageFile) {
            formData.append(`options[${index}][image]`, opt.imageFile)
        }
    })

    // Append Images
    form.value.images.forEach((file) => {
      formData.append('images[]', file)
    })
    
    // Deleted Images
    form.value.deleted_images.forEach((id) => {
        formData.append('deleted_images[]', id.toString())
    })

    let question;
    if (props.question) {
      // Update
      // Use _method PUT for Laravel FormData handling if standard PUT fails with files
       formData.append('_method', 'PUT')
       const res = await api.post(`/api/lessons/${props.lessonId}/questions/${props.question.id}`, formData) as any
       question = res.question
    } else {
      // Create
      const res = await api.post(`/api/lessons/${props.lessonId}/questions`, formData) as any
      question = res.question // Response format from controller
    }

    emit('submit', question)
    emit('close')
    swal.toast('บันทึกคำถามเรียบร้อย', 'success')
  } catch (error: any) {
    console.error('Failed to save question:', error)
    swal.error(error?.data?.message || 'ไม่สามารถบันทึกคำถามได้')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between">
                <span>{{ question ? 'แก้ไขคำถาม' : 'เพิ่มคำถามใหม่' }}</span>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-500">
                  <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
              </DialogTitle>
              
              <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <!-- Question Text -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำถาม</label>
                  <textarea 
                    v-model="form.text" 
                    rows="3" 
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500"
                    placeholder="ระบุคำถาม..."
                  ></textarea>
                </div>

                <!-- Images -->
                <div>
                   <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รูปภาพประกอบ (ถ้ามี)</label>
                   
                   <!-- Existing Images -->
                   <div v-if="existingImages.length > 0" class="grid grid-cols-4 gap-2 mb-2">
                      <div v-for="img in existingImages" :key="img.id" class="relative group">
                          <img :src="img.full_url || img.image_url" class="h-16 w-full object-cover rounded-lg border dark:border-gray-600" />
                          <button @click="removeExistingImage(img.id)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                              <Icon icon="fluent:dismiss-12-regular" class="w-3 h-3" />
                          </button>
                      </div>
                   </div>

                   <!-- New Images Preview -->
                   <div v-if="previewImages.length > 0" class="grid grid-cols-4 gap-2 mb-2">
                      <div v-for="(url, idx) in previewImages" :key="idx" class="relative group">
                          <img :src="url" class="h-16 w-full object-cover rounded-lg border dark:border-gray-600" />
                          <button @click="removeFile(idx)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                              <Icon icon="fluent:dismiss-12-regular" class="w-3 h-3" />
                          </button>
                      </div>
                   </div>
                   
                   <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                      <Icon icon="fluent:image-add-24-regular" class="w-5 h-5" />
                      เพิ่มรูปภาพ
                      <input type="file" multiple accept="image/*" class="hidden" @change="handleFileSelect">
                   </label>
                </div>

                <hr class="border-gray-200 dark:border-gray-700 my-4" />

                <!-- Options -->
                <div>
                   <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวเลือกคำตอบ</label>
                   <div class="space-y-3">
                       <div v-for="(option, index) in form.options" :key="index" class="flex items-center gap-3">
                            <!-- Correct Checkbox (Custom Visual) -->
                            <div class="flex-shrink-0 pt-1 cursor-pointer" @click="option.is_correct = !option.is_correct" title="เลือกข้อที่ถูกต้อง (เลือกได้มากกว่า 1 ข้อ)">
                                <div 
                                    class="w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200"
                                    :class="option.is_correct ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 dark:border-gray-600 hover:border-green-400'"
                                >
                                    <Icon v-if="option.is_correct" icon="fluent:checkmark-16-filled" class="w-4 h-4" />
                                </div>
                                <input type="checkbox" v-model="option.is_correct" class="hidden">
                            </div>
                           
                           <!-- Option Text & Image -->
                           <div class="flex-1">
                               <div v-if="option.image" class="relative mb-2 w-max group">
                                   <img :src="option.image" class="h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900" />
                                   <button @click="removeOptionImage(index)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                       <Icon icon="fluent:dismiss-12-regular" class="w-3 h-3" />
                                   </button>
                               </div>

                               <div class="flex gap-2">
                                   <input 
                                      type="text" 
                                      v-model="option.text" 
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm"
                                      :placeholder="`ตัวเลือกที่ ${index + 1}`"
                                      :class="{'border-green-500 ring-1 ring-green-500': option.is_correct}"
                                   >
                                   <label class="flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="เพิ่มรูปภาพ">
                                        <Icon icon="fluent:image-20-regular" class="w-5 h-5" />
                                        <input type="file" accept="image/*" class="hidden" @change="(e) => handleOptionFileSelect(e, index)">
                                   </label>
                               </div>
                           </div>

                           <!-- Remove Button -->
                           <button 
                              @click="removeOption(index)" 
                              class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center text-gray-400 hover:text-red-500 p-1"
                              v-if="form.options.length > 2"
                           >
                              <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                           </button>
                       </div>
                   </div>
                   
                   <button @click="addOption" class="mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium">
                       <Icon icon="fluent:add-circle-20-filled" class="w-5 h-5" />
                       เพิ่มตัวเลือก
                   </button>
                </div>

                <hr class="border-gray-200 dark:border-gray-700 my-4" />

                <!-- Points -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนน</label>
                  <input 
                    type="number" 
                    v-model="form.points" 
                    class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500"
                    min="1"
                  >
                </div>

              </div>

              <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <button
                  type="button"
                  class="inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none"
                  @click="emit('close')"
                  :disabled="isLoading"
                >
                  ยกเลิก
                </button>
                <button
                  type="button"
                  class="inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2"
                  @click="onSubmit"
                  :disabled="isLoading"
                >
                  <Icon v-if="isLoading" icon="eos-icons:loading" class="w-4 h-4 animate-spin" />
                  <span>บันทึก</span>
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
