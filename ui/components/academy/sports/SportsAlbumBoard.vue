<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- โหมด A — รายการอัลบั้ม -->
    <template v-if="!activeAlbum">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 min-w-0 flex-1 break-words">อัลบั้มรูปภาพ</h2>
        <button
          v-if="canManage"
          @click="showCreateForm = !showCreateForm"
          class="flex-shrink-0 min-h-[44px] px-4 py-2 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg"
        >
          สร้างอัลบั้มใหม่
        </button>
      </div>

      <!-- ฟอร์มสร้างอัลบั้ม -->
      <div
        v-if="canManage && showCreateForm"
        class="p-4 bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700"
      >
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">ชื่ออัลบั้ม <span class="text-red-500">*</span></label>
            <input
              v-model="formAlbum.name"
              type="text"
              class="w-full min-h-[44px] px-3 py-2 border rounded-md dark:bg-slate-900 dark:border-slate-700"
              placeholder="เช่น รูปตอนแข่งวิ่ง 100ม."
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">คำอธิบาย</label>
            <textarea
              v-model="formAlbum.description"
              class="w-full min-h-[44px] px-3 py-2 border rounded-md dark:bg-slate-900 dark:border-slate-700"
              rows="2"
            ></textarea>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">รายการแข่ง (ไม่บังคับ)</label>
              <select v-model="formAlbum.discipline_id" class="w-full min-h-[44px] px-3 py-2 border rounded-md dark:bg-slate-900 dark:border-slate-700">
                <option :value="null">-- ไม่ระบุ --</option>
                <option v-for="d in disciplines" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">คณะสี (ไม่บังคับ)</label>
              <select v-model="formAlbum.house_group_id" class="w-full min-h-[44px] px-3 py-2 border rounded-md dark:bg-slate-900 dark:border-slate-700">
                <option :value="null">-- ไม่ระบุ --</option>
                <option v-for="h in houses" :key="h.id" :value="h.id">{{ h.name }}</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-2 min-h-[44px]">
            <input type="checkbox" id="isPublicCheckbox" v-model="formAlbum.is_public" class="w-5 h-5" />
            <label for="isPublicCheckbox" class="flex min-h-[44px] items-center text-sm font-medium">ให้คนทั่วไปดูได้</label>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button
              @click="showCreateForm = false"
              class="min-h-[44px] px-4 py-2 border border-slate-300 rounded-md hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
            >
              ยกเลิก
            </button>
            <button
              @click="submitCreateAlbum"
              :disabled="!formAlbum.name || isSubmitting"
              class="min-h-[44px] px-4 py-2 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg disabled:opacity-50"
            >
              บันทึก
            </button>
          </div>
        </div>
      </div>

      <!-- กริดอัลบั้ม -->
      <div v-if="albums.length > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
        <div
          v-for="album in albums"
          :key="album.id"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col cursor-pointer hover:shadow-md transition-shadow"
          @click="openAlbum(album)"
        >
          <!-- ปกอัลบั้ม -->
          <div class="aspect-video w-full bg-slate-100 dark:bg-slate-900 relative">
            <img
              v-if="album.cover_photo?.thumbnail_url"
              :src="album.cover_photo.thumbnail_url"
              class="w-full h-full object-cover"
              loading="lazy"
              alt=""
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
              <Icon icon="fluent:image-24-regular" class="w-10 h-10" />
            </div>
            
            <div v-if="!album.is_public" class="absolute top-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded-md flex items-center gap-1">
              <Icon icon="fluent:lock-closed-16-regular" />
              ส่วนตัว
            </div>
          </div>
          
          <!-- ข้อมูลอัลบั้ม -->
          <div class="p-3 sm:p-4 flex-1 flex flex-col">
            <div class="flex-1">
              <h3 class="font-bold text-slate-800 dark:text-slate-100 line-clamp-1">{{ album.name }}</h3>
              <p class="text-sm text-slate-500 mt-1">{{ album.photos_count || 0 }} รูป</p>
              
              <div class="flex flex-wrap gap-1 mt-2">
                <span v-if="album.discipline_id" class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 text-xs rounded-full">
                  {{ getDisciplineName(album.discipline_id) }}
                </span>
                <span v-if="album.house_group_id" class="inline-block px-2 py-0.5 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 text-xs rounded-full">
                  {{ getHouseName(album.house_group_id) }}
                </span>
              </div>
            </div>
            
            <div v-if="canManage" class="mt-3 flex justify-end gap-2" @click.stop>
              <button
                @click="deleteAlbumPrompt(album)"
                class="min-h-[44px] p-2 text-red-500 hover:bg-red-50 rounded-md dark:hover:bg-red-900/20"
                title="ลบอัลบั้ม"
              >
                <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else-if="!isLoading" class="text-center py-10 bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700">
        <Icon icon="fluent:image-multiple-24-regular" class="w-12 h-12 mx-auto text-slate-300" />
        <p class="mt-3 text-slate-500">ยังไม่มีอัลบั้มรูปภาพ</p>
      </div>
    </template>

    <!-- โหมด B — ดูรูปในอัลบั้ม -->
    <template v-else>
      <div class="flex flex-col space-y-4">
        <!-- ปุ่มกลับ & หัวเรื่อง -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <button
            @click="closeAlbum"
            class="min-h-[44px] flex-shrink-0 inline-flex items-center gap-1 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
          >
            <Icon icon="fluent:arrow-left-20-regular" class="w-5 h-5" />
            กลับไปรายการอัลบั้ม
          </button>
          
          <div class="min-w-0 flex-1 break-words">
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ activeAlbum.name }}</h2>
            <p v-if="activeAlbum.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ activeAlbum.description }}</p>
            <p class="text-sm text-slate-500 mt-1">จำนวน {{ photos.length }} รูป</p>
          </div>
        </div>

        <!-- กล่องอัปโหลด (เฉพาะ canManage) -->
        <div v-if="canManage" class="bg-white dark:bg-slate-800 p-4 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700">
          <div class="flex flex-col gap-3">
            <div v-if="uploadError" class="p-3 bg-red-100 text-red-700 border border-red-200 rounded-md text-sm">
              {{ uploadError }}
            </div>
            
            <label
              class="min-h-[44px] flex items-center justify-center gap-2 cursor-pointer border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 p-4 transition-colors"
              :class="{ 'opacity-50 cursor-not-allowed': isUploading }"
            >
              <input
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleFileSelect"
                :disabled="isUploading"
              />
              <Icon icon="fluent:cloud-arrow-up-24-regular" class="w-6 h-6" />
              <span class="font-medium">เลือกรูปภาพเพื่ออัปโหลด</span>
            </label>
            
            <!-- รายการไฟล์ที่เลือก -->
            <div v-if="selectedFiles.length > 0" class="space-y-2 mt-2">
              <p class="font-medium text-sm">ไฟล์ที่เลือก ({{ selectedFiles.length }}/20)</p>
              <ul class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700 border border-slate-200 dark:border-slate-700 rounded-md">
                <li v-for="(file, idx) in selectedFiles" :key="idx" class="flex items-center justify-between p-2 text-sm">
                  <span class="truncate pr-2">{{ file.name }}</span>
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-slate-500">{{ formatFileSize(file.size) }}</span>
                    <button
                      @click="removeSelectedFile(idx)"
                      class="min-h-[44px] p-2 text-red-500 hover:bg-red-50 rounded-md"
                      :disabled="isUploading"
                    >
                      <Icon icon="fluent:dismiss-16-regular" />
                    </button>
                  </div>
                </li>
              </ul>
              <div class="flex justify-end gap-2 pt-2">
                <button
                  @click="selectedFiles = []"
                  class="min-h-[44px] px-4 py-2 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50"
                  :disabled="isUploading"
                >
                  ล้างทั้งหมด
                </button>
                <button
                  @click="submitUploadPhotos"
                  class="min-h-[44px] px-4 py-2 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg disabled:opacity-50"
                  :disabled="isUploading"
                >
                  <span v-if="isUploading">กำลังอัปโหลด...</span>
                  <span v-else>อัปโหลด {{ selectedFiles.length }} ไฟล์</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- กริดรูปภาพ -->
        <div v-if="photos.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
          <!--
            ใช้ div + role="button" ไม่ใช่ <button> จริง เพราะข้างในมีปุ่ม "ตั้งเป็นปก"/"ลบ" ซ้อนอยู่
            <button> ซ้อน <button> เป็น HTML ที่ไม่ถูกต้อง เบราว์เซอร์จะจัดการ event ไม่แน่นอน
          -->
          <div
            v-for="photo in photos"
            :key="photo.id"
            role="button"
            tabindex="0"
            class="group relative block aspect-square cursor-pointer overflow-hidden rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900"
            @click="openLightbox(photo)"
            @keydown.enter="openLightbox(photo)"
            @keydown.space.prevent="openLightbox(photo)"
          >
            <img
              :src="photo.thumbnail_url"
              class="h-full w-full object-cover"
              loading="lazy"
              alt=""
            />
            
            <div v-if="activeAlbum.cover_photo_id === photo.id" class="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-md">
              ปกอัลบั้ม
            </div>
            
            <div v-if="canManage" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-2 flex justify-between items-end gap-2" @click.stop>
              <button
                v-if="activeAlbum.cover_photo_id !== photo.id"
                @click.stop="setAsCover(photo)"
                class="min-h-[44px] px-2 py-1 text-white text-xs hover:bg-white/20 rounded backdrop-blur-sm"
              >
                ตั้งเป็นปก
              </button>
              <div v-else class="flex-1"></div>
              
              <button
                @click.stop="deletePhotoPrompt(photo)"
                class="min-h-[44px] p-2 text-white hover:text-red-400 rounded backdrop-blur-sm"
                title="ลบรูปภาพ"
              >
                <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
        
        <div v-else-if="!isLoadingPhotos" class="text-center py-10 bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700">
          <Icon icon="fluent:image-multiple-24-regular" class="w-12 h-12 mx-auto text-slate-300" />
          <p class="mt-3 text-slate-500">ยังไม่มีรูปในอัลบั้มนี้</p>
        </div>
      </div>
    </template>

    <!-- Lightbox -->
    <Teleport to="body">
      <div
        v-if="lightbox.photo"
        class="fixed inset-0 z-50 bg-black/90 flex flex-col"
        @keydown.esc="closeLightbox"
        tabindex="0"
        ref="lightboxRef"
      >
        <!-- แถบด้านบน -->
        <div class="flex justify-end p-2 sm:p-4 shrink-0">
          <button
            @click="closeLightbox"
            class="min-h-[44px] min-w-[44px] flex items-center justify-center text-white/70 hover:text-white bg-black/50 rounded-full"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
          </button>
        </div>
        
        <!-- รูปภาพ -->
        <div class="flex-1 min-h-0 flex items-center justify-center p-4 relative">
          <img
            :src="lightbox.photo.url"
            class="max-h-[80vh] max-w-full object-contain"
            alt=""
          />
          
          <!-- ปุ่มซ้ายขวา -->
          <button
            v-if="hasPrevPhoto"
            @click="prevPhoto"
            class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-white bg-black/50 hover:bg-black/80 rounded-full"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-6 h-6" />
          </button>
          
          <button
            v-if="hasNextPhoto"
            @click="nextPhoto"
            class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-white bg-black/50 hover:bg-black/80 rounded-full"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-6 h-6" />
          </button>
        </div>
        
        <!-- คำบรรยาย & แก้ไข -->
        <div class="p-4 bg-black/80 text-white min-h-[100px] shrink-0">
          <div class="max-w-3xl mx-auto flex flex-col gap-3">
            <template v-if="canManage">
              <div class="flex flex-col sm:flex-row gap-2">
                <input
                  v-model="lightboxCaption"
                  type="text"
                  placeholder="เพิ่มคำบรรยาย..."
                  class="min-w-0 flex-1 min-h-[44px] px-3 py-2 bg-white/10 border border-white/20 rounded-md text-white placeholder-white/50 focus:bg-white/20 focus:outline-none"
                />
                <button
                  @click="saveCaption"
                  :disabled="isSavingCaption"
                  class="min-h-[44px] px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50 flex-shrink-0"
                >
                  บันทึก
                </button>
              </div>
            </template>
            <template v-else>
              <p class="text-center text-lg break-words">{{ lightbox.photo.caption || 'ไม่มีคำบรรยาย' }}</p>
            </template>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import type { SportsDiscipline } from '~/composables/useSportsScoring'
