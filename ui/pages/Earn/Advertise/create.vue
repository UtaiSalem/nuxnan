<script setup>
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useObjectUrl } from '@vueuse/core'
import MainLayout from '~/layouts/main.vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'
import AdvertiseItemCard from '~/components/widgets/advertises/AdvertiseItemCard.vue'

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const router = useRouter()

definePageMeta({
  layout: false
})

const isLoading = ref(false)

// Form Data - Creative
const title = ref('')
const description = ref('')
const mediaLink = ref('')
const mediaImage = ref(null)
const inputMediaImage = ref(null)
const dragingMedia = ref(false)

// Form Data - Campaign
const quantityToShowProductMedia = ref(1000)
const timeToShowProductMedia = ref(5)
const transferDate = ref(new Date())
const transferTime = ref({ hours: new Date().getHours(), minutes: new Date().getMinutes() })

// Form Data - Payment
const payWithWallet = ref(false)
const inputSlip = ref(null)
const slipImage = ref(null)
const dragingSlip = ref(false)

const totalMoneyAdvert = ref(0)
const quantityOptions = [100, 500, 1000, 2000, 5000, 10000]
const timeOptions = [5, 10, 15, 30, 60]

const previewAdvert = computed(() => ({
    advertiser: authStore.user,
    media_image: mediaImage.value?.url || 'https://via.placeholder.com/300?text=Ad+Image+Preview',
    media_type: mediaImage.value?.type || '',
    total_views: quantityToShowProductMedia.value,
    remaining_views: quantityToShowProductMedia.value,
    duration: timeToShowProductMedia.value,
}))

const walletBalance = computed(() => parseFloat(authStore.user?.wallet) || 0)

function computeTotalCost() {
    if(quantityToShowProductMedia.value < 0) quantityToShowProductMedia.value = 0;
    // Formula: Money = Quantity * Duration * 0.10 THB
    totalMoneyAdvert.value = quantityToShowProductMedia.value * timeToShowProductMedia.value * 0.10;
}

function computeViewsFromCost() {
    if(totalMoneyAdvert.value < 0) totalMoneyAdvert.value = 0;
    if(timeToShowProductMedia.value > 0) {
        // Views = Cost / (Duration * 0.10)
        quantityToShowProductMedia.value = Math.floor(totalMoneyAdvert.value / (timeToShowProductMedia.value * 0.10));
    }
}

// Media Image Handlers
const browseInputMedia = () => inputMediaImage.value?.click()
const onInputMediaChange = (e) => {
    if (e.target.files && e.target.files[0]) {
        mediaImage.value = {
            file: e.target.files[0],
            url: URL.createObjectURL(e.target.files[0]),
            type: e.target.files[0].type
        }
    }
}
const onDropMediaFile = (e) => {
    dragingMedia.value = false;
    let files = [...e.dataTransfer.items].filter((item) => item.kind === 'file').map((item) => item.getAsFile())
    if (files.length > 0) {
        mediaImage.value = {
            file: files[0],
            url: useObjectUrl(files[0]),
            type: files[0].type
        }
    }
}
const deleteMediaImage = () => {
    mediaImage.value = null;
    // clear input value to allow re-selecting same file
    if(inputMediaImage.value) inputMediaImage.value.value = '';
}

// Slip Handlers
const browseInputSlip = () => inputSlip.value?.click()
const onInputSlipChange = (e) => {
    if (e.target.files && e.target.files[0]) {
        slipImage.value = {
            file: e.target.files[0],
            url: URL.createObjectURL(e.target.files[0]),
        }
    }
}
const onDropSlipFile = (e) => {
    dragingSlip.value = false;
    let files = [...e.dataTransfer.items].filter((item) => item.kind === 'file').map((item) => item.getAsFile())
    if (files.length > 0) {
        slipImage.value = {
            file: files[0],
            url: useObjectUrl(files[0]),
        }
    }
}
const deleteSlipImage = () => {
    slipImage.value = null;
    if(inputSlip.value) inputSlip.value.value = '';
}

