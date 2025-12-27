<script setup>
import { Icon } from '@iconify/vue';
</script>

<template>
    <div>
        <div class="section-banner mt-2 mb-4 shadow-lg">
            <img class="section-banner-icon" :src="'/storage/images/banner/forums-icon.png'"
                alt="forums-icon">
            <p class="section-banner-title">รวมรายวิชา</p>
        </div>
        <div class=" bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4">
            <div class="flex flex-row justify-around">
                <Link :href="`/courses`" v-if="!$page.url === '/courses/feeds'"
                    class="border-b-4 hover:border-cyan-500 rounded-none w-full text-center flex-row justify-center"
                    :class="{ 'border-b-4 border-cyan-500': $page.url === `/courses/feeds` }">
                    <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                        <Icon icon="bi:view-list" class="w-8 h-8" 
                        :class="{ 'text-cyan-500': $page.url === `/courses/feeds` }"
                        />
                        <span>กระดานข่าวรายวิชา</span>
                    </div>
                </Link>
                <Link :href="`/courses`"
                    class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                    :class="{ 'border-b-4 border-cyan-500': $page.url === `/courses/users/${$page.props.auth.user.id}` }">
                    <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                        <Icon icon="lucide:layout-list" class="w-8 h-8"
                            :class="{ 'text-cyan-500': $page.url === `/courses/users/${$page.props.auth.user.id}` }" />
                        <span
                            :class="{ 'text-cyan-500': $page.url === `/courses/users/${$page.props.auth.user.id}` }">รายวิชาของฉัน</span>
                    </div>
                </Link>
                <Link :href="`/courses`"
                    class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center ">
                    <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                        <Icon icon="lucide:layout-list" class="w-8 h-8" />
                        <span>รายวิชาที่ฉันเป็นสมาชิก</span>
                    </div>
                </Link>
                <Link :href="`/courses`"
                    class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center ">
                    <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                        <Icon icon="lucide:layout-list" class="w-8 h-8" />
                        <span>รวมรายวิชาทั้งหมด</span>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
