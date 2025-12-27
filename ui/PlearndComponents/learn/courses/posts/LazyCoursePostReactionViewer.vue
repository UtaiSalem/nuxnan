<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    post: Object,
    reactions: Object,
    model: String,
    isLoading: Boolean,
    reactionsCount: Number,
});

const emit = defineEmits(['reaction-updated']);

// State
const isProcessing = ref(false);
const userReaction = ref(null);
const showComments = ref(false);
const comments = ref([]);
const isLoadingComments = ref(false);

// Computed properties
const reactionCounts = computed(() => {
    if (!props.reactions) return { likes: 0, dislikes: 0 };
    return {
        likes: props.reactions.likes || 0,
        dislikes: props.reactions.dislikes || 0
    };
});

const hasUserReacted = computed(() => {
    return userReaction.value !== null;
});

const userReactionType = computed(() => {
    return userReaction.value;
});

// Methods
const authStore = useAuthStore();

const toggleLike = async () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    
    // Optimistic UI Update - ตัดแต้มทันทีก่อนรอ Backend
    const wasLiked = userReaction.value === 'like';
    const wasDisliked = userReaction.value === 'dislike';
    
    // คำนวณแต้มที่จะตัด (Course Post ใช้ 24 แต้ม)
    let pointsToDeduct = 0;
    if (wasLiked) {
        pointsToDeduct = 12; // ยกเลิกไลค์
    } else if (wasDisliked) {
        pointsToDeduct = 24; // ยกเลิก dislike (12) + เพิ่ม like (24) แต่รวมแล้ว 24
    } else {
        pointsToDeduct = 24; // เพิ่มไลค์
    }
    
    // ตัดแต้มใน UI ทันที
    authStore.deductPoints(pointsToDeduct);
    
    // อัพเดท UI ทันที
    const previousReaction = userReaction.value;
    userReaction.value = wasLiked ? null : 'like';
    
    emit('reaction-updated', {
        type: 'like',
        action: wasLiked ? 'removed' : 'added'
    });
    
    try {
        const response = await axios.post(`/posts/${props.post.id}/like`);
        
        if (!response.data.success) {
            // ถ้าไม่สำเร็จ คืนค่าเดิม
            authStore.rollback(pointsToDeduct);
            userReaction.value = previousReaction;
            
            emit('reaction-updated', {
                type: 'like',
                action: 'rollback'
            });
            
            // แสดง error message
            if (response.data.message) {
                alert(response.data.message);
            }
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        // คืนค่าเดิมถ้า error
        authStore.rollback(pointsToDeduct);
        userReaction.value = previousReaction;
        
        emit('reaction-updated', {
            type: 'like',
            action: 'rollback'
        });
    } finally {
        isProcessing.value = false;
    }
};

const toggleDislike = async () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    
    // Optimistic UI Update - ตัดแต้มทันทีก่อนรอ Backend
    const wasLiked = userReaction.value === 'like';
    const wasDisliked = userReaction.value === 'dislike';
    
    // คำนวณแต้มที่จะตัด (Course Post ใช้ 12 แต้มสำหรับ dislike)
    let pointsToDeduct = 0;
    if (wasDisliked) {
        pointsToDeduct = 12; // ยกเลิก dislike
    } else if (wasLiked) {
        pointsToDeduct = 24; // ยกเลิก like (12) + เพิ่ม dislike (12)
    } else {
        pointsToDeduct = 12; // เพิ่ม dislike
    }
    
    // ตัดแต้มใน UI ทันที
    authStore.deductPoints(pointsToDeduct);
    
    // อัพเดท UI ทันที
    const previousReaction = userReaction.value;
    userReaction.value = wasDisliked ? null : 'dislike';
    
    emit('reaction-updated', {
        type: 'dislike',
        action: wasDisliked ? 'removed' : 'added'
    });
    
    try {
        const response = await axios.post(`/posts/${props.post.id}/dislike`);
        
        if (!response.data.success) {
            // ถ้าไม่สำเร็จ คืนค่าเดิม
            authStore.rollback(pointsToDeduct);
            userReaction.value = previousReaction;
            
            emit('reaction-updated', {
                type: 'dislike',
                action: 'rollback'
            });
            
            // แสดง error message
            if (response.data.message) {
                alert(response.data.message);
            }
        }
    } catch (error) {
        console.error('Error toggling dislike:', error);
        // คืนค่าเดิมถ้า error
        authStore.rollback(pointsToDeduct);
        userReaction.value = previousReaction;
        
        emit('reaction-updated', {
            type: 'dislike',
            action: 'rollback'
        });
    } finally {
        isProcessing.value = false;
    }
};

