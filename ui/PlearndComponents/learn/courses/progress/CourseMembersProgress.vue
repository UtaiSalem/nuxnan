<script setup>
import { ref, computed } from 'vue';
import { usePage } from "@inertiajs/vue3";

import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';
import MembersProgress from './MembersProgress.vue';

const activeGroupTab = ref(usePage().props.courseMemberOfAuth.last_accessed_group_tab);

async function setActiveGroupTab(tab) {
    activeGroupTab.value = tab;
    let resp = await axios.post(`/courses/${usePage().props.course.data.id}/members/${usePage().props.courseMemberOfAuth.id}/set-active-group-tab`, {group_tab: activeGroupTab.value});
    if (resp.data.success) {
        usePage().props.courseMemberOfAuth.last_accessed_group_tab = tab;
    }
}

const unGroupedMembers = ref(usePage().props.members.data.filter((member) => !member.group));

const activeGroupMembers = computed(() => {
    if (activeGroupTab.value < usePage().props.groups.data.length) {
        return usePage().props.groups.data[activeGroupTab.value].members;
    } else {
        return unGroupedMembers.value;
    }
});

</script>

<template>
    <div class="w-full mx-auto ">
        <section class="max-w-full" aria-multiselectable="false">
            <ul v-if="$page.props.isCourseAdmin"
                class="flex flex-wrap items-center border-b border-slate-200 plearnd-card" role="tablist">
                <li v-for="(group, index) in $page.props.groups.data" :key="index" class="w-1/3" role="presentation ">
                    <button @click.prevent="setActiveGroupTab(index)"
                        class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-300 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap hover:border-violet-600 focus:border-violet-700 text-violet-500 hover:text-violet-600 focus:text-violet-700 hover:bg-violet-50 focus:bg-violet-50 disabled:cursor-not-allowed disabled:border-slate-500 stroke-violet-500 hover:stroke-violet-600 focus:stroke-violet-700"
                        :class="activeGroupTab === index ? 'border-violet-500 bg-violet-200 font-bold' : 'font-medium'"
                        id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                        aria-controls="tab-panel-1a" aria-selected="true">
                        <span>{{ group.name }}</span>
                    </button>
                </li>
                <li>
                    <button @click.prevent="setActiveGroupTab($page.props.groups.data.length)"
                        class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-300 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap hover:border-violet-600 focus:border-violet-700 text-violet-500 hover:text-violet-600 focus:text-violet-700 hover:bg-violet-50 focus:bg-violet-50 disabled:cursor-not-allowed disabled:border-slate-500 stroke-violet-500 hover:stroke-violet-600 focus:stroke-violet-700"
                        :class="activeGroupTab === $page.props.groups.data.length ? 'border-violet-500 bg-violet-200 font-bold' : 'font-medium'"
                        id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                        aria-controls="tab-panel-1a" aria-selected="true">
                        <span>{{ 'ไม่มีกลุ่ม' }}</span>
                    </button>
                </li>
            </ul>
        </section>
        <section class="">
            <div class="" :class="{ 'pt-4': $page.props.isCourseAdmin }" id="tab-panel-1a" aria-hidden="false"
                role="tabpanel" aria-labelledby="tab-label-1a" tabindex="-1">
                <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">
                    <MembersProgress :members="activeGroupMembers" />
                </staggered-fade>
            </div>
        </section>
    </div>
</template>

