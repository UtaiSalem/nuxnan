<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface NextLesson {
  id: number
  title: string
}

interface Props {
  earnedPoints: number
  totalPoints: number
  correctCount: number
  totalQuestions: number
  percentage: number
  lessonCompleted?: boolean
  hasAssignments?: boolean
  assignmentCount?: number
  /**
   * tri-state: object = มีบทถัดไป, null = รู้แน่ชัดว่าเป็นบทสุดท้าย,
   * undefined = ยังไม่รู้ (รายการบทเรียนของรายวิชายังไม่ถูกโหลด)
   * จงใจไม่ตั้ง default ใน withDefaults เพราะ Vue จะกลืน undefined เป็น default ทำให้แยกสองสถานะไม่ออก
   */
  nextLesson?: NextLesson | null
  togglingProgress?: boolean
  /** 'card' = แสดงคาไว้ใต้ข้อสุดท้าย, 'modal' = แสดงในโมดัลฉลอง */
  variant?: 'card' | 'modal'
  /** id ของหัวข้อ ใช้ผูกกับ aria-labelledby ของโมดัล */
  titleId?: string
}

const props = withDefaults(defineProps<Props>(), {
  lessonCompleted: false,
  hasAssignments: false,
  assignmentCount: 0,
  togglingProgress: false,
  variant: 'card',
})

const emit = defineEmits<{
  'mark-complete': []
  'go-assignments': []
  'go-next-lesson': []
  'go-progress': []
}>()

type StepKey = 'mark-complete' | 'go-assignments' | 'go-next-lesson' | 'go-progress'

interface Step {
  key: StepKey
  icon: string
  title: string
  desc: string
}

// เรียงตามลำดับความสำคัญ และแสดงเฉพาะขั้นตอนที่ทำได้จริง
const steps = computed<Step[]>(() => {
  const list: Step[] = []

  if (!props.lessonCompleted) {
    list.push({
      key: 'mark-complete',
      icon: 'fluent:checkmark-circle-24-filled',
      title: 'ทำเครื่องหมายว่าอ่านจบบทเรียนนี้',
      desc: 'บันทึกว่าคุณเรียนบทนี้จบแล้ว เพื่อให้นับรวมในความคืบหน้าของรายวิชา',
    })
  }

  if (props.hasAssignments) {
    list.push({
      key: 'go-assignments',
      icon: 'fluent:clipboard-task-24-filled',
      title: 'ไปทำแบบฝึกหัด',
      desc: props.assignmentCount > 0
        ? `บทเรียนนี้มีแบบฝึกหัด ${props.assignmentCount} ชิ้น รอให้คุณส่งคำตอบ`
        : 'บทเรียนนี้มีแบบฝึกหัดให้ส่งคำตอบเพิ่มเติม',
    })
  }

  if (props.nextLesson) {
    list.push({
      key: 'go-next-lesson',
      icon: 'fluent:arrow-next-24-filled',
      title: `ไปบทเรียนถัดไป: ${props.nextLesson.title}`,
      desc: 'เลื่อนหน้าจอไปยังบทเรียนถัดไปของรายวิชานี้',
    })
  } else {
    // เคลมว่า "เป็นบทสุดท้าย" ได้เฉพาะตอนที่รู้แน่ชัด (null) เท่านั้น
    // ถ้ายังไม่รู้ (undefined) ให้ใช้ข้อความกลางๆ ที่ไม่ผิดไม่ว่าความจริงจะเป็นแบบไหน
    const isKnownLastLesson = props.nextLesson === null
    list.push({
      key: 'go-progress',
      icon: 'fluent:data-trending-24-filled',
      title: 'ดูความคืบหน้าของฉัน',
      desc: isKnownLastLesson
        ? 'นี่เป็นบทเรียนสุดท้ายแล้ว ดูสรุปผลการเรียนทั้งรายวิชาของคุณ'
        : 'ดูสรุปผลการเรียนทั้งรายวิชาของคุณ',
    })
  }

  return list
})

const runStep = (key: StepKey) => {
  emit(key as any)
}
</script>

<template>
  <div
    class="rounded-2xl border border-green-200 dark:border-green-800 bg-white dark:bg-gray-800 shadow-sm overflow-hidden"
    :class="variant === 'card' ? 'mt-8' : ''"
  >
    <!-- Success Banner -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/25 dark:to-emerald-900/20 p-6 border-b border-green-100 dark:border-green-900/50">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 p-3 rounded-xl bg-green-500/10 text-green-600 dark:text-green-400">
          <Icon icon="fluent:trophy-24-filled" class="w-8 h-8" />
        </div>
        <div class="min-w-0">
          <h4 :id="titleId" class="text-xl font-bold text-green-800 dark:text-green-300">
            ทำแบบทดสอบครบแล้ว 🎉
          </h4>
          <p class="mt-1 text-sm text-green-700/80 dark:text-green-400/80">
            คุณตอบถูกครบทุกข้อในบทเรียนนี้แล้ว เลือกขั้นตอนถัดไปด้านล่างเพื่อเรียนต่อได้เลย
          </p>
        </div>
      </div>
    </div>

    <div class="p-6">
      <!-- Score Summary -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 p-4">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">คะแนนที่ได้</p>
          <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
            {{ earnedPoints }}
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ {{ totalPoints }}</span>
          </p>
        </div>
        <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 p-4">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ตอบถูก</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ correctCount }}
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ {{ totalQuestions }} ข้อ</span>
          </p>
        </div>
        <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 p-4">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">เปอร์เซ็นต์</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ percentage }}%</p>
        </div>
      </div>

      <div class="flex w-full h-2.5 mt-4 bg-green-500/10 dark:bg-green-500/20 rounded-full overflow-hidden">
        <div
          class="h-full bg-green-500 rounded-full transition-all duration-700 ease-out"
          :style="{ width: `${Math.min(100, Math.max(0, percentage))}%` }"
        />
      </div>

      <!-- Next Steps -->
      <div class="mt-6">
        <h5 class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white mb-3">
          <Icon icon="fluent:route-24-filled" class="w-4 h-4 text-blue-500" />
          ขั้นตอนถัดไป
        </h5>

        <div class="flex flex-col gap-2.5">
          <button
            v-for="(step, index) in steps"
            :key="step.key"
            type="button"
            :disabled="step.key === 'mark-complete' && togglingProgress"
            class="w-full flex items-center gap-3 sm:gap-4 text-left p-4 rounded-xl border transition-all active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
            :class="index === 0
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30'
              : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
            @click="runStep(step.key)"
          >
            <div
              class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
              :class="index === 0
                ? 'bg-blue-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
            >
              <Icon
                v-if="step.key === 'mark-complete' && togglingProgress"
                icon="eos-icons:bubble-loading"
                class="w-5 h-5"
              />
              <Icon v-else :icon="step.icon" class="w-6 h-6" />
            </div>

            <div class="flex-1 min-w-0">
              <p
                class="font-semibold text-sm sm:text-base truncate"
                :class="index === 0 ? 'text-blue-700 dark:text-blue-300' : 'text-gray-900 dark:text-white'"
              >
                {{ step.title }}
              </p>
              <p class="mt-0.5 text-xs sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                {{ step.desc }}
              </p>
            </div>

            <Icon
              icon="fluent:chevron-right-24-regular"
              class="hidden sm:block w-5 h-5 flex-shrink-0"
              :class="index === 0 ? 'text-blue-500' : 'text-gray-400'"
            />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
