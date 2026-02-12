<script setup>
import { Icon } from '@iconify/vue';
import { Link,  } from '@inertiajs/vue3';
</script>

<template>
    <div class=" bg-white shadow-xl w-full max-w-7xl mx-auto rounded-lg overflow-hidden mt-4">
        <div class="flex flex-row justify-around">
            <Link :href="`/courses`" v-if="$page.url===`/courses/feeds`" class="border-b-4 hover:border-cyan-500 rounded-none w-full text-center flex-row justify-center"
                        :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/feeds`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="bi:view-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/feeds`}" />
                            <span :class="{'text-cyan-500': $page.url===`/courses/feeds`}">กระดานข่าวรายวิชา</span>
                        </div>
                    </Link>
            <Link :href="`/courses/users/${$page.props.auth.user.id}`" 
                        class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                        :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/users/${$page.props.auth.user.id}`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}`}" />
                            <span :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}`}">รายวิชาของฉัน</span>
                        </div>
                    </Link>
            <Link :href="`/courses/users/${$page.props.auth.user.id}/member`" 
                        class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                        :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}" />
                            <span :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}">รายวิชาที่ฉันเป็นสมาชิก</span>
                        </div>
                    </Link>
            <Link :href="`/courses`" class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                        :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses`}" />
                            <span :class="{'text-cyan-500': $page.url===`/courses`}">รวมรายวิชาทั้งหมด</span>
                        </div>
                    </Link>
            <Link :href="`/courses/create`" v-if="$page.props.auth.user.pp > 68400" class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                        :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/create`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/create`}" />
                            <span :class="{'text-cyan-500': $page.url===`/courses/create`}">เพิ่มรายวิชาใหม่</span>
                        </div>
                    </Link>
        </div>
    </div>
</template>