const toggleComments = async () => {
    if (showComments.value) {
        showComments.value = false;
        return;
    }
    
    if (comments.value.length === 0) {
        await loadComments();
    }
    showComments.value = true;
};

const loadComments = async () => {
    if (isLoadingComments.value) return;
    
    isLoadingComments.value = true;
    try {
        const response = await axios.get(`/api/courses/${props.post.course_id}/posts/${props.post.id}/comments?limit=5`);
        if (response.data.success) {
            comments.value = response.data.comments;
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    } finally {
        isLoadingComments.value = false;
    }
};

// Initialize user reaction if available
onMounted(() => {
    if (props.reactions && props.reactions.user_reaction) {
        userReaction.value = props.reactions.user_reaction;
    }
});
</script>

<template>
    <div class="border-t border-gray-200 mt-4 pt-4">
        <!-- Loading skeleton -->
        <div v-if="isLoading" class="animate-pulse">
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                    <div class="h-8 w-20 bg-gray-300 rounded"></div>
                    <div class="h-8 w-20 bg-gray-300 rounded"></div>
                </div>
                <div class="h-8 w-20 bg-gray-300 rounded"></div>
            </div>
        </div>
        
        <!-- Show placeholder if there are no reactions but we know there should be -->
        <div v-if="!isLoading && reactions === null && reactionsCount > 0" class="flex justify-between items-center">
            <div class="flex space-x-4">
                <div class="text-gray-500 text-sm">มีการโต้ตอบ {{ reactionsCount }} รายการ</div>
            </div>
            <div class="text-gray-500 text-sm">คลิกเพื่อดูการโต้ตอบ</div>
        </div>
        
        <!-- Reactions section -->
        <div v-else-if="!isLoading && reactions !== null" class="space-y-4">
            <!-- Reaction buttons -->
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                    <!-- Like button -->
                    <button 
                        @click="toggleLike"
                        :disabled="isProcessing"
                        class="flex items-center space-x-2 px-3 py-2 rounded-lg transition-colors duration-200"
                        :class="{
                            'bg-blue-100 text-blue-600': userReactionType === 'like',
                            'hover:bg-gray-100 text-gray-600': userReactionType !== 'like'
                        }"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"></path>
                        </svg>
                        <span class="font-medium">{{ reactionCounts.likes }}</span>
                    </button>
                    
                    <!-- Dislike button -->
                    <button 
                        @click="toggleDislike"
                        :disabled="isProcessing"
                        class="flex items-center space-x-2 px-3 py-2 rounded-lg transition-colors duration-200"
                        :class="{
                            'bg-red-100 text-red-600': userReactionType === 'dislike',
                            'hover:bg-gray-100 text-gray-600': userReactionType !== 'dislike'
                        }"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 9.5a1.5 1.5 0 11-3 0v-6a1.5 1.5 0 013 0v6zM14 9.667v-5.43a2 2 0 00-1.105-1.79l-.05-.025A4 4 0 0011.055 2H5.64a2 2 0 00-1.962 1.608l-1.2 6A2 2 0 004.44 12H8v4a2 2 0 002 2 1 1 0 001-1v-.667a4 4 0 01.8-2.4l1.4-1.866a4 4 0 00.8-2.4z"></path>
                        </svg>
                        <span class="font-medium">{{ reactionCounts.dislikes }}</span>
                    </button>
                </div>
                
                <!-- Comments button -->
                <button 
                    @click="toggleComments"
                    class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">{{ props.post.comments_count || 0 }}</span>
                </button>
            </div>
            
            <!-- Comments section (lazy loaded) -->
            <div v-if="showComments" class="border-t border-gray-200 pt-4">
                <!-- Loading comments -->
                <div v-if="isLoadingComments" class="flex justify-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
                
                <!-- Comments list -->
                <div v-else-if="comments.length > 0" class="space-y-3">
                    <div v-for="comment in comments" :key="comment.id" class="flex space-x-3">
                        <img 
                            :src="comment.user.avatar" 
                            :alt="comment.user.name"
                            class="w-8 h-8 rounded-full"
                        >
                        <div class="flex-1 bg-gray-100 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-medium text-sm">{{ comment.user.name }}</span>
                                <span class="text-xs text-gray-500">{{ comment.created_at }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ comment.content }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- No comments -->
                <div v-else class="text-center text-gray-500 py-4">
                    ยังไม่มีความคิดเห็น
                </div>
            </div>
        </div>
    </div>
</template>