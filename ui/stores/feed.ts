import { defineStore } from 'pinia'

export const useFeedStore = defineStore('feed', () => {
    const posts = ref([])
    const trendingTopics = ref([])

    function setPosts(newPosts) {
        posts.value = newPosts
    }

    function addPost(post) {
        posts.value.unshift(post)
    }

    function updatePost(postId, updatedPost) {
        const index = posts.value.findIndex(p => {
            // Handle activity-wrapped posts
            if (p.target_resource && p.target_resource.id === postId) return true
            return p.id === postId
        })
        if (index !== -1) {
            // If it's an activity-wrapped post, update target_resource
            if (posts.value[index].target_resource) {
                posts.value[index].target_resource = { ...posts.value[index].target_resource, ...updatedPost }
            } else {
                posts.value[index] = { ...posts.value[index], ...updatedPost }
            }
        }
    }

    function removePost(postId) {
        posts.value = posts.value.filter(p => {
            if (p.target_resource && p.target_resource.id === postId) return false
            return p.id !== postId
        })
    }

    function setTrending(topics) {
        trendingTopics.value = topics
    }

    return {
        posts,
        trendingTopics,
        setPosts,
        addPost,
        updatePost,
        removePost,
        setTrending
    }
})
