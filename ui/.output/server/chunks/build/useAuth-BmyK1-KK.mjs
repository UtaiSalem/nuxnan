import { d as useAuthStore } from './server.mjs';
import { computed } from 'vue';

const useAuth = () => {
  const authStore = useAuthStore();
  const login = async (credentials) => {
    try {
      await authStore.login(credentials);
      return true;
    } catch (error) {
      throw error;
    }
  };
  const register = async (userData) => {
    await new Promise((resolve) => setTimeout(resolve, 500));
    return true;
  };
  const user = computed(() => authStore.user);
  const isAuthenticated = computed(() => authStore.isAuthenticated);
  return {
    user,
    isAuthenticated,
    login,
    register
  };
};

export { useAuth as u };
//# sourceMappingURL=useAuth-BmyK1-KK.mjs.map