import { useSportsAlbums } from '~/composables/useSportsAlbums'
import type { SportsAlbum, SportsPhoto } from '~/composables/useSportsAlbums'

const props = defineProps<{
  academyId: number
  editionId: number
  disciplines: SportsDiscipline[]
  houses: { id: number; name: string; color: string }[]
  canManage: boolean
}>()

const albumsApi = useSportsAlbums()
const { formatFileSize } = albumsApi

// State: โหมด A
const albums = ref<SportsAlbum[]>([])
const isLoading = ref(false)
const showCreateForm = ref(false)
const formAlbum = ref({
  name: '',
  description: '',
  discipline_id: null as number | null,
  house_group_id: null as number | null,
  is_public: true,
})
const isSubmitting = ref(false)

// State: โหมด B
const activeAlbum = ref<SportsAlbum | null>(null)
const photos = ref<SportsPhoto[]>([])
const isLoadingPhotos = ref(false)
const selectedFiles = ref<File[]>([])
const isUploading = ref(false)
const uploadError = ref('')

// State: Lightbox
const lightbox = ref<{ photo: SportsPhoto | null; index: number }>({ photo: null, index: -1 })
const lightboxRef = ref<HTMLElement | null>(null)
const lightboxCaption = ref('')
const isSavingCaption = ref(false)

