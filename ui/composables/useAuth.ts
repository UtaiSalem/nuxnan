export const useAuth = () => {
    const authStore = useAuthStore()

    const login = async (credentials) => {
        try {
            await authStore.login(credentials)
            return true
        } catch (error) {
            throw error
        }
    }

    const register = async (userData) => {
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500))
        return true
    }

    return {
        login,
        register
    }
}
