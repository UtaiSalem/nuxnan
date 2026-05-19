<script setup>
import { ref, reactive } from 'vue';
import { Head, usePage, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import MainLayout from "~/layouts/main.vue";
import LessonCoverProfile from '@/components/learn/course/lesson/LessonCoverProfile.vue';
import AssignmentListViewer from '@/components/learn/course/lesson/LessonAssignmentSection.vue';
import CreateNewAssignmentCard from '@/components/learn/course/lesson/CreateNewAssignmentCard.vue';
import QuestionsListViewer from '@/components/learn/course/lesson/LessonQuizSection.vue';
import LessonImagesViewer from '@/components/learn/course/lesson/LessonImagesViewer.vue';

const props = defineProps({
    isCourseAdmin: Boolean,
    academy: Object,
    course: Object,
    lesson: Object,
    topics: Object,
    assignments: Object,
    questions: Object,
    imagePath: String,
});

const activeTab = ref(2);
const isDarkMode = ref(false);
// const tempLogo = ref(props.course.data.user.avatar || '/storage/images/logos/default_logo.png');
const tempLogo = ref(props.course.data.user.avatar);
const tempCover = ref('/storage/images/courses/covers/default_cover.jpg');
const tempHeader = ref(props.course.data.name);
const tempSubheader = ref(props.course.data.code);


const setActiveTab = (tab) => activeTab.value = tab;

function onAddNewAssignmentHandler(newAsm){
    props.assignments.data.push(newAsm);
}

// Topic Management
const expandedTopics = ref([]);
const showTopicModal = ref(false);
const isEditingTopic = ref(false);
const editingTopic = ref(null);

const topicForm = reactive({
    title: '',
    content: '',
    youtube_url: '',
    min_read: 5,
    images: []
});

const toggleTopic = (topicId) => {
    if (expandedTopics.value.includes(topicId)) {
        expandedTopics.value = expandedTopics.value.filter(id => id !== topicId);
    } else {
        expandedTopics.value.push(topicId);
    }
};

const openCreateTopicModal = () => {
    isEditingTopic.value = false;
    editingTopic.value = null;
    topicForm.title = '';
    topicForm.content = '';
    topicForm.youtube_url = '';
    topicForm.min_read = 5;
    topicForm.images = [];
    
    // We'll implementing a modal using Swal for simplicity first, or a separate component later
    // For now, let's trigger a Swal with custom HTML or use a separate component
    openTopicSwal();
};

const editTopic = (topic) => {
    isEditingTopic.value = true;
    editingTopic.value = topic;
    topicForm.title = topic.title;
    topicForm.content = topic.content;
    topicForm.youtube_url = topic.youtube_url;
    topicForm.min_read = topic.min_read || 5;
    
    openTopicSwal();
};

import Swal from 'sweetalert2';
const api = useApi();

const openTopicSwal = () => {
    const isEdit = isEditingTopic.value;
    
    Swal.fire({
        title: isEdit ? 'แก้ไขหัวข้อย่อย' : 'เพิ่มหัวข้อย่อย',
        html: `
            <input id="swal-topic-title" class="swal2-input" placeholder="ชื่อหัวข้อ" value="${topicForm.title}">
            <textarea id="swal-topic-content" class="swal2-textarea" placeholder="เนื้อหา" rows="4">${topicForm.content || ''}</textarea>
            <input id="swal-topic-youtube" class="swal2-input" placeholder="YouTube URL (ถ้ามี)" value="${topicForm.youtube_url || ''}">
            <input id="swal-topic-min-read" type="number" class="swal2-input" placeholder="เวลาอ่าน (นาที)" value="${topicForm.min_read}">
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            return {
                title: (document.getElementById('swal-topic-title')).value,
                content: (document.getElementById('swal-topic-content')).value,
                youtube_url: (document.getElementById('swal-topic-youtube')).value,
                min_read: (document.getElementById('swal-topic-min-read')).value
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const data = result.value;
            
            try {
                if (isEdit) {
                    await api.put(`/api/lessons/${props.lesson.data.id}/topics/${editingTopic.value.id}`, data);
                    Swal.fire('สำเร็จ', 'แก้ไขข้อมูลเรียบร้อย', 'success');
                } else {
                   await api.post(`/api/lessons/${props.lesson.data.id}/topics`, data);
                   Swal.fire('สำเร็จ', 'เพิ่มหัวข้อเรียบร้อย', 'success');
                }
                // Refresh page or manually update list
                location.reload(); 
            } catch (err) {
                console.error(err);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
            }
        }
    });
};

const deleteTopic = async (topic) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "การกระทำนี้ไม่สามารถยกเลิกได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบเลย'
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/lessons/${props.lesson.data.id}/topics/${topic.id}`);
            Swal.fire('ลบสำเร็จ!', 'หัวข้อถูกลบแล้ว', 'success');
            location.reload();
        } catch (err) {
             Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบหัวข้อได้', 'error');
        }
    }
};
</script>