async function submitForm() {
    if (!authStore.isAuthenticated) {
        Swal.fire('Error', 'Please login first', 'error')
        return
    }

    // Recalculate cost one last time to be sure
    const expectedCost = quantityToShowProductMedia.value * timeToShowProductMedia.value * 0.10;
    // Allow small epsilon diff or just sync it
    if(Math.abs(totalMoneyAdvert.value - expectedCost) > 0.01) {
         totalMoneyAdvert.value = expectedCost;
    }

    if (!mediaImage.value) {
        Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดรูปภาพโฆษณา', 'warning')
        return
    }

    if (!payWithWallet.value && !slipImage.value) {
        Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดสลิปการโอนเงิน หรือเลือกชำระด้วย Wallet', 'warning')
        return
    }

    if (payWithWallet.value && walletBalance.value < totalMoneyAdvert.value) {
        Swal.fire('แจ้งเตือน', 'ยอดเงินใน Wallet ไม่เพียงพอ', 'error')
        return
    }

    isLoading.value = true
    const advertData = new FormData()
    advertData.append('advertiser_id', authStore.user?.id)
    advertData.append('title', title.value)
    if(description.value) advertData.append('description', description.value)
    if(mediaLink.value) advertData.append('media_link', mediaLink.value)
    advertData.append('amounts', totalMoneyAdvert.value)
    advertData.append('duration', timeToShowProductMedia.value)
    advertData.append('total_views', quantityToShowProductMedia.value)
    const year = transferDate.value.getFullYear();
    const month = String(transferDate.value.getMonth() + 1).padStart(2, '0');
    const day = String(transferDate.value.getDate()).padStart(2, '0');
    advertData.append('transfer_date', `${year}-${month}-${day}`) // YYYY-MM-DD

    // Time handling
    let hours = 0, minutes = 0;
    if (transferTime.value) {
        if(typeof transferTime.value === 'string') {
             // If datepicker returns string HH:mm
             const parts = transferTime.value.split(':');
             hours = parts[0];
             minutes = parts[1];
        } else {
             hours = transferTime.value.hours ?? 0;
             minutes = transferTime.value.minutes ?? 0;
        }
    }
    advertData.append('transfer_time', `${hours}:${minutes}`)

    if(slipImage.value) advertData.append('slip', slipImage.value.file)
    if(mediaImage.value) advertData.append('media_image', mediaImage.value.file)

    try {
        const advertResp = await $fetch(`${apiBase}/api/advertises`, {
            method: 'POST',
            body: advertData,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (advertResp.success) {
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'สร้างโฆษณาเรียบร้อยแล้ว',
                icon: 'success',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#10B981'
            }).then(() => {
                navigateTo('/earn/advertise')
            })
        } else {
            throw new Error(advertResp.message || 'Unknown Error')
        }
    } catch (error) {
        Swal.fire('เกิดข้อผิดพลาด', error.message || 'Error occurred', 'error')
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    computeTotalCost()
})
</script>

