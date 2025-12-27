export const useTrending = () => {
    const feedStore = useFeedStore()

    const fetchTrending = async () => {
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500))
        const mockTrending = [
            { tag: '#VueJS', count: '12k Posts' },
            { tag: '#Nuxt3', count: '8.5k Posts' },
            { tag: '#TailwindCSS', count: '15k Posts' },
            { tag: '#WebDev', count: '20k Posts' }
        ]
        feedStore.setTrending(mockTrending)
    }

    return {
        fetchTrending
    }
}
