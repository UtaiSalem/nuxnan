<script setup>
import PendingFriendRequester from '@/PlearndComponents/widgets/friends/PendingFriendRequester.vue';

const props = defineProps({
    pendingFriends: Object,
});

</script>

<template>
    <div class="bg-white p-4 rounded-lg shadow-lg border-t-4 border-blue-500">
        <p class="text-lg font-bold mb-2">คำขอเป็นเพื่อน</p>
        <hr>
        <!-- no friend request -->
        <div v-if="!props.pendingFriends.data.length" class="text-center">
            <p class="text-gray-500 mt-2">ไม่มีคำขอเป็นเพื่อน</p>
        </div>

        <div v-else v-for="(friendship, index) in props.pendingFriends.data" :key="friendship.id">
                <PendingFriendRequester 
                    :friendship 
                    @splice-friend-request="props.pendingFriends.data.splice(index, 1)"
                />
            <hr>
        </div>
    </div>
</template>
