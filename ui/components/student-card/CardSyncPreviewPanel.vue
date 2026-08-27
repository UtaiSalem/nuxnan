<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface SyncPreview {
  counts: Record<'create' | 'update' | 'expire' | 'unchanged' | 'orphan' | 'duplicate', number>
  details: Record<string, any[]>
}

const props = defineProps<{ preview: SyncPreview }>()
const confirmation = ref('')

defineEmits<{ commit: []; cancel: [] }>()
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <Icon icon="heroicons:arrow-path-rounded-square" class="w-6 h-6 text-blue-500" />
                    ตัวอย่างการซิงค์บัตรนักเรียน
                </h3>
                <p class="text-sm text-gray-500 mt-1">สรุปการเปลี่ยนแปลงที่จะเกิดขึ้นกับฐานข้อมูลบัตรนักเรียน</p>
            </div>
            <div class="flex gap-2">
                <button @click="$emit('cancel')" class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                    ยกเลิก
                </button>
                <button
                    :disabled="confirmation !== 'SYNC' || props.preview.counts.duplicate > 0"
                    @click="$emit('commit')"
                    class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 font-medium transition-colors flex items-center gap-2 shadow-sm"
                >
                    <Icon icon="heroicons:check-circle" class="w-5 h-5" />
                    ยืนยันการซิงค์ข้อมูล
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div v-if="props.preview.counts.duplicate > 0" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                พบบัตร active ซ้ำ {{ props.preview.counts.duplicate }} รายการ ต้องแก้ข้อมูลซ้ำก่อนจึงจะซิงค์ได้
            </div>
            <label class="mb-6 block text-sm font-medium text-gray-700">
                พิมพ์ <strong>SYNC</strong> เพื่อยืนยันการเปลี่ยนแปลง
                <input v-model="confirmation" autocomplete="off" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2" />
            </label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-center">
                    <div class="text-3xl font-bold text-green-600 mb-1">{{ props.preview.counts.create }}</div>
                    <div class="text-sm text-green-700 font-medium">สร้างใหม่</div>
                </div>
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-1">{{ props.preview.counts.update }}</div>
                    <div class="text-sm text-blue-700 font-medium">อัปเดตข้อมูล/ย้ายห้อง</div>
                </div>
                <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-1">{{ props.preview.counts.expire }}</div>
                    <div class="text-sm text-orange-700 font-medium">หมดอายุ/จบการศึกษา</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl text-center">
                    <div class="text-3xl font-bold text-gray-600 mb-1">{{ props.preview.counts.unchanged }}</div>
                    <div class="text-sm text-gray-700 font-medium">ไม่มีการเปลี่ยนแปลง</div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Create list -->
                <div v-if="props.preview.details.create.length > 0">
                    <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        รายการที่จะสร้างใหม่ ({{ props.preview.details.create.length }})
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto border border-gray-100">
                        <div v-for="item in props.preview.details.create" :key="item.student_id" class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
                            <span class="text-gray-700">{{ item.name }}</span>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">ม.{{ item.target_level }}/{{ item.target_section }}</span>
                        </div>
                    </div>
                </div>

                <!-- Update list -->
                <div v-if="props.preview.details.update.length > 0">
                    <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        รายการที่จะอัปเดต ({{ props.preview.details.update.length }})
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto border border-gray-100">
                        <div v-for="item in props.preview.details.update" :key="item.student_id" class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
                            <span class="text-gray-700">{{ item.name }}</span>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500 line-through">ม.{{ item.from }}</span>
                                <Icon icon="heroicons:arrow-right" class="w-4 h-4 text-gray-400" />
                                <span class="text-blue-600 font-medium">ม.{{ item.to }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expire list -->
                <div v-if="props.preview.details.expire.length > 0">
                    <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        รายการที่จะหมดอายุ ({{ props.preview.details.expire.length }})
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto border border-gray-100">
                        <div v-for="item in props.preview.details.expire" :key="item.student_id" class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
                            <span class="text-gray-700">{{ item.name }}</span>
                            <span class="text-gray-500 text-sm">ห้องเดิม: ม.{{ item.current }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