// โหลดข้อมูล
const loadAlbums = async () => {
  isLoading.value = true
  try {
    const res = await albumsApi.listAlbums(props.academyId, props.editionId)
    albums.value = res || []
  } catch (e: any) {
    console.error('Failed to load albums', e)
  } finally {
    isLoading.value = false
  }
}

// Watch props เพื่อโหลดข้อมูลใหม่
watch(() => [props.academyId, props.editionId], () => {
  loadAlbums()
  activeAlbum.value = null
}, { immediate: true })

// Helper หารายละเอียด
const getDisciplineName = (id: number) => props.disciplines.find(d => d.id === id)?.name || ''
const getHouseName = (id: number) => props.houses.find(h => h.id === id)?.name || ''

// การจัดการอัลบั้ม
const submitCreateAlbum = async () => {
  if (!formAlbum.value.name) return
  isSubmitting.value = true
  try {
    await albumsApi.createAlbum(props.academyId, props.editionId, formAlbum.value)
    showCreateForm.value = false
    formAlbum.value = { name: '', description: '', discipline_id: null, house_group_id: null, is_public: true }
    await loadAlbums()
  } catch (e: any) {
    alert(e?.data?.message || 'เกิดข้อผิดพลาดในการสร้างอัลบั้ม')
  } finally {
    isSubmitting.value = false
  }
}

