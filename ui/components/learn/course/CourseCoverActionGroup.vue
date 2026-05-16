<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps({
    course: { type: Object, required: true },
    courseMemberOfAuth: { type: Object, default: null },
    memberStatus: { type: [String, Number], default: null },
    isRequestingMember: { type: Boolean, default: false },
    isRequestingUnmember: { type: Boolean, default: false },
    canPurchaseCopy: { type: Boolean, default: false },
    showAcceptMemberOption: { type: Boolean, default: false }
})

const emit = defineEmits([
    'request-member',
    'purchase-course',
    'cancel-member',
    'toggle-pending-menu'
])
</script>

<template>
    <div class="flex flex-col sm:flex-row lg:items-center gap-3 w-full lg:w-auto">
        <!-- Pending Status -->
        <div v-if="courseMemberOfAuth && (memberStatus === '0' || memberStatus === 'pending')" class="relative w-full sm:w-auto">
            <button @click.prevent="emit('toggle-pending-menu')"
                class="w-full sm:min-w-[180px] h-12 flex items-center justify-center gap-2 px-6 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-lg transition-all shadow-lg shadow-amber-500/25 active:scale-95 text-sm tracking-wide">
                <Icon icon="heroicons:clock" class="w-5 h-5" />
                <span>รอการตอบรับ</span>
                <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showAcceptMemberOption}" />
            </button>
            
            <div v-if="showAcceptMemberOption" class="absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden backdrop-blur-md bg-opacity-95">
                <button @click.prevent="emit('cancel-member')" :disabled="isRequestingUnmember"
                    class="w-full px-4 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 font-bold flex items-center justify-center sm:justify-start gap-2 disabled:opacity-50 transition-colors text-sm">
                    <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="heroicons:x-circle" class="w-5 h-5" />
                    <span>ยกเลิกคำขอ</span>
                </button>
            </div>
        </div>

        <!-- Active Member -->
        <button v-else-if="courseMemberOfAuth && (memberStatus === '1' || memberStatus === 'active')"
            @click.prevent="emit('cancel-member')" :disabled="isRequestingUnmember"
            class="group/active w-full sm:min-w-[180px] h-12 flex items-center justify-center gap-2 px-6 bg-emerald-500 hover:bg-red-500 text-white font-black rounded-lg transition-all shadow-lg shadow-emerald-500/25 disabled:opacity-50 active:scale-95 text-sm tracking-wide">
            <template v-if="isRequestingUnmember">
                <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" />
                <span>กำลังดำเนินการ...</span>
            </template>
            <template v-else>
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 group-hover/active:hidden" />
                <Icon icon="majesticons:door-exit-line" class="w-5 h-5 hidden group-hover/active:block" />
                <span class="group-hover/active:hidden">เป็นสมาชิกแล้ว</span>
                <span class="hidden group-hover/active:block">ออกจากรายวิชา</span>
            </template>
        </button>

        <!-- Not a member -->
        <div v-else-if="!courseMemberOfAuth" class="grid grid-cols-1 sm:flex gap-3 w-full">
            <button
                type="button"
                @click.prevent="emit('request-member')"
                :disabled="isRequestingMember"
                class="flex-1 sm:min-w-[180px] h-12 flex items-center justify-center gap-2 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-lg transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50 active:scale-95 text-sm tracking-wide whitespace-nowrap"
            >
                <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                <Icon v-else icon="heroicons:user-plus-solid" class="w-5 h-5" />
                <span>สมัครสมาชิก +</span>
            </button>
            
            <button
                v-if="canPurchaseCopy"
                type="button"
                @click.prevent="emit('purchase-course')"
                class="flex-1 sm:min-w-[180px] h-12 flex items-center justify-center gap-2 px-6 bg-cyan-500 hover:bg-cyan-600 text-white font-black rounded-lg transition-all shadow-lg shadow-cyan-500/25 active:scale-95 text-sm tracking-wide whitespace-nowrap"
            >
                <Icon icon="fluent:cart-24-filled" class="w-5 h-5" />
                <span>ซื้อรายวิชา</span>
            </button>
        </div>
    </div>
</template>
