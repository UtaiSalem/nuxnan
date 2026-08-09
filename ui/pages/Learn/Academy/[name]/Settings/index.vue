<script setup>
import { ref } from 'vue';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import AcademyInfoWidget from '@/components/learn/academy/widgets/AcademyInfoWidget.vue';
import AcademyAnnouncementWidget from '@/components/learn/academy/widgets/AcademyAnnouncementWidget.vue';
import AcademyActivityWidget from '@/components/learn/academy/widgets/AcademyActivityWidget.vue';
import { Icon } from '@iconify/vue';
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
});

</script>

<template>
        <AcademyLayout 
            :academy
            :isAcademyAdmin
        >
            <template #leftSideWidget>
                <div class="space-y-4">
                    <AcademyInfoWidget :academy="props.academy" />
                    <AcademyAnnouncementWidget :academyId="props.academy.data.id" />
                </div>
            </template>

            <template #academyContent>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
                    <div class="flex items-center mb-6">
                        <Icon icon="lucide:settings" class="w-6 h-6 mr-2 text-gray-600 dark:text-gray-300" />
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">การบริหารจัดการสถานศึกษา</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link :href="`/Learn/Academy/${props.academy.data.name}/Settings/General`" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <h3 class="font-semibold text-lg mb-1 text-gray-800 dark:text-gray-200">ข้อมูลทั่วไป</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">แก้ไขข้อมูลพื้นฐานของสถานศึกษา</p>
                        </Link>
                        
                        <Link :href="`/Learn/Academy/${props.academy.data.name}/Settings/Members`" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <h3 class="font-semibold text-lg mb-1 text-gray-800 dark:text-gray-200">สมาชิก</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">จัดการสมาชิกและคำขอเข้าร่วม</p>
                        </Link>

                        <Link :href="`/Learn/Academy/${props.academy.data.name}/Settings/Roles`" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <h3 class="font-semibold text-lg mb-1 text-gray-800 dark:text-gray-200">บทบาทและสิทธิ์</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">กำหนดสิทธิ์การเข้าถึงของผู้ใช้งาน</p>
                        </Link>
                    </div>
                </div>
                
                <div class="mt-4 flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
                     <Icon icon="lucide:construction" class="w-12 h-12 text-yellow-500 mb-2" />
                    <p class="text-gray-500 dark:text-gray-400">ส่วนอื่นๆ กำลังอยู่ระหว่างการพัฒนา</p>
                </div>

            </template>

            <template #rightSideWidget>
                <div class="space-y-4">
                    <AcademyActivityWidget :academyId="props.academy.data.id" />
                </div>
            </template>
        </AcademyLayout>
</template>