const deleteAlbumPrompt = async (album: SportsAlbum) => {
  if (confirm('ยืนยันการลบอัลบั้ม?\nลบอัลบั้มแล้วรูปทั้งหมดในอัลบั้มจะถูกลบถาวรด้วย')) {
    try {
      await albumsApi.deleteAlbum(props.academyId, props.editionId, album.id)
      await loadAlbums()
    } catch (e: any) {
      alert(e?.data?.message || 'เกิดข้อผิดพลาดในการลบอัลบั้ม')
    }
  }
}

// การเข้าสู่โหมด B
const openAlbum = async (album: SportsAlbum) => {
  activeAlbum.value = album
  isLoadingPhotos.value = true
  photos.value = []
  selectedFiles.value = []
  uploadError.value = ''
  
  try {
    const res = await albumsApi.listPhotos(props.academyId, props.editionId, album.id)
    photos.value = res || []
  } catch (e: any) {
    console.error('Failed to load photos', e)
  } finally {
    isLoadingPhotos.value = false
  }
}

const closeAlbum = () => {
  activeAlbum.value = null
  photos.value = []
  // รีเฟรชอัลบั้มเผื่อจำนวนรูปหรือปกเปลี่ยน
  loadAlbums()
}

// การอัปโหลด
const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files?.length) return
  
  uploadError.value = ''
  const newFiles = Array.from(target.files)
  
  // ตรวจสอบ
  let hasError = false
  const validFiles: File[] = []
  
  for (const file of newFiles) {
    if (file.size > 8 * 1024 * 1024) { // 8 MB
      uploadError.value = `ขนาดไฟล์เกิน 8 MB (${file.name})`
      hasError = true
      break
    }
    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type)) {
      uploadError.value = `รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, WEBP (${file.name})`
      hasError = true
      break
    }
    validFiles.push(file)
  }
  
  if (!hasError) {
    const combined = [...selectedFiles.value, ...validFiles]
    if (combined.length > 20) {
      uploadError.value = 'เลือกไฟล์ได้สูงสุดครั้งละ 20 ไฟล์'
    } else {
      selectedFiles.value = combined
    }
  }
  
  target.value = '' // reset
}