<template>
<MainLayout>
    <!-- Header Banner -->
    <template #hero>
        <div class="relative rounded-2xl overflow-hidden shadow-lg mb-8 bg-gradient-to-r from-blue-600 to-indigo-700 h-48 flex items-center px-8">
             <div class="absolute inset-0 opacity-20 bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-center"></div>
             <div class="relative z-10 flex items-center gap-6">
                 <div class="p-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                     <Icon icon="solar:megaphone-bold-duotone" class="w-12 h-12 text-white" />
                 </div>
                 <div>
                     <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">ลงโฆษณา</h1>
                     <p class="text-blue-100 text-lg">โปรโมทสินค้าและบริการของคุณให้ผู้ใช้นับหมื่นเห็นได้ง่ายๆ</p>
                 </div>
             </div>
        </div>
    </template>

    <template #default>
        <div class="max-w-7xl mx-auto pb-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- Left Column: Form -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Section 1: Creative -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">1</span>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">รายละเอียดโฆษณา</h2>
                        </div>

                        <div class="space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หัวข้อโฆษณา / ชื่อสินค้า <span class="text-red-500">*</span></label>
                                <input v-model="title" type="text" placeholder="เช่น โปรโมชั่นพิเศษลด 50%..." class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm" required />
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (ไม่บังคับ)</label>
                                <textarea v-model="description" rows="3" placeholder="อธิบายจุดเด่นของสินค้าหรือบริการ..." class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm"></textarea>
                            </div>

                            <!-- Media Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพหรือวิดีโอโฆษณา <span class="text-red-500">*</span></label>
                                
                                <div v-if="!mediaImage" 
                                     @click="browseInputMedia"
                                     @dragover.prevent="dragingMedia = true"
                                     @dragleave.prevent="dragingMedia = false"
                                     @drop.prevent="onDropMediaFile"
                                     :class="{'border-blue-500 bg-blue-50': dragingMedia}"
                                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-8 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"
                                >
                                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                        <Icon icon="solar:cloud-upload-bold-duotone" class="w-8 h-8" />
                                    </div>
                                    <p class="text-gray-900 dark:text-white font-medium mb-1">คลิกเพื่ออัปโหลด หรือลากไฟล์มาวางที่นี่</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">รองรับ JPG, PNG, MP4, WEBM (Max 20MB)</p>
                                    <input type="file" ref="inputMediaImage" class="hidden" accept="image/*,video/*" @change="onInputMediaChange">
                                </div>

                                <div v-else class="relative rounded-2xl overflow-hidden shadow-md group">
                                    <button @click.stop="deleteMediaImage" class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg z-20 transition-transform hover:scale-110">
                                        <Icon icon="solar:trash-bin-bold" />
                                    </button>
                                    
                                    <div v-if="mediaImage.type && mediaImage.type.startsWith('video/')" class="aspect-video bg-black flex items-center justify-center">
                                         <video :src="mediaImage.url" controls class="max-h-[400px] w-full"></video>
                                    </div>
                                    <img v-else :src="mediaImage.url" class="w-full object-contain max-h-[400px] bg-gray-100 dark:bg-gray-900" />
                                </div>
                            </div>
                            
                            <!-- Link -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลิงก์เว็บไซต์ / สินค้า (ไม่บังคับ)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                        <Icon icon="solar:link-circle-bold" />
                                    </div>
                                    <input v-model="mediaLink" type="url" placeholder="https://..." class="pl-10 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: Campaign Settings -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                         <div class="flex items-center gap-3 mb-6">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold">2</span>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">ตั้งค่าแคมเปญ</h2>
                        </div>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนการแสดงผล (Views)</label>
                                <div class="relative">
                                    <input type="number" v-model.number="quantityToShowProductMedia" @input="computeTotalCost" class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11 pr-16" placeholder="จำนวน" min="100">
                                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500 text-sm">ครั้ง</div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button v-for="opt in quantityOptions" :key="opt" @click="quantityToShowProductMedia = opt; computeTotalCost()" class="text-xs px-2 py-1 rounded-md border border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors" :class="{'bg-amber-50 border-amber-200 text-amber-700': quantityToShowProductMedia === opt}">
                                        {{ opt.toLocaleString() }}
                                    </button>
                                </div>
                            </div>

                            <!-- Duration -->
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระยะเวลาต่อวิว (วินาที)</label>
                                <select v-model.number="timeToShowProductMedia" @change="computeTotalCost" class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11">
                                    <option v-for="opt in timeOptions" :key="opt" :value="opt">{{ opt }} วินาที</option>
                                </select>
                            </div>

                            <!-- Budget (New) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">งบประมาณ (บาท)</label>
                                <div class="relative">
                                    <input type="number" v-model.number="totalMoneyAdvert" @input="computeViewsFromCost" class="w-full rounded-xl border-gray-300 focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11 pr-12" placeholder="0.00" min="0" step="0.01">
                                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500 text-sm">THB</div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">คำนวณอัตโนมัติจาก {{ timeToShowProductMedia * 0.10 }} บาท/วิว</p>
                            </div>
                        </div>
                    </section>

                    <!-- Section 3: Payment -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                         <div class="flex items-center gap-3 mb-6">
                            <span class="w-8 h-8 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold">3</span>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">ชำระเงิน</h2>
                        </div>
                        
                        <!-- Toggle Payment Method -->
                        <div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl mb-6">
                            <button @click="payWithWallet = false" :class="!payWithWallet ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                                <Icon icon="solar:card-transfer-bold" />
                                โอนเงินธนาคาร
                            </button>
                            <button @click="payWithWallet = true" :class="payWithWallet ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                                <Icon icon="solar:wallet-money-bold" />
                                Wallet ({{ walletBalance.toFixed(2) }} ฿)
                            </button>
                        </div>

                        <!-- Bank Transfer Form -->
                        <div v-if="!payWithWallet" class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-200 dark:border-gray-600 flex items-start gap-4">
                                <Icon icon="solar:info-circle-bold" class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" />
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    <p class="font-bold text-gray-900 dark:text-white mb-1">บัญชีธนาคารสำหรับโอนเงิน</p>
                                    <p>ธนาคาร: กสิกรไทย (K-Bank)</p>
                                    <p>เลขที่บัญชี: <span class="font-mono font-bold select-all">XXX-X-XXXXX-X</span></p>
                                    <p>ชื่อบัญชี: บจก. เพลินด์</p>
                                </div>
                            </div>

                             <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่โอน</label>
                                    <VueDatePicker v-model="transferDate" :format="'dd/MM/yyyy'" :enable-time-picker="false" auto-apply class="date-picker-custom" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เวลาโอน</label>
                                     <VueDatePicker v-model="transferTime" time-picker :format="'HH:mm'" auto-apply >
                                         <template #input-icon>
                                             <Icon icon="solar:clock-circle-bold" class="w-5 h-5 text-gray-400"/>
                                         </template>
                                     </VueDatePicker>
                                </div>
                            </div>

                             <!-- Slip Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หลักฐานการโอนเงิน (Slip) <span class="text-red-500">*</span></label>
                                <div v-if="!slipImage" 
                                     @click="browseInputSlip"
                                     @dragover.prevent="dragingSlip = true"
                                     @dragleave.prevent="dragingSlip = false"
                                     @drop.prevent="onDropSlipFile"
                                     :class="{'border-teal-500 bg-teal-50': dragingSlip}"
                                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"
                                >
                                    <Icon icon="solar:document-add-bold" class="w-8 h-8 text-gray-400 group-hover:text-teal-500 mx-auto mb-2 transition-colors" />
                                    <p class="text-sm text-gray-600 dark:text-gray-400">อัปโหลดสลิป</p>
                                    <input type="file" ref="inputSlip" class="hidden" accept="image/*" @change="onInputSlipChange">
                                </div>
                                <div v-else class="relative rounded-xl overflow-hidden shadow-sm border border-gray-200 inline-block group">
                                     <button @click.stop="deleteSlipImage" class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Icon icon="solar:close-circle-bold" />
                                    </button>
                                    <img :src="slipImage.url" class="h-32 w-auto object-contain" />
                                    <div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[10px] px-2 py-0.5 truncate">{{ slipImage.file.name }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Warning -->
                        <div v-else class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-xl border border-teal-200 dark:border-teal-700 text-center">
                            <Icon icon="solar:check-circle-bold" class="w-10 h-10 text-teal-500 mx-auto mb-2" />
                            <p class="font-medium text-teal-900 dark:text-teal-100">ระบบจะตัดเงินจาก Wallet ของคุณทันที</p>
                            <p class="text-sm text-teal-600 dark:text-teal-300">สะดวกรวดเร็ว ไม่ต้องรอตรวจสอบยอดเงิน</p>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Sidebar (Sticky) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        
                        <!-- Preview Card -->
                        <div class="bg-gray-100 dark:bg-gray-800/50 rounded-2xl p-4 border border-dashed border-gray-300 dark:border-gray-600">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 text-center">ตัวอย่างโฆษณา</p>
                            <div class="transform scale-90 sm:scale-100 origin-top bg-white rounded-xl shadow-lg pointer-events-none">
                                <AdvertiseItemCard :advert="previewAdvert" />
                            </div>
                            <div class="mt-2 text-center">
                                <p class="text-sm font-bold text-gray-800 dark:text-white truncate px-2">{{ title || 'หัวข้อโฆษณา' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate px-4">{{ description || 'รายละเอียด...' }}</p>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="p-6">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สรุปยอดชำระ</h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                        <span>จำนวน Views</span>
                                        <span class="font-medium">{{ quantityToShowProductMedia.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                        <span>ระยะเวลา/View</span>
                                        <span class="font-medium">{{ timeToShowProductMedia }} วินาที</span>
                                    </div>
                                    <div class="h-px bg-gray-100 dark:bg-gray-700 my-2"></div>
                                    <div class="flex justify-between items-end">
                                        <span class="font-bold text-gray-900 dark:text-white">รวมเป็นเงิน</span>
                                        <div class="text-right">
                                            <div class="text-3xl font-bold text-blue-600">{{ totalMoneyAdvert.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</div>
                                            <div class="text-xs text-gray-400">บาท</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                                <button @click="submitForm" :disabled="isLoading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                    <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                                    <span v-else>ยืนยันและชำระเงิน</span>
                                </button>
                                <button @click="router.back()" class="w-full mt-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-sm font-medium py-2">
                                    ยกเลิก
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </template>
</MainLayout>
</template>

<style scoped>
/* Custom Scrollbar for Textarea if needed */
textarea::-webkit-scrollbar {
  width: 8px;
}
textarea::-webkit-scrollbar-track {
  background: transparent;
}
textarea::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}
</style>
