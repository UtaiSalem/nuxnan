import { defineStore } from 'pinia'

export const useChatStore = defineStore('chat', () => {
    const messages = ref([])
    const friends = ref([])

    function setFriends(friendList) {
        friends.value = friendList
    }

    function addMessage(message) {
        messages.value.push(message)
    }

    return {
        messages,
        friends,
        setFriends,
        addMessage
    }
})
