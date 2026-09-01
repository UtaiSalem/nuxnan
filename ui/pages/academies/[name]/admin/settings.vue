<script setup lang="ts">
/**
 * Academy Admin - Settings
 * หน้าตั้งค่าโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { onBeforeRouteLeave } from 'vue-router'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const activeTab = ref('general')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isOwner, fetchMyRole } = useAcademyRole(academyId)
const canManage = computed(() => isOwner.value || can('settings.manage'))
const isReadOnly = computed(() => ! canManage.value)

// Form
const form = ref({
  name: '',
  name_en: '',
  description: '',
  description_en: '',
  slogan: '',
  type: '' as string,
  established_year: '' as string | number,
  director: '' as string | number,
  social_media_links: {} as Record<string, string>,
  email: '',
  phone: '',
  website: '',
  address: '',
  province: '',
  country: 'Thailand',
  privacy: 'public' as 'public' | 'private',
  join_mode: 'open' as 'open' | 'approval' | 'invite_only',
  show_member_list: true,
  show_course_list: true,
  card_request_flow_enabled: true,
  donation_enabled: true,
  student_editable_fields: {
    mode: 'blacklist' as 'blacklist' | 'whitelist',
    fields: [] as string[],
  },
})

// SET-S6 — แคตตาล็อกมาจาก backend (Academy::STUDENT_EDITABLE_FIELD_CATALOG) ห้าม hardcode ซ้ำ
const editableFieldCatalog = ref<string[]>([])

const academyTypeCatalog = ref<string[]>([])
const socialLinkCatalog = ref<string[]>([])

const ACADEMY_TYPE_LABELS: Record<string, string> = {
  public: 'รัฐบาล',
  private: 'เอกชน',
  foundation: 'มูลนิธิ',
  international: 'นานาชาติ',
}

const SOCIAL_LABELS: Record<string, { label: string; icon: string; placeholder: string }> = {
  facebook:  { label: 'Facebook',  icon: 'fluent:share-24-regular', placeholder: 'https://facebook.com/...' },
  line:      { label: 'LINE',      icon: 'fluent:chat-24-regular',  placeholder: 'https://line.me/R/ti/p/@...' },
  youtube:   { label: 'YouTube',   icon: 'fluent:video-24-regular', placeholder: 'https://youtube.com/@...' },
  tiktok:    { label: 'TikTok',    icon: 'fluent:music-note-2-24-regular', placeholder: 'https://tiktok.com/@...' },
  instagram: { label: 'Instagram', icon: 'fluent:camera-24-regular', placeholder: 'https://instagram.com/...' },
  x:         { label: 'X (Twitter)', icon: 'fluent:globe-24-regular', placeholder: 'https://x.com/...' },
}

const directorQuery = ref('')
const directorResults = ref<any[]>([])
const isSearchingDirector = ref(false)
const selectedDirector = ref<any>(null)
let directorSearchTimer: any = null

const searchDirector = () => {
  clearTimeout(directorSearchTimer)
  const term = directorQuery.value.trim()
  if (term.length < 2) { directorResults.value = []; return }
  directorSearchTimer = setTimeout(async () => {
    if (! academyId.value) return
    isSearchingDirector.value = true
    try {
      const res: any = await api.get(
        `/api/academies/${academyId.value}/members/search?search=${encodeURIComponent(term)}&status=2&per_page=10`
      )
      directorResults.value = (res?.members ?? []).filter((m: any) => m?.user?.id)
    } catch (e) {
      directorResults.value = []
    } finally {
      isSearchingDirector.value = false
    }
  }, 350)
}

const pickDirector = (member: any) => {
  // AcademyMemberResource ส่งรูปมาในคีย์ profile_photo_url (คนละคีย์กับ UserResource ที่ใช้คำว่า avatar)
  selectedDirector.value = { id: member.user.id, name: member.user.name, avatar: member.user.profile_photo_url ?? member.user.avatar ?? null }
  form.value.director = member.user.id
  directorQuery.value = ''
  directorResults.value = []
}

const clearDirector = () => {
  selectedDirector.value = null
  form.value.director = ''
}

const maxEstablishedYear = computed(() => new Date().getFullYear() + 543)

const FIELD_LABELS: Record<string, string> = {
  academic: 'ข้อมูลการศึกษา (ทั้งกลุ่ม)',
  health: 'ข้อมูลสุขภาพ (ทั้งกลุ่ม)',
  address: 'ที่อยู่ (ทั้งกลุ่ม)',
  contact: 'ข้อมูลติดต่อ (ทั้งกลุ่ม)',
  guardian: 'ผู้ปกครอง (ทั้งกลุ่ม)',
  citizen_id: 'เลขบัตรประชาชน',
  student_id: 'รหัสนักเรียน',
  gender: 'เพศ',
  date_of_birth: 'วันเกิด',
  title_prefix_th: 'คำนำหน้า (ไทย)',
  title_prefix_en: 'คำนำหน้า (อังกฤษ)',
  first_name_th: 'ชื่อ (ไทย)',
  first_name_en: 'ชื่อ (อังกฤษ)',
  last_name_th: 'นามสกุล (ไทย)',
  last_name_en: 'นามสกุล (อังกฤษ)',
  nickname: 'ชื่อเล่น',
  nationality: 'สัญชาติ',
  religion: 'ศาสนา',
  profile_image: 'รูปโปรไฟล์',
}

const fieldLabel = (key: string) => FIELD_LABELS[key] || key

// blacklist ว่าง = ไม่มีอะไรต้องขออนุมัติเลย · whitelist ว่าง = ต้องขออนุมัติทุกอย่าง
const editableFieldsWarning = computed(() => {
  const { mode, fields } = form.value.student_editable_fields
  if (mode === 'blacklist' && fields.length === 0) {
    return 'ตอนนี้ไม่มีฟิลด์ไหนต้องขออนุมัติเลย — นักเรียนแก้ข้อมูลตัวเองได้ทุกอย่างทันที รวมถึงชื่อและเลขบัตรประชาชน'
  }
  if (mode === 'whitelist' && fields.length === 0) {
    return 'ตอนนี้นักเรียนแก้อะไรเองไม่ได้เลย ทุกการแก้ไขจะกลายเป็นคำขอรออนุมัติทั้งหมด'
  }
  return ''
})

const toggleEditableField = (key: string) => {
  const list = form.value.student_editable_fields.fields
  const i = list.indexOf(key)
  if (i === -1) list.push(key)
  else list.splice(i, 1)
}

// Avatar/Cover
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const coverFile = ref<File | null>(null)
const coverPreview = ref<string | null>(null)

const tabs = computed(() => {
  const base = [
    { id: 'general', label: 'ข้อมูลทั่วไป', icon: 'fluent:building-24-regular' },
    { id: 'contact', label: 'ข้อมูลติดต่อ', icon: 'fluent:mail-24-regular' },
    { id: 'privacy', label: 'ความเป็นส่วนตัว', icon: 'fluent:lock-closed-24-regular' },
    { id: 'registration', label: 'การลงทะเบียน', icon: 'fluent:person-add-24-regular' },
    { id: 'system', label: 'ระบบและนโยบาย', icon: 'fluent:settings-24-regular' },
  ]

  if (isOwner.value) {
    base.push({ id: 'danger', label: 'โซนอันตราย', icon: 'fluent:warning-24-regular' })
  }

  return base
})

watch(tabs, (list) => {
  if (! list.some(tab => tab.id === activeTab.value)) {
    activeTab.value = list[0]?.id ?? 'general'
  }
}, { immediate: true })

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('settings.view') && !canManage.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      // Populate form
      populateForm()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// SET-S11 — สแนปช็อตแบบ canonical: เรียงคีย์ + ตัดลิงก์โซเชียลที่ว่างทิ้ง
// ถ้าไม่ตัด ผู้ใช้ที่พิมพ์แล้วลบจนว่างจะถูกนับว่า "มีการแก้ไข" ทั้งที่ค่าเท่าเดิม
const formFingerprint = () => {
  const data = JSON.parse(JSON.stringify(form.value))
  
  if (data.social_media_links) {
    for (const key of Object.keys(data.social_media_links)) {
      if (!data.social_media_links[key] || data.social_media_links[key].trim() === '') {
        delete data.social_media_links[key]
      }
    }
  }
  
  if (data.student_editable_fields?.fields) {
    data.student_editable_fields.fields.sort()
  }
  
  const sortKeys = (obj: any): any => {
    if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return obj
    const sorted: any = {}
    Object.keys(obj).sort().forEach(k => {
      sorted[k] = sortKeys(obj[k])
    })
    return sorted
  }
  
  return JSON.stringify(sortKeys(data))
}

const pristine = ref('')

const isDirty = computed(() =>
  ! isSaving.value
  && (formFingerprint() !== pristine.value || avatarFile.value !== null || coverFile.value !== null)
)

onBeforeRouteLeave((to, from, next) => {
  if (isDirty.value) {
    const confirmed = window.confirm('คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')
    confirmed ? next() : next(false)
  } else {
    next()
  }
})

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload)
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

const populateForm = () => {
  if (!academy.value) return
  
  form.value = {
    name: academy.value.name || '',
    name_en: academy.value.name_en || '',
    description: academy.value.description || '',
    description_en: academy.value.description_en || '',
    slogan: academy.value.slogan || '',
    type: academy.value.type || '',
    established_year: academy.value.established_year ?? '',
    director: academy.value.director?.id ?? '',
    social_media_links: { ...(academy.value.social_media_links ?? {}) },
    email: academy.value.email || '',
    phone: academy.value.phone || '',
    website: academy.value.website || '',
    address: academy.value.address || '',
    province: academy.value.province || '',
    country: academy.value.country || 'Thailand',
    privacy: academy.value.privacy || 'public',
    join_mode: academy.value.join_mode || 'open',
    show_member_list: academy.value.show_member_list ?? true,
    show_course_list: academy.value.show_course_list ?? true,
    card_request_flow_enabled: academy.value.setting?.card_request_flow_enabled ?? false,
    donation_enabled: academy.value.donation_enabled ?? true,
    student_editable_fields: {
      mode: academy.value.student_editable_fields?.mode ?? 'blacklist',
      fields: [...(academy.value.student_editable_fields?.fields ?? [])],
    },
  }
  
  editableFieldCatalog.value = academy.value.student_editable_field_catalog ?? []
  academyTypeCatalog.value = academy.value.academy_type_catalog ?? []
  socialLinkCatalog.value = academy.value.social_link_catalog ?? []
  selectedDirector.value = academy.value.director?.id
    ? { id: academy.value.director.id, name: academy.value.director.name, avatar: academy.value.director.avatar ?? null }
    : null

  avatarPreview.value = academy.value.logo_url || academy.value.logo
  coverPreview.value = academy.value.cover_url || academy.value.cover
  pristine.value = formFingerprint()
}

const onAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    avatarFile.value = target.files[0]
    avatarPreview.value = URL.createObjectURL(target.files[0])
  }
}

const onCoverChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    coverFile.value = target.files[0]
    coverPreview.value = URL.createObjectURL(target.files[0])
  }
}

const saveSettings = async () => {
  if (! canManage.value) return
  if (!academyId.value) return
  
  isSaving.value = true
  try {
    const formData = new FormData()
    
    // Add form fields
    // SET-S6 — student_editable_fields เป็นอ็อบเจกต์ `String(value)` จะได้ "[object Object]"
    // ต้องส่งเป็นคีย์ซ้อนแบบที่ Laravel แปลงกลับเป็นอาเรย์ได้
    Object.entries(form.value).forEach(([key, value]) => {
      if (key === 'student_editable_fields') return
      // SET-S7 — social_media_links เป็นอ็อบเจกต์ String(value) จะได้ "[object Object]"
      if (key === 'social_media_links') return
      // multipart ส่งได้แต่สตริง และกฎ `boolean` ของ Laravel ไม่รับ "true"/"false" — รับแค่ "1"/"0"
      // (สวิตช์เดิมอย่าง show_member_list รอดมาได้เพราะไม่มีกฎ validate คุมอยู่เลย
      //  อ่านผ่าน $request->boolean() ตรง ๆ ซึ่งรับสตริง "true" ได้)
      formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value))
    })

    formData.append('student_editable_fields[mode]', form.value.student_editable_fields.mode)
    form.value.student_editable_fields.fields.forEach((field) => {
      formData.append('student_editable_fields[fields][]', field)
    })

    // SET-S7 — ส่งทุกคีย์ในแคตตาล็อกเสมอ (ช่องที่ถูกล้างต้องส่งค่าว่างไปด้วย
    // ไม่งั้น backend จะไม่รู้ว่าผู้ดูแลตั้งใจลบลิงก์นั้นทิ้ง)
    socialLinkCatalog.value.forEach((key) => {
      formData.append(`social_media_links[${key}]`, (form.value.social_media_links?.[key] ?? '').trim())
    })
    
    // Add files if changed
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    if (coverFile.value) {
      formData.append('cover', coverFile.value)
    }

    const response: any = await api.post(`/api/academies/${academyId.value}/settings`, formData)
    
    if (response.success) {
      Swal.fire('สำเร็จ', 'บันทึกการตั้งค่าเรียบร้อยแล้ว', 'success')
      
      // SET-S11 — เขียนค่าที่เซิร์ฟเวอร์ normalize แล้วกลับลงฟอร์ม (G12)
      // เดิมใช้ response.academy แค่เช็คชื่อ ⇒ สิ่งที่ผู้ใช้เห็นค้างที่ค่าที่ตัวเองพิมพ์
      // ต่างจากของจริงหลายจุด: social_media_links ถูกตัดคีย์ว่างทิ้ง · student_editable_fields
      // ถูก normalize รูปทรง · established_year เก็บเป็น int · donation_enabled เป็น boolean เสมอ
      if (response.academy) {
        academy.value = response.academy
        avatarFile.value = null
        coverFile.value = null
        populateForm()          // รีเซ็ตสแนปช็อตของ SET-S11 ไปในตัว
      }

      // SET-S8 — route param [name] ผูกกับคอลัมน์ `name` ไม่ใช่ slug (คอลัมน์ name_slug ถูกลบทิ้งแล้ว)
      // ถ้าไม่ย้าย URL ตามชื่อใหม่ การ refresh ครั้งถัดไปจะได้ 404 เพราะ URL ยังค้างที่ชื่อเก่า
      if (response.academy?.name && response.academy.name !== academyName.value) {
        navigateTo(`/academies/${encodeURIComponent(response.academy.name)}/admin/settings`)
      }
    }
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถบันทึกได้', 'error')
  } finally {
    isSaving.value = false
  }
}

const isArchived = computed(() => academy.value?.is_archived === true)
const isArchiving = ref(false)

const confirmArchiveAcademy = async () => {
  const result = await Swal.fire({
    title: 'เก็บถาวรโรงเรียน?',
    text: 'โรงเรียนจะหายจากไดเรกทอรีและการค้นหา สมาชิกภายนอกจะเข้าดูไม่ได้ — แต่ข้อมูลทั้งหมดยังอยู่ครบและกู้คืนได้ทุกเมื่อ',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'เก็บถาวร',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444',
    input: 'text',
    inputLabel: `พิมพ์ "${academy.value?.name}" เพื่อยืนยัน`,
    inputPlaceholder: academy.value?.name,
    preConfirm: (value) => {
      if (value !== academy.value?.name) {
        Swal.showValidationMessage('ชื่อไม่ตรงกัน')
      }
    }
  })

  if (! result.isConfirmed) return

  isArchiving.value = true
  try {
    await api.post(`/api/academies/${academyId.value}/archive`, {})
    await Swal.fire('สำเร็จ', 'เก็บถาวรโรงเรียนเรียบร้อยแล้ว', 'success')
    navigateTo('/academies?view=archived')
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถเก็บถาวรได้', 'error')
  } finally {
    isArchiving.value = false
  }
}

const confirmRestoreAcademy = async () => {
  const result = await Swal.fire({
    title: 'กู้คืนโรงเรียน?',
    text: 'โรงเรียนจะกลับมาแสดงในไดเรกทอรีและการค้นหาตามปกติ',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'กู้คืน',
    cancelButtonText: 'ยกเลิก',
  })

  if (! result.isConfirmed) return

  isArchiving.value = true
  try {
    await api.delete(`/api/academies/${academyId.value}/archive`)
    await Swal.fire('สำเร็จ', 'กู้คืนโรงเรียนเรียบร้อยแล้ว', 'success')
    window.location.reload()
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถกู้คืนได้', 'error')
  } finally {
    isArchiving.value = false
  }
}

const privacyOptions = [
  { value: 'public', label: 'สาธารณะ', description: 'ทุกคนสามารถเห็นและเข้าถึงได้' },
  { value: 'private', label: 'ส่วนตัว', description: 'เฉพาะสมาชิกเท่านั้นที่สามารถเข้าถึงได้' },
]

const joinModeOptions = [
  { value: 'open', label: 'เปิดรับสมัคร', description: 'ทุกคนสามารถสมัครเป็นสมาชิกได้ทันที' },
  { value: 'approval', label: 'ต้องอนุมัติ', description: 'ต้องรอการอนุมัติจากผู้ดูแล' },
  { value: 'invite_only', label: 'เชิญเท่านั้น', description: 'เฉพาะผู้ที่ได้รับเชิญเท่านั้น' },
]
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่าโรงเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลและการตั้งค่าของโรงเรียน</p>
        </div>
        <button 
          v-if="canManage"
          @click="saveSettings"
          :disabled="isSaving"
          class="min-h-[44px] sm:min-h-0 px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2 disabled:opacity-50"
        >
          <span v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
          <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
          บันทึก
        </button>
      </div>

      <div
        v-if="isReadOnly"
        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20"
      >
        <Icon icon="fluent:eye-24-regular" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
        <p class="min-w-0 flex-1 break-words text-sm text-amber-800 dark:text-amber-300">
          คุณกำลังดูการตั้งค่าในโหมดอ่านอย่างเดียว — ต้องมีสิทธิ์ "แก้ไขการตั้งค่าโรงเรียน" จึงจะแก้ไขได้
        </p>
      </div>

      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Tabs Sidebar -->
        <div class="lg:w-56 shrink-0">
          <nav class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                activeTab === tab.id
                  ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 border-l-4 border-primary-500'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-4 border-transparent',
                tab.id === 'danger' && 'text-red-600 dark:text-red-400'
              ]"
            >
              <Icon :icon="tab.icon" class="w-5 h-5" />
              <span class="text-sm font-medium">{{ tab.label }}</span>
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="flex-1">
          <!-- General Tab -->
          <div v-if="activeTab === 'general'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ข้อมูลทั่วไป</h2>

            <!-- Cover & Avatar -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปปก</label>
                <div class="relative h-32 bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden">
                  <img 
                    v-if="coverPreview" 
                    :src="coverPreview" 
                    class="w-full h-full object-cover"
                  />
                  <label v-if="canManage" class="absolute inset-0 flex items-center justify-center cursor-pointer hover:bg-black/20 transition-colors">
                    <Icon icon="fluent:camera-24-regular" class="w-8 h-8 text-gray-400" />
                    <input type="file" accept="image/*" @change="onCoverChange" class="hidden" />
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปโปรไฟล์</label>
                <div class="flex items-center gap-4">
                  <div class="relative w-20 h-20">
                    <img 
                      :src="avatarPreview || '/images/default-academy.png'"
                      class="w-20 h-20 rounded-xl object-cover"
                    />
                    <label v-if="canManage" class="absolute inset-0 flex items-center justify-center cursor-pointer rounded-xl hover:bg-black/20 transition-colors">
                      <Icon icon="fluent:camera-24-regular" class="w-6 h-6 text-white/80" />
                      <input type="file" accept="image/*" @change="onAvatarChange" class="hidden" />
                    </label>
                  </div>
                  <p class="text-sm text-gray-500">รูปภาพขนาด 200x200 พิกเซลขึ้นไป</p>
                </div>
              </div>
            </div>

            <!-- Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อโรงเรียน (ภาษาไทย) *
                </label>
                <input
                  v-model="form.name"
                  :disabled="isReadOnly"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อโรงเรียน (ภาษาอังกฤษ)
                </label>
                <input
                  v-model="form.name_en"
                  :disabled="isReadOnly"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
              </div>
            </div>

            <!-- Description -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  คำอธิบาย (ภาษาไทย)
                </label>
                <textarea
                  v-model="form.description"
                  :disabled="isReadOnly"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                  placeholder="อธิบายเกี่ยวกับโรงเรียน..."
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  คำอธิบาย (ภาษาอังกฤษ)
                </label>
                <textarea
                  v-model="form.description_en"
                  :disabled="isReadOnly"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                  placeholder="Describe your academy..."
                />
              </div>
            </div>

            <!-- Slogan -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                คำขวัญโรงเรียน
              </label>
              <input
                v-model="form.slogan"
                :disabled="isReadOnly"
                type="text"
                maxlength="255"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
              />
              <p class="text-sm text-gray-500 mt-1">แสดงบนการ์ดโรงเรียนและหน้าโปรไฟล์</p>
            </div>

            <!-- Type and Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ประเภทโรงเรียน
                </label>
                <select
                  v-model="form.type"
                  :disabled="isReadOnly"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <option value="">ไม่ระบุ</option>
                  <option v-for="t in academyTypeCatalog" :key="t" :value="t">{{ ACADEMY_TYPE_LABELS[t] || t }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ปีที่ก่อตั้ง
                </label>
                <input
                  v-model="form.established_year"
                  :disabled="isReadOnly"
                  type="number"
                  min="2400"
                  :max="maxEstablishedYear"
                  placeholder="เช่น 2510"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
                <p class="text-sm text-gray-500 mt-1">ระบุเป็น พ.ศ.</p>
              </div>
            </div>

            <!-- Director -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ผู้อำนวยการโรงเรียน
              </label>
              
              <div v-if="selectedDirector" class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700">
                <img v-if="selectedDirector.avatar" :src="selectedDirector.avatar" class="w-8 h-8 rounded-full mr-3 object-cover" />
                <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 mr-3 flex items-center justify-center shrink-0">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                </div>
                <div class="min-w-0 flex-1 break-words">
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDirector.name }}</span>
                </div>
                <button
                  v-if="!isReadOnly"
                  @click="clearDirector"
                  type="button"
                  class="flex-shrink-0 min-h-[44px] px-3 text-red-500 hover:text-red-700 transition-colors flex items-center"
                >
                  <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 mr-1" />
                  เอาออก
                </button>
              </div>

              <div v-else class="relative">
                <input
                  v-model="directorQuery"
                  @input="searchDirector"
                  :disabled="isReadOnly"
                  type="text"
                  placeholder="พิมพ์ชื่อสมาชิกอย่างน้อย 2 ตัวอักษร"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
                
                <ul v-if="directorResults.length || isSearchingDirector" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                  <li v-if="isSearchingDirector" class="p-4 text-center text-gray-500">
                    กำลังค้นหา...
                  </li>
                  <li v-else v-for="m in directorResults" :key="m.user.id">
                    <button
                      type="button"
                      @click="pickDirector(m)"
                      class="w-full text-left min-h-[44px] px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors"
                    >
                      <img v-if="m.user.profile_photo_url" :src="m.user.profile_photo_url" class="w-8 h-8 rounded-full object-cover" />
                      <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0">
                        <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                      </div>
                      <div class="min-w-0 flex-1 overflow-hidden">
                        <div class="truncate text-gray-900 dark:text-white">{{ m.user.name }}</div>
                        <div v-if="m.user.email" class="truncate text-sm text-gray-500">{{ m.user.email }}</div>
                      </div>
                    </button>
                  </li>
                </ul>
              </div>
              <p class="text-sm text-gray-500 mt-1">เลือกได้เฉพาะสมาชิกของโรงเรียนที่ได้รับอนุมัติแล้ว</p>
            </div>

            <!-- Social Media Channels -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-white mb-1">ช่องทางโซเชียล</h3>
              <p class="text-sm text-gray-500 mb-4">ลิงก์ที่กรอกจะแสดงบนหน้าโรงเรียน</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="key in socialLinkCatalog" :key="key">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1">
                    <Icon :icon="SOCIAL_LABELS[key]?.icon || 'fluent:link-24-regular'" class="w-4 h-4" />
                    {{ SOCIAL_LABELS[key]?.label || key }}
                  </label>
                  <input
                    type="url"
                    :disabled="isReadOnly"
                    :placeholder="SOCIAL_LABELS[key]?.placeholder"
                    :value="form.social_media_links[key] || ''"
                    @input="form.social_media_links[key] = ($event.target as HTMLInputElement).value"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Contact Tab -->
          <div v-if="activeTab === 'contact'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ข้อมูลติดต่อ</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล</label>
                <div class="relative">
                  <Icon icon="fluent:mail-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="form.email"
                    :disabled="isReadOnly"
                    type="email"
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                    placeholder="academy@example.com"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โทรศัพท์</label>
                <div class="relative">
                  <Icon icon="fluent:call-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="form.phone"
                    :disabled="isReadOnly"
                    type="tel"
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                    placeholder="02-xxx-xxxx"
                  />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เว็บไซต์</label>
              <div class="relative">
                <Icon icon="fluent:globe-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="form.website"
                  :disabled="isReadOnly"
                  type="url"
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                  placeholder="https://www.example.com"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่</label>
              <textarea
                v-model="form.address"
                :disabled="isReadOnly"
                rows="2"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                placeholder="ที่อยู่โรงเรียน..."
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จังหวัด</label>
                <input
                  v-model="form.province"
                  :disabled="isReadOnly"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเทศ</label>
                <input
                  v-model="form.country"
                  :disabled="isReadOnly"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                />
              </div>
            </div>
          </div>

          <!-- Privacy Tab -->
          <div v-if="activeTab === 'privacy'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ความเป็นส่วนตัว</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ระดับความเป็นส่วนตัว</label>
              <div class="space-y-3">
                <label 
                  v-for="opt in privacyOptions"
                  :key="opt.value"
                  class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors"
                  :class="form.privacy === opt.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                >
                  <input
                    type="radio"
                    v-model="form.privacy"
                    :disabled="isReadOnly"
                    :value="opt.value"
                    class="mt-1 text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ opt.label }}</p>
                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <div class="space-y-4">
              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">แสดงรายชื่อสมาชิก</p>
                  <p class="text-sm text-gray-500">อนุญาตให้ผู้อื่นดูรายชื่อสมาชิก</p>
                </div>
                <input type="checkbox" v-model="form.show_member_list" :disabled="isReadOnly" class="toggle" />
              </label>

              <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">แสดงรายการคอร์ส</p>
                  <p class="text-sm text-gray-500">อนุญาตให้ผู้อื่นดูรายการคอร์ส</p>
                </div>
                <input type="checkbox" v-model="form.show_course_list" :disabled="isReadOnly" class="toggle" />
              </label>
            </div>
            
            <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-3 sm:p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
              <Icon icon="fluent:info-24-regular" class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
              <p class="min-w-0 flex-1 break-words text-sm text-blue-800 dark:text-blue-200">
                สมาชิกที่อนุมัติแล้วและผู้ดูแลเห็นรายชื่อสมาชิกกับรายวิชาได้เสมอ สวิตช์สองตัวนี้คุมเฉพาะคนนอกโรงเรียน
                · ถ้าตั้งความเป็นส่วนตัวเป็น "ส่วนตัว" คนนอกจะเห็นแค่หน้าปกโรงเรียนกับปุ่มขอเข้าร่วมเท่านั้น
              </p>
            </div>
          </div>

          <!-- Registration Tab -->
          <div v-if="activeTab === 'registration'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">การลงทะเบียน</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">โหมดการเข้าร่วม</label>
              <div class="space-y-3">
                <label 
                  v-for="opt in joinModeOptions"
                  :key="opt.value"
                  class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors"
                  :class="form.join_mode === opt.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                >
                  <input
                    type="radio"
                    v-model="form.join_mode"
                    :disabled="isReadOnly"
                    :value="opt.value"
                    class="mt-1 text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ opt.label }}</p>
                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-3 sm:p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
              <Icon icon="fluent:info-24-regular" class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
              <p class="min-w-0 flex-1 break-words text-sm text-blue-800 dark:text-blue-200">
                โหมดการเข้าร่วมคุมทั้งหมดว่าใครเข้าโรงเรียนได้บ้าง — เลือก "เชิญเท่านั้น" แล้วปุ่มขอเข้าร่วม
                จะหายไปจากหน้าโรงเรียน และคำขอที่ยิงตรงเข้ามาจะถูกปฏิเสธ เข้าได้เฉพาะทางลิงก์เชิญหรือคำเชิญตรงเท่านั้น
              </p>
            </div>
          </div>

          <!-- System & Policy -->
          <div v-if="activeTab === 'system'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-gray-900 dark:text-white">ระบบและนโยบาย</h2>

            <div class="space-y-4">
              <label class="flex items-center justify-between gap-3 p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div class="min-w-0 flex-1">
                  <p class="font-medium text-gray-900 dark:text-white break-words">ระบบคำร้องทำบัตรนักเรียน</p>
                  <p class="text-sm text-gray-500 break-words">เปิดให้นักเรียนยื่นคำร้องขอทำบัตรผ่านระบบ ปิดแล้วเจ้าหน้าที่ออกบัตรให้เองอย่างเดียว</p>
                </div>
                <input type="checkbox" v-model="form.card_request_flow_enabled" :disabled="isReadOnly" class="toggle flex-shrink-0" />
              </label>

              <label class="flex items-center justify-between gap-3 p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div class="min-w-0 flex-1">
                  <p class="font-medium text-gray-900 dark:text-white break-words">เปิดรับบริจาคให้โรงเรียน</p>
                  <p class="text-sm text-gray-500 break-words">ปิดแล้วแผงรับบริจาคจะหายจากหน้าโรงเรียน และคำขอบริจาคที่ยิงตรงเข้ามาจะถูกปฏิเสธ</p>
                </div>
                <input type="checkbox" v-model="form.donation_enabled" :disabled="isReadOnly" class="toggle flex-shrink-0" />
              </label>
            </div>

            <div class="space-y-3 border-t border-gray-100 dark:border-gray-700 pt-6">
              <div>
                <p class="font-medium text-gray-900 dark:text-white break-words">ข้อมูลที่นักเรียนแก้เองได้</p>
                <p class="text-sm text-gray-500 break-words">
                  เลือกโหมดแล้วติ๊กรายการ — ฟิลด์ที่ "ต้องขออนุมัติ" เมื่อนักเรียนแก้จะกลายเป็นคำขอรอเจ้าหน้าที่อนุมัติแทนการบันทึกทันที
                </p>
              </div>

              <div class="flex flex-col gap-2 sm:flex-row">
                <label
                  v-for="opt in [
                    { value: 'blacklist', label: 'รายการที่ติ๊ก = ต้องขออนุมัติ' },
                    { value: 'whitelist', label: 'รายการที่ติ๊ก = แก้ได้เลย' },
                  ]"
                  :key="opt.value"
                  class="flex min-h-[44px] flex-1 items-center gap-3 rounded-xl border-2 p-3 cursor-pointer transition-colors sm:min-h-0"
                  :class="form.student_editable_fields.mode === opt.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600'"
                >
                  <input
                    type="radio"
                    v-model="form.student_editable_fields.mode"
                    :disabled="isReadOnly"
                    :value="opt.value"
                    class="flex-shrink-0 text-primary-600 focus:ring-primary-500"
                  />
                  <span class="min-w-0 flex-1 break-words text-sm text-gray-900 dark:text-white">{{ opt.label }}</span>
                </label>
              </div>

              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label
                  v-for="key in editableFieldCatalog"
                  :key="key"
                  class="flex min-h-[44px] items-center gap-3 rounded-lg border border-gray-200 p-3 cursor-pointer transition-colors dark:border-gray-600 sm:min-h-0"
                  :class="form.student_editable_fields.fields.includes(key) ? 'bg-primary-50 dark:bg-primary-900/20' : ''"
                >
                  <input
                    type="checkbox"
                    :checked="form.student_editable_fields.fields.includes(key)"
                    :disabled="isReadOnly"
                    @change="toggleEditableField(key)"
                    class="flex-shrink-0 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span class="min-w-0 flex-1 break-words text-sm text-gray-900 dark:text-white">{{ fieldLabel(key) }}</span>
                </label>
              </div>

              <div v-if="editableFieldsWarning" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 sm:p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <Icon icon="fluent:warning-24-regular" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                <p class="min-w-0 flex-1 break-words text-sm text-amber-800 dark:text-amber-200">{{ editableFieldsWarning }}</p>
              </div>
            </div>
          </div>

          <!-- Danger Zone -->
          <div v-if="activeTab === 'danger'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 p-4 sm:p-6 space-y-6">
            <h2 class="font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
              <Icon icon="fluent:warning-24-regular" class="w-5 h-5" />
              โซนอันตราย
            </h2>

            <!-- สถานะปกติ: เสนอให้เก็บถาวร -->
            <div v-if="! isArchived" class="p-3 sm:p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <h3 class="font-medium text-red-800 dark:text-red-300 mb-2 break-words">เก็บถาวรโรงเรียน</h3>
              <p class="text-sm text-red-600 dark:text-red-400 mb-4 break-words">
                โรงเรียนจะหายจากไดเรกทอรี การค้นหา และรายการของผู้ใช้คนอื่น — <strong>ข้อมูลไม่ถูกลบ</strong>
                สมาชิก คอร์ส และผลการเรียนยังอยู่ครบ และเจ้าของกู้คืนได้ทุกเมื่อจากหน้ารายการโรงเรียน
              </p>
              <button
                v-if="isOwner"
                :disabled="isArchiving"
                @click="confirmArchiveAcademy"
                class="w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors"
              >
                {{ isArchiving ? 'กำลังดำเนินการ...' : 'เก็บถาวรโรงเรียนนี้' }}
              </button>
              <p v-else class="text-sm text-gray-500 italic break-words">
                เฉพาะเจ้าของโรงเรียนหรือผู้ดูแลระบบเท่านั้นที่เก็บถาวรโรงเรียนได้
              </p>
            </div>

            <!-- สถานะถูกเก็บถาวรอยู่: เสนอให้กู้คืน -->
            <div v-else class="p-3 sm:p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
              <h3 class="font-medium text-amber-800 dark:text-amber-300 mb-2 break-words">โรงเรียนนี้ถูกเก็บถาวรอยู่</h3>
              <p class="text-sm text-amber-700 dark:text-amber-400 mb-4 break-words">
                ผู้ใช้คนอื่นมองไม่เห็นโรงเรียนนี้ในไดเรกทอรีและการค้นหา กดกู้คืนเพื่อให้กลับมาแสดงตามปกติ
              </p>
              <button
                v-if="isOwner"
                :disabled="isArchiving"
                @click="confirmRestoreAcademy"
                class="w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors"
              >
                {{ isArchiving ? 'กำลังดำเนินการ...' : 'กู้คืนโรงเรียนนี้' }}
              </button>
              <p v-else class="text-sm text-gray-500 italic break-words">
                เฉพาะเจ้าของโรงเรียนหรือผู้ดูแลระบบเท่านั้นที่กู้คืนโรงเรียนได้
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.toggle {
  @apply w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 appearance-none relative cursor-pointer;
  @apply after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600;
}

.toggle:focus {
  @apply outline-none ring-4 ring-primary-300 dark:ring-primary-800;
}

.toggle:checked {
  @apply bg-primary-600;
}

.toggle:checked::after {
  @apply translate-x-full border-white;
}
</style>
