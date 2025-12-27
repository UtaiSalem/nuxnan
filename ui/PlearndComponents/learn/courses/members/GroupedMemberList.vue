<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from "@inertiajs/vue3";
import Swal from 'sweetalert2';

import MemberCard from '@/PlearndComponents/learn/courses/members/MemberCard.vue'
import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';

const props = defineProps({
    groups: {
        type: Object,
        default: () => ({})
    },
});

// const emit = defineEmits(['request-unmember-course']);
const authGroup = computed(() => {
    return usePage().props.groups.data.find((group) => group.id === usePage().props.courseMemberOfAuth.group_id);
});

const activeGroupTab = ref(usePage().props.courseMemberOfAuth ? usePage().props.courseMemberOfAuth.last_accessed_group_tab : 0);
const unGroupedMembers = ref(usePage().props.members.data.filter((member)=> !member.group));

const groupMembers = ref(authGroup.value ? authGroup.value.members : props.groups[activeGroupTab.value].members);

const sorting = ref(0);


function requestToBeUnMember(memberId, memberIndx) {
    if (usePage().props.courseMemberOfAuth.id === memberId) {
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: "ไม่สามารถลบสมาชิกได้เนื่องจากคุณเป็นผู้ดูแลรายวิชา!",
            icon: 'warning',
            showConfirmButton: false,
            timer: 1000
        });
        return;
    };

    Swal.fire({
        title: 'ยืนยันการลบสมาชิกออกจากรายวิชาหรือไม่?',
        text: "คุณจะไม่สามารถกู้คืนข้อมูลสมาชิกนี้ได้อีกครั้ง!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบออก!',
        cancelButtonText: 'ยกเลิก'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                let unmemberCourseResp = await axios.delete(`/courses/${usePage().props.course.data.id}/members/${memberId}/delete`);
                if (unmemberCourseResp.data && unmemberCourseResp.data.success) {
                    // console.log(unmemberCourseResp.data);
                    if (activeGroupTab.value < usePage().props.groups.data.length) {
                        groupMembers.value.splice(memberIndx, 1);
                    }else{
                        unGroupedMembers.value.splice(memberIndx, 1);
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสมาชิกเรียบร้อย!',
                        text: 'สมาชิกถูกลบออกจากรายวิชาแล้ว',
                        showConfirmButton: false,
                        timer: 1200
                    });
                }
            } catch (error) {
                console.log(error);
            }
        }
    });

}

const setActiveGroupTab = async (tab) =>{
    activeGroupTab.value = tab ;
    sorting.value = 0;
    
    if (activeGroupTab.value < props.groups.length) {
        groupMembers.value = props.groups[activeGroupTab.value].members.sort((a, b) => a.order_number - b.order_number);
    }else{
        groupMembers.value = unGroupedMembers.value;
    }

    if (usePage().props.courseMemberOfAuth && (tab < props.groups.length)) {
         let resp = await axios.post(`/courses/${usePage().props.course.data.id}/members/${usePage().props.courseMemberOfAuth.id}/set-active-group-tab`, {group_tab: activeGroupTab.value});
         if (resp.data.success) {
             usePage().props.courseMemberOfAuth.last_accessed_group_tab = tab;
         }
    }
}

const setSorting = (sort) => {
    sorting.value = sort;
    if (sort === 1) {
        groupMembers.value.sort((a, b) => b.achieved_score - a.achieved_score);
    } else {
        groupMembers.value.sort((a, b) => a.order_number - b.order_number);
    }
}

onMounted(() => {
    if (usePage().props.courseMemberOfAuth) {
        if (usePage().props.isCourseAdmin){
            activeGroupTab.value = usePage().props.courseMemberOfAuth.last_accessed_group_tab ?? 0 ;
            if (activeGroupTab.value < props.groups.length) {
                groupMembers.value = props.groups[activeGroupTab.value].members.sort((a, b) => a.order_number - b.order_number);
            }else{
                groupMembers.value = unGroupedMembers.value;
            }
        }else{
            if(authGroup.value) {
                groupMembers.value = authGroup.value.members.sort((a, b) => a.order_number - b.order_number);
            }else{
                groupMembers.value = unGroupedMembers.value;
            }
        }
    }
});
</script>

<template>
    <div class="w-full mx-auto ">
        <section class="max-w-full" aria-multiselectable="false">
            <ul v-if="$page.props.isCourseAdmin" class="flex flex-wrap items-center border-b border-slate-200 plearnd-card" role="tablist">
                <li v-for="(group, index) in $page.props.groups.data" :key="index" class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation ">
                    <button @click.prevent="setActiveGroupTab(index)"
                        class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-300 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap hover:border-violet-600 focus:border-violet-700 text-violet-500 hover:text-violet-600 focus:text-violet-700 hover:bg-violet-50 focus:bg-violet-50 disabled:cursor-not-allowed disabled:border-slate-500 stroke-violet-500 hover:stroke-violet-600 focus:stroke-violet-700"
                        :class="activeGroupTab === index ? 'border-violet-500 bg-violet-200 font-bold':'font-medium'"
                        id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                        aria-controls="tab-panel-1a" aria-selected="true">
                        <span>{{ group.name + ' (' + group.members.length + ')' }}</span>
                        
                    </button>
                </li>
                <li v-if="unGroupedMembers.filter(member => member.user.id !== $page.props.course.data.user.id).length > 0" class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation ">
                    <button @click.prevent="setActiveGroupTab($page.props.groups.data.length)"
                        class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-300 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap hover:border-violet-600 focus:border-violet-700 text-violet-500 hover:text-violet-600 focus:text-violet-700 hover:bg-violet-50 focus:bg-violet-50 disabled:cursor-not-allowed disabled:border-slate-500 stroke-violet-500 hover:stroke-violet-600 focus:stroke-violet-700"
                        :class="activeGroupTab === $page.props.groups.data.length ? 'border-violet-500 bg-violet-200 font-bold':'font-medium'"
                        id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                        aria-controls="tab-panel-1a" aria-selected="true">
                        <span>{{ 'ไม่มีกลุ่ม' + ' (' + unGroupedMembers.filter(member => member.user.id !== $page.props.course.data.user.id).length + ')' }}</span>
                    </button>
                </li>
            </ul>

            <!-- Sort options button -->
            <div class="flex w-full rounded shadow-lg" :class="{'mt-4': $page.props.isCourseAdmin}">
                <button @click.prevent="setSorting(0)"
                    class="w-full flex justify-center font-medium rounded-l-lg px-5 py-2"
                    :class="sorting === 0 ? 'bg-violet-400 text-white' : 'bg-white text-gray-500'"
                    >
                    เรียงตามเลขที่
                </button>
                <button @click.prevent="setSorting(1)"
                    class="w-full flex justify-center font-medium rounded-r-lg px-5 py-2"
                    :class="sorting === 1 ? 'bg-violet-400 text-white' : 'bg-white text-gray-500'"
                    >
                    เรียงตามคะแนน
                </button>
            </div>
            <!-- Sort options button -->


            <div class="pt-4" id="tab-panel-1a" aria-hidden="false" role="tabpanel" aria-labelledby="tab-label-1a"
                tabindex="-1">
                <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">

                    <MemberCard  v-for="(member, index) in groupMembers" :key="index"
                        :data-index="index"
                        :member="member"
                        @request-unmember-course="requestToBeUnMember(member.id, index)"
                    />

                </staggered-fade>

            </div>
        </section>
    </div>
</template>
