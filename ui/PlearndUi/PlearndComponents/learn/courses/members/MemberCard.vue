<script setup>
import { computed, toRef } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';

// ✅ Import composable
import { useMemberProgress } from '@/composables/useMemberProgress';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';

const props = defineProps({
    member: {
      type: Object,
      required: true,
      default: () => ({})
    },
    dataIndex: {
      type: Number,
      default: 0
    },
});

const emit = defineEmits(['request-unmember-course']);

// Cache usePage props
const page = computed(() => usePage().props);
const courseTotalScore = computed(() => page.value.course?.data?.total_score || 0);
const isCourseAdmin = computed(() => page.value.isCourseAdmin);
const authUserId = computed(() => page.value.auth?.user?.id);

const canManage = computed(() => 
  props.member?.user?.id === authUserId.value || isCourseAdmin.value
);

// ✅ ใช้ composable สำหรับ progress calculation
const memberRef = toRef(props, 'member');
const { 
  percentage, 
  progressStatus, 
  progressColor, 
  statusMessage,
  statusIcon,
  remainingScore,
  remainingPercentage,
  progressBarStyle
} = useMemberProgress(memberRef, courseTotalScore);

// Member display data
const memberDisplayName = computed(() => 
  props.member?.member_name || props.member?.user?.name || 'Unknown'
);

const memberOrderNumber = computed(() => 
  props.member?.order_number || '#'
);

const memberGroupName = computed(() => 
  props.member?.group?.name || '-'
);

const memberAvatar = computed(() => 
  props.member?.user?.avatar || '/images/default-avatar.png'
);

// Handler functions
const handleUnmember = () => {
  emit('request-unmember-course', {
    memberId: props.member.id,
    memberName: memberDisplayName.value
  });
};

</script>

<template>
    <!-- ✅ ใช้ v-if แทน hidden class -->
    <li v-if="props.member?.role !== 3"
        class="group relative bg-white hover:bg-gradient-to-br hover:from-white hover:to-indigo-50 shadow-lg hover:shadow-xl p-4 rounded-xl w-full mb-3 transition-all duration-300 border border-gray-100 hover:border-indigo-200"
        :aria-label="`Member card for ${memberDisplayName}`"
        role="article">
        
        <!-- Admin Controls -->
        <div class="absolute top-3 right-3 z-10" v-if="isCourseAdmin">
            <DotsDropdownMenu @delete-model="handleUnmember">
                <template #deleteModel>
                    <span>ลบสมาชิก</span>
                </template>
            </DotsDropdownMenu>
        </div>
        
        <!-- Flex Layout สำหรับจัดวาง Member Info และ Progress Bar -->
        <div class="flex flex-col lg:flex-row gap-4 items-start" :class="{'pr-10': isCourseAdmin}">
            <!-- Member Info -->
            <div class="flex items-center w-full lg:w-64 lg:flex-shrink-0">
                <!-- Avatar with ring -->
                <div class="relative">
                    <img 
                        class="w-16 h-16 rounded-full ring-2 transition-all duration-300" 
                        :class="[progressColor.ring, `group-hover:${progressColor.ring}`]"
                        :src="memberAvatar" 
                        :alt="`${memberDisplayName}'s avatar`"
                        loading="lazy"
                    />
                    <!-- Status Badge -->
                    <div 
                        class="absolute -bottom-1 -right-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-lg"
                        :class="progressColor.bg">
                        {{ percentage }}%
                    </div>
                </div>
                
                <div class="ml-4">
                    <!-- Name -->
                    <a v-if="isCourseAdmin"
                       :href="`/courses/${page.course?.data?.id}/members/${props.member.id}/member-settings`"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="text-gray-900 font-bold tracking-wide text-lg hover:text-indigo-600 transition-colors duration-300 cursor-pointer inline-flex items-center group/link">
                        <span>{{ memberDisplayName }}</span>
                        <Icon icon="heroicons:arrow-top-right-on-square" class="w-4 h-4 ml-1.5 opacity-0 group-hover/link:opacity-100 transition-opacity duration-200" />
                    </a>
                    <p v-else class="text-gray-900 font-bold tracking-wide text-lg">
                        {{ memberDisplayName }}
                    </p>
                    
                    <!-- Status Message -->
                    <div class="flex items-center mt-1 mb-2" :class="progressColor.text">
                        <Icon :icon="statusIcon" class="w-4 h-4 mr-1.5" />
                        <span class="text-xs font-semibold">{{ statusMessage }}</span>
                    </div>
                    
                    <!-- Meta Info -->
                    <div class="flex items-center space-x-3 text-sm">
                        <!-- Order Number -->
                        <span class="flex items-center text-gray-600 hover:text-indigo-600 transition-colors">
                            <Icon icon="fluent:number-symbol-square-20-filled" class="w-5 h-5 mr-1" />
                            <span class="font-medium">{{ memberOrderNumber }}</span>
                        </span>
                        
                        <!-- Group -->
                        <span class="flex items-center text-gray-600 hover:text-indigo-600 transition-colors">
                            <Icon icon="heroicons:user-group-solid" class="w-5 h-5 mr-1" />
                            <span class="font-medium">{{ memberGroupName }}</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="flex flex-col items-start lg:items-end w-full flex-1">
                <div class="flex items-center justify-between mb-2 w-full">
                    <div class="flex items-center">
                        <Icon icon="heroicons:academic-cap-solid" class="w-5 h-5 text-indigo-500 mr-2" />
                        <span class="text-sm font-semibold text-gray-700">ความคืบหน้า</span>
                    </div>
                    <!-- <span class="text-xs text-gray-500 font-medium">
                        เหลืออีก {{ remainingPercentage }}%
                    </span> -->
                </div>
                
                <div class="relative w-full bg-gray-100 rounded-full h-7 overflow-hidden shadow-inner">
                    <!-- Progress Fill with Gradient -->
                    <div 
                        class="h-full text-xs font-bold text-white flex items-center justify-center rounded-full transition-all duration-500 ease-out relative overflow-hidden bg-gradient-to-r"
                        :class="progressColor.gradient"
                        :style="`width: ${percentage}%`"
                        role="progressbar"
                        :aria-valuenow="percentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Progress: ${percentage}% - ${progressStatus}`">
                        
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                        
                        <!-- Percentage Text -->
                        <span class="relative z-10 font-bold">{{ percentage }}%</span>
                    </div>
                    
                    <!-- No Score State -->
                    <div v-if="percentage === 0" class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-medium text-gray-500">ยังไม่มีคะแนน</span>
                    </div>
                </div>
                
                <!-- Score Detail -->
                <div class="flex items-center justify-between w-full mt-2 text-xs">
                    <div class="text-gray-500">
                        <span class="font-medium" :class="progressColor.text">{{ props.member?.achieved_score || 0 }}</span>
                        <span class="mx-1">/</span>
                        <span>{{ courseTotalScore }}</span>
                        <span class="ml-1">คะแนน</span>
                    </div>
                    <!-- <div class="text-gray-400">
                        <span>เหลือ </span>
                        <span class="font-medium">{{ remainingScore }}</span>
                        <span> คะแนน</span>
                    </div> -->
                </div>
            </div>
        </div>
    </li>
</template>

<style scoped>
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>
