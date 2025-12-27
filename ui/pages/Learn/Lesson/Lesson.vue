<script setup>
import { ref } from 'vue';
import { Head, usePage, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import MainLayout from "~/layouts/main.vue";
import LessonCoverProfile from '@/PlearndComponents/learn/courses/lessons/LessonCoverProfile.vue';
import AssignmentListViewer from '@/PlearndComponents/learn/courses/assignments/AssignmentListViewer.vue';
import CreateNewAssignmentCard from '@/PlearndComponents/learn/courses/assignments/CreateNewAssignmentCard.vue';
import QuestionsListViewer from '@/PlearndComponents/learn/courses/questions/QuestionsListViewer.vue';
import LessonImagesViewer from '@/PlearndComponents/learn/courses/lessons/LessonImagesViewer.vue';

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
                        <!-- <Link :href="`/courses/users/${$page.props.auth.user.id}`" as="button" type="button" @click.prevent="setActiveTab(5)" -->
                        <Link :href="'/courses/'+ props.course.data.id"
                            class="tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500': activeTab === 1 }">
                            <div class="flex flex-col items-center py-2 justify-center text-slate-600/80 ">
                                <Icon icon="icon-park-outline:view-grid-detail" class="w-8 h-8"
                                    :class="{'text-cyan-500': activeTab === 1}" />
                                <span class="hidden sm:block"
                                    :class="{'text-cyan-500': activeTab === 1}">รายวิชา</span>
                            </div>
                        </Link>
                        <!-- <Link :href="`/courses/users/${$page.props.auth.user.id}/member`" -->
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
                        <!-- <Link :href="`/courses`" -->
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
                            <div class="my-2 text-sm font-normal">{{ lesson.data.content }}</div>
                            <LessonImagesViewer 
                                :model_id="props.lesson.data.id"
                                :images="props.lesson.data.images"
                                :edit="isCourseAdmin"
                            />
                        </div>
                    </div>

                    <div v-if="activeTab===3" class="mt-4 space-y-4">
                        <AssignmentListViewer
                            :assignmentableType="'lessons'"
                            :assignmentableId="props.lesson.data.id"
                            :assignmentNameTh="'บทเรียน'"
                            :assignmentApiRoute="`/lessons/${props.lesson.data.id}`"
                            v-model:assignments="props.assignments.data"
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
                            :questionableType="'lessons'"
                            :questionableId="props.lesson.data.id"
                            :questionNameTh="'หัวข้อ'"
                            :questionApiRoute="`/lessons/${props.lesson.data.id}`"
                            v-model:questions="props.questions.data"
                            :quizId="props.lesson.data.id"
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


