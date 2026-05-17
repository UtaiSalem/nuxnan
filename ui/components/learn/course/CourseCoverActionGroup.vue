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
    <div class="flex w-full min-w-0 flex-col gap-3 sm:flex-row lg:items-center">
        <!-- Pending Status -->
        <div v-if="courseMemberOfAuth && (memberStatus === '0' || memberStatus === 'pending')" class="relative w-full min-w-0">
            <button @click.prevent="emit('toggle-pending-menu')"
                class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-sm font-black tracking-wide text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 active:scale-95 sm:px-6">
                <Icon icon="heroicons:clock" class="w-5 h-5" />
                <span>รอการตอบรับ</span>
                <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showAcceptMemberOption}" />
            </button>
            
            <div v-if="showAcceptMemberOption" class="absolute left-0 z-50 mt-2 w-full overflow-hidden rounded-xl border border-gray-100 bg-white/95 shadow-2xl backdrop-blur-md dark:border-gray-700 dark:bg-gray-800/95 sm:right-0 sm:left-auto sm:w-52">
                <button @click.prevent="emit('cancel-member')" :disabled="isRequestingUnmember"
                    class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50 dark:hover:bg-red-900/20 sm:justify-start">
                    <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="heroicons:x-circle" class="w-5 h-5" />
                    <span>ยกเลิกคำขอ</span>
                </button>
            </div>
        </div>

        <!-- Active Member -->
        <button v-else-if="courseMemberOfAuth && (memberStatus === '1' || memberStatus === 'active')"
            @click.prevent="emit('cancel-member')" :disabled="isRequestingUnmember"
            class="group/active flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 text-sm font-black tracking-wide text-white shadow-lg shadow-emerald-500/25 transition hover:bg-red-500 active:scale-95 disabled:opacity-50 sm:px-6">
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
        <div v-else-if="!courseMemberOfAuth" class="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                type="button"
                @click.prevent="emit('request-member')"
                :disabled="isRequestingMember"
                class="flex h-12 min-w-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-black tracking-wide text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50 sm:px-5"
            >
                <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                <Icon v-else icon="heroicons:user-plus-solid" class="w-5 h-5" />
                <span>สมัครสมาชิก +</span>
            </button>
            
            <button
                v-if="canPurchaseCopy"
                type="button"
                @click.prevent="emit('purchase-course')"
                class="flex h-12 min-w-0 items-center justify-center gap-2 rounded-xl bg-cyan-500 px-4 text-sm font-black tracking-wide text-white shadow-lg shadow-cyan-500/25 transition hover:bg-cyan-600 active:scale-95 sm:px-5"
            >
                <Icon icon="fluent:cart-24-filled" class="w-5 h-5" />
                <span>ซื้อรายวิชา</span>
            </button>
        </div>
    </div>
</template>
