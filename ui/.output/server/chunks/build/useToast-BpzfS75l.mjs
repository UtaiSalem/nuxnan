import { e as useNotificationStore } from './server.mjs';

const useToast = () => {
  const notificationStore = useNotificationStore();
  return {
    success: (message, title, duration) => {
      return notificationStore.success(message, title, duration);
    },
    error: (message, title, duration) => {
      return notificationStore.error(message, title, duration);
    },
    warning: (message, title, duration) => {
      return notificationStore.warning(message, title, duration);
    },
    info: (message, title, duration) => {
      return notificationStore.info(message, title, duration);
    },
    // For custom notifications
    show: (notification) => {
      return notificationStore.add(notification);
    },
    // Remove specific notification
    remove: (id) => {
      notificationStore.remove(id);
    },
    // Clear all
    clear: () => {
      notificationStore.clear();
    }
  };
};

export { useToast as u };
//# sourceMappingURL=useToast-BpzfS75l.mjs.map
