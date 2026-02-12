<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    lessonId: {
        type: Number,
        required: true
    },
    currentUser: {
        type: Object,
        required: true
    }
});

const toast = useToast();
const confirm = useConfirm();

const comments = ref([]);
const newComment = ref('');
const isSubmittingComment = ref(false);
const isLoadingComments = ref(true);
const editingCommentId = ref(null);
const editedCommentContent = ref('');

const fetchComments = async () => {
    isLoadingComments.value = true;
    try {
        const response = await axios.get(`/api/lessons/${props.lessonId}/comments`);
        comments.value = response.data;
    } catch (error) {
        console.error('Error fetching comments:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load comments', life: 3000 });
    } finally {
        isLoadingComments.value = false;
    }
};

const submitComment = async () => {
    if (!newComment.value.trim()) return;
    
    isSubmittingComment.value = true;
    try {
        const response = await axios.post(`/api/lessons/${props.lessonId}/comments`, {
            content: newComment.value
        });
        comments.value.unshift(response.data);
        newComment.value = '';
        toast.add({ severity: 'success', summary: 'Success', detail: 'Comment added successfully', life: 3000 });
    } catch (error) {
        console.error('Error submitting comment:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to add comment', life: 3000 });
    } finally {
        isSubmittingComment.value = false;
    }
};

const startEditingComment = (comment) => {
    editingCommentId.value = comment.id;
    editedCommentContent.value = comment.content;
};

const cancelEditingComment = () => {
    editingCommentId.value = null;
    editedCommentContent.value = '';
};

const updateComment = async (comment) => {
    try {
        const response = await axios.put(`/api/comments/${comment.id}`, {
            content: editedCommentContent.value
        });
        const index = comments.value.findIndex(c => c.id === comment.id);
        comments.value[index] = response.data;
        cancelEditingComment();
        toast.add({ severity: 'success', summary: 'Success', detail: 'Comment updated successfully', life: 3000 });
    } catch (error) {
        console.error('Error updating comment:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update comment', life: 3000 });
    }
};

const deleteComment = (comment) => {
    confirm.require({
        message: 'Are you sure you want to delete this comment?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                await axios.delete(`/api/comments/${comment.id}`);
                comments.value = comments.value.filter(c => c.id !== comment.id);
                toast.add({ severity: 'success', summary: 'Success', detail: 'Comment deleted successfully', life: 3000 });
            } catch (error) {
                console.error('Error deleting comment:', error);
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete comment', life: 3000 });
            }
        }
    });
};

onMounted(() => {
    fetchComments();
});
</script>

<template>
    <div class="course-lesson-comments">
        <h3 class="text-xl font-semibold mb-4">ความคิดเห็น</h3>
        
        <!-- ฟอร์มสำหรับเพิ่มความคิดเห็นใหม่ -->
        <div class="mb-4">
            <Textarea
                v-model="newComment"
                placeholder="เพิ่มความคิดเห็นของคุณ..."
                :autoResize="true"
                rows="3"
                class="w-full mb-2"
            />
            <Button 
                label="ส่งความคิดเห็น" 
                @click="submitComment"
                :loading="isSubmittingComment"
            />
        </div>

        <!-- รายการความคิดเห็น -->
        <div v-if="isLoadingComments" class="text-center">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        <div v-else-if="comments.length > 0" class="space-y-4">
            <div v-for="comment in comments" :key="comment.id" class="bg-gray-100 p-4 rounded-lg">
                <div class="flex items-center mb-2">
                    <img :src="comment.user.avatar" alt="User Avatar" class="w-8 h-8 rounded-full mr-2">
                    <span class="font-semibold">{{ comment.user.name }}</span>
                </div>
                <div v-if="editingCommentId === comment.id">
                    <Textarea
                        v-model="editedCommentContent"
                        :autoResize="true"
                        rows="3"
                        class="w-full mb-2"
                    />
                    <Button label="Update" @click="updateComment(comment)" class="mr-2" />
                    <Button label="Cancel" @click="cancelEditingComment" class="p-button-secondary" />
                </div>
                <p v-else>{{ comment.content }}</p>
                <div class="mt-2 flex justify-between items-center">
                    <span class="text-sm text-gray-500">{{ comment.created_at }}</span>
                    <div v-if="currentUser.id === comment.user_id || currentUser.isAdmin">
                        <Button icon="pi pi-pencil" @click="startEditingComment(comment)" class="p-button-text p-button-sm mr-2" />
                        <Button icon="pi pi-trash" @click="deleteComment(comment)" class="p-button-text p-button-sm p-button-danger" />
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="text-gray-500 italic">ยังไม่มีความคิดเห็น</p>

        <ConfirmDialog></ConfirmDialog>
    </div>
</template>
