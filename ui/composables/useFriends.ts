export const useFriends = () => {
    const chatStore = useChatStore()

    const fetchFriends = async () => {
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500))
        const mockFriends = [
            { id: 1, name: 'Sarah Jane', avatar: '/images/resources/user2.jpg', status: 'online' },
            { id: 2, name: 'John Doe', avatar: '/images/resources/user3.jpg', status: 'offline' },
            { id: 3, name: 'Emily Clark', avatar: '/images/resources/user4.jpg', status: 'online' }
        ]
        chatStore.setFriends(mockFriends)
    }

    return {
        fetchFriends
    }
}