<template>
    <div>
        <MainLayout :title="props.lesson.data.title">           

            <template #coverProfileCard>
                <LessonCoverProfile 
                    :logo="tempLogo"  
                    :cover="tempCover"  
                    :name="tempHeader"
                    :code="tempSubheader"
                />
                <div class=" bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4">
                    <div class="flex flex-row justify-around">
                        <!-- <Link :href="`/Learn/Courses/users/${$page.props.auth.user.id}`" as="button" type="button" @click.prevent="setActiveTab(5)" -->
                        <Link :href="'/Learn/Courses/'+ props.course.data.id"
                            class="tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500': activeTab === 1 }">
                            <div class="flex flex-col items-center py-2 justify-center text-slate-600/80 ">
                                <Icon icon="icon-park-outline:view-grid-detail" class="w-8 h-8"
                                    :class="{'text-cyan-500': activeTab === 1}" />
                                <span class="hidden sm:block"
                                    :class="{'text-cyan-500': activeTab === 1}">รายวิชา</span>
                            </div>
                        </Link>
                        <!-- <Link :href="`/Learn/Courses/users/${$page.props.auth.user.id}/member`" -->
                        <button type="button" @click.prevent="setActiveTab(2)"
                            class="tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500': activeTab===2}">
                            <div class="flex flex-col items-center py-2 justify-center text-slate-600/80 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8"
                                    :class="{'text-cyan-500': activeTab===2}" />
                                <span class="hidden sm:block"
                                    :class="{'text-cyan-500': activeTab===2}">เนื้อหา</span>
                            </div>
                        </button>
                        <button type="button" @click.prevent="setActiveTab(3)"
                            class="tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500': activeTab===3}">
                            <div class="flex flex-col items-center py-2 justify-center text-slate-600/80 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8"
                                    :class="{'text-cyan-500': activeTab===3}" />
                                <span class="hidden sm:block"
                                    :class="{'text-cyan-500': activeTab===3}">แบบฝึกหัด</span>
                            </div>
                        </button>
                        <!-- <Link :href="`/Learn/Courses`" -->
                        <button type="button" @click.prevent="setActiveTab(4)"
                            class="tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500': activeTab === 4}">
                            <div class="flex flex-col items-center py-2 justify-center text-slate-600/80 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8"
                                    :class="{'text-cyan-500': activeTab === 4}" />
                                <span class="hidden sm:block" :class="{'text-cyan-500': activeTab === 4}">แบบทดสอบ</span>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template #leftSideWidget>
                <div>
                    <!-- <ActivityCardVue /> -->
                </div>
            </template>

            <template #mainContent>
                <div class="w-full space-y-4">

                    <div v-if="activeTab===2" class="plearnd-card my-4">
                        <div class="flex flex-col" >
                            <div v-if="lesson.data.youtube_url">
                                <!-- youtube div element -->
                                <vue-plyr>
                                    <div data-plyr-provider="youtube" :data-plyr-embed-id="lesson.data.youtube_url"></div>
                                </vue-plyr>
                            </div>

                            <div class="my-4 text-md font-bold">{{ lesson.data.title }}</div>
                            <div class="text-md font-bold">คำอธิบายบทเรียน</div>
                            <div class="my-4 text-sm font-semibold text-gray-600">{{ lesson.data.description }}</div>
                            <div class="text-sm font-semibold">เนื้อหา</div>
                            <div class="my-2 text-sm font-normal">
                                <CommonRichTextViewer :content="lesson.data.content" :can-expand="false" />
                            </div>
                            <LessonImagesViewer 
                                :model_id="props.lesson.data.id"
                                :images="props.lesson.data.images"
                                :edit="isCourseAdmin"
                            />

                            <!-- Topics Section -->
                            <div class="mt-8 border-t border-gray-200 pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold">หัวข้อย่อย (Topics)</h3>
                                    <button 
                                        v-if="isCourseAdmin"
                                        @click="openCreateTopicModal"
                                        class="px-3 py-1.5 bg-cyan-600 text-white text-sm rounded-md hover:bg-cyan-700 flex items-center gap-2"
                                    >
                                        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
                                        เพิ่มหัวข้อ
                                    </button>
                                </div>

                                <div v-if="props.lesson.data.topics && props.lesson.data.topics.length > 0" class="space-y-4">
                                    <div 
                                        v-for="(topic, index) in props.lesson.data.topics" 
                                        :key="topic.id"
                                        class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50"
                                    >
                                        <div 
                                            class="p-4 bg-white flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
                                            @click="toggleTopic(topic.id)"
                                        >
                                            <div class="flex items-center gap-3">
                                                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-sm">
                                                    {{ index + 1 }}
                                                </span>
                                                <div class="font-medium text-gray-900">{{ topic.title }}</div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <!-- Admin Actions -->
                                                <div v-if="isCourseAdmin" class="flex items-center gap-1 mr-4" @click.stop>
                                                    <button 
                                                        @click="editTopic(topic)"
                                                        class="p-1.5 text-gray-400 hover:text-blue-600 rounded-full hover:bg-blue-50"
                                                        title="แก้ไข"
                                                    >
                                                        <Icon icon="fluent:edit-20-regular" class="w-4 h-4" />
                                                    </button>
                                                    <button 
                                                        @click="deleteTopic(topic)"
                                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded-full hover:bg-red-50"
                                                        title="ลบ"
                                                    >
                                                        <Icon icon="fluent:delete-20-regular" class="w-4 h-4" />
                                                    </button>
                                                </div>
                                                <Icon 
                                                    icon="fluent:chevron-down-24-regular" 
                                                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                                    :class="{ 'rotate-180': expandedTopics.includes(topic.id) }"
                                                />
                                            </div>
                                        </div>

                                        <!-- Topic Content (Collapsible) -->
                                        <div v-show="expandedTopics.includes(topic.id)" class="border-t border-gray-200 p-4">
                                            <!-- YouTube -->
                                            <div v-if="topic.youtube_url" class="mb-4 aspect-video rounded-lg overflow-hidden bg-black">
                                                 <vue-plyr>
                                                    <div data-plyr-provider="youtube" :data-plyr-embed-id="topic.youtube_url"></div>
                                                </vue-plyr>
                                            </div>

                                            <div v-if="topic.content" class="mb-4">
                                                <CommonRichTextViewer :content="topic.content" :can-expand="false" />
                                            </div>

                                            <!-- Images -->
                                            <div v-if="topic.images && topic.images.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <div v-for="img in topic.images" :key="img.id" class="rounded-lg overflow-hidden border border-gray-200 aspect-square group relative">
                                                    <img :src="`/storage/images/courses/lessons/topics/${img.filename}`" class="w-full h-full object-cover">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                    <p>ยังไม่มีหัวข้อย่อยในบทเรียนนี้</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab===3" class="mt-4 space-y-4">
                        <AssignmentListViewer
                            :assignments="props.assignments.data"
                            :lessonId="props.lesson.data.id"
                        />

                        <CreateNewAssignmentCard v-if="$page.props.isCourseAdmin"
                            :assignmentableType="'lessons'"
                            :assignmentableId="props.lesson.data.id"
                            :assignmentNameTh="'บทเรียน'"
                            :assignmentApiRoute="`/lessons/${props.lesson.data.id}`"
                            @add-new-assignment="(newAsm) => { onAddNewAssignmentHandler(newAsm) }"
                        />
                    </div>
                    <div v-if="activeTab===4" class="mt-4">
                        <QuestionsListViewer
                            :questions="props.questions.data"
                            :lessonId="props.lesson.data.id"
                        />
                    </div>
                </div>
            </template>

            <template #rightSideWidget>
                <div>
                    <!-- <MeassageCardVue /> -->
                </div>
            </template>
        </MainLayout>       
    </div>
</template>


