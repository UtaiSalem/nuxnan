<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import CourseLayout from '@/layouts/CourseLayout.vue';
import StaggeredFade from '@/components/accessories/StaggeredFade.vue';
import NonGroupedMemberList from '@/components/learn/course/members/NonGroupedMemberList.vue'

const props = defineProps({
    isCourseAdmin: Boolean,
    course: Object,
    lessons: Object,
    groups: Object,
    members: Object,
    courseMemberOfAuth: Object,
});

const api = useApi()
const pendingMembers = ref([...(props.members?.data ?? [])]);
const processingId = ref(null);

async function approve(member) {
    processingId.value = member.id;
    try {
        await api.post(`/api/courses/${props.course.id}/members/${member.id}/approve`);
        pendingMembers.value = pendingMembers.value.filter(m => m.id !== member.id);
    } catch (e) {
        console.error('approve failed', e);
    } finally {
        processingId.value = null;
    }
}

async function reject(member) {
    const confirmed = confirm(`ยืนยันปฏิเสธคำขอของ "${member.user?.name}"?`);
    if (!confirmed) return;
    processingId.value = member.id;
    try {
        await api.post(`/api/courses/${props.course.id}/members/${member.id}/reject`);
        pendingMembers.value = pendingMembers.value.filter(m => m.id !== member.id);
    } catch (e) {
        console.error('reject failed', e);
    } finally {
        processingId.value = null;
    }
}
</script>

<template>
    <div>
     <CourseLayout
        :isCourseAdmin
        :course
        :lessons
        :groups
        :courseMemberOfAuth
        :activeTab="6"
     >
        <template #courseContent>
            <div class="mt-4 space-y-4">

                <!-- Pending requests section (admin only) -->
                <div v-if="isCourseAdmin && pendingMembers.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <Icon icon="heroicons:clock" class="h-5 w-5 text-amber-600" />
                        <h3 class="font-bold text-amber-800">
                            คำขอเข้าเรียน
                            <span class="ml-1 rounded-full bg-amber-500 px-2 py-0.5 text-xs text-white">
                                {{ pendingMembers.length }}
                            </span>
                        </h3>
                    </div>

                    <ul class="space-y-2">
                        <li
                            v-for="member in pendingMembers"
                            :key="member.id"
                            class="flex items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 shadow-sm"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <img
                                    v-if="member.user?.profile_image"
                                    :src="member.user.profile_image"
                                    class="h-9 w-9 rounded-full object-cover"
                                />
                                <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm">
                                    {{ member.user?.name?.charAt(0) ?? '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-800 text-sm">{{ member.user?.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ member.user?.email }}</p>
                                    <p v-if="member.group" class="mt-0.5 text-xs text-indigo-600">
                                        <Icon icon="heroicons:user-group" class="inline h-3 w-3" />
                                        {{ member.group?.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button
                                    @click="approve(member)"
                                    :disabled="processingId === member.id"
                                    class="flex items-center gap-1 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600 disabled:opacity-50 transition-colors"
                                >
                                    <Icon v-if="processingId === member.id" icon="svg-spinners:ring-resize" class="h-4 w-4" />
                                    <Icon v-else icon="heroicons:check" class="h-4 w-4" />
                                    อนุมัติ
                                </button>
                                <button
                                    @click="reject(member)"
                                    :disabled="processingId === member.id"
                                    class="flex items-center gap-1 rounded-lg bg-red-100 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-200 disabled:opacity-50 transition-colors"
                                >
                                    <Icon v-if="processingId === member.id" icon="svg-spinners:ring-resize" class="h-4 w-4" />
                                    <Icon v-else icon="heroicons:x-mark" class="h-4 w-4" />
                                    ปฏิเสธ
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- All members list -->
                <StaggeredFade :duration="50" tag="ul" class="flex flex-col w-full">
                    <NonGroupedMemberList
                        :members="props.members.data"
                        :groups="props.groups.data"
                    />
                </StaggeredFade>

            </div>
        </template>
     </CourseLayout>
    </div>
</template>