const removeSelectedFile = (idx: number) => {
  selectedFiles.value.splice(idx, 1)
}

const submitUploadPhotos = async () => {
  if (!activeAlbum.value || selectedFiles.value.length === 0) return
  isUploading.value = true
  uploadError.value = ''
  
  try {
    await albumsApi.uploadPhotos(
      props.academyId,
      props.editionId,
      activeAlbum.value.id,
      selectedFiles.value
    )
    selectedFiles.value = []
    
    // โหลดรูปใหม่
    const res = await albumsApi.listPhotos(props.academyId, props.editionId, activeAlbum.value.id)
    photos.value = res || []
  } catch (e: any) {
    uploadError.value = e?.data?.message || 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ'
  } finally {
    isUploading.value = false
  }
}

// การจัดการรูปในอัลบั้ม
const setAsCover = async (photo: SportsPhoto) => {
  if (!activeAlbum.value) return
  try {
    await albumsApi.updateAlbum(props.academyId, props.editionId, activeAlbum.value.id, {
      cover_photo_id: photo.id
    })
    activeAlbum.value.cover_photo_id = photo.id
  } catch (e: any) {
    alert(e?.data?.message || 'เกิดข้อผิดพลาดในการตั้งหน้าปก')
  }
}

const deletePhotoPrompt = async (photo: SportsPhoto) => {
  if (!activeAlbum.value) return
  if (confirm('ยืนยันการลบรูปภาพนี้?')) {
    try {
      await albumsApi.deletePhoto(props.academyId, props.editionId, photo.id)
      photos.value = photos.value.filter(p => p.id !== photo.id)
      
      if (activeAlbum.value.cover_photo_id === photo.id) {
        activeAlbum.value.cover_photo_id = null
      }
    } catch (e: any) {
      alert(e?.data?.message || 'เกิดข้อผิดพลาดในการลบรูปภาพ')
    }
  }
}

// Lightbox
const openLightbox = (photo: SportsPhoto) => {
  const idx = photos.value.findIndex(p => p.id === photo.id)
  lightbox.value = { photo, index: idx }
  lightboxCaption.value = photo.caption || ''
  
  document.body.style.overflow = 'hidden'
  
  nextTick(() => {
    lightboxRef.value?.focus()
  })
}

const closeLightbox = () => {
  lightbox.value = { photo: null, index: -1 }
  document.body.style.overflow = ''
}

onUnmounted(() => {
  document.body.style.overflow = ''
})

const hasPrevPhoto = computed(() => lightbox.value.index > 0)
const hasNextPhoto = computed(() => lightbox.value.index < photos.value.length - 1 && lightbox.value.index >= 0)

const prevPhoto = () => {
  if (hasPrevPhoto.value) {
    const nextIdx = lightbox.value.index - 1
    const p = photos.value[nextIdx]
    lightbox.value = { photo: p, index: nextIdx }
    lightboxCaption.value = p.caption || ''
  }
}

const nextPhoto = () => {
  if (hasNextPhoto.value) {
    const nextIdx = lightbox.value.index + 1
    const p = photos.value[nextIdx]
    lightbox.value = { photo: p, index: nextIdx }
    lightboxCaption.value = p.caption || ''
  }
}

const saveCaption = async () => {
  if (!lightbox.value.photo) return
  isSavingCaption.value = true
  try {
    await albumsApi.updatePhoto(
      props.academyId,
      props.editionId,
      lightbox.value.photo.id,
      { caption: lightboxCaption.value }
    )
    lightbox.value.photo.caption = lightboxCaption.value
    // อัปเดตในอาร์เรย์ด้วย
    const pInArray = photos.value.find(p => p.id === lightbox.value.photo!.id)
    if (pInArray) {
      pInArray.caption = lightboxCaption.value
    }
  } catch (e: any) {
    alert(e?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกคำบรรยาย')
  } finally {
    isSavingCaption.value = false
  }
}
</script>
