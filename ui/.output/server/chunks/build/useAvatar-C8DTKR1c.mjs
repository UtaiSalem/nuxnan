import { b as useRuntimeConfig } from './server.mjs';

const getInitials = (name) => {
  if (!name) return "?";
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }
  return name.charAt(0).toUpperCase();
};
const getColorFromName = (name) => {
  if (!name) return "f5f5f5";
  const colors = [
    "f5f5f5",
    // soft white/cream
    "fafafa",
    // near white
    "f0f0f0",
    // light gray
    "e8e8e8",
    // slightly darker gray
    "f5f0e8",
    // warm cream
    "f0f5f0",
    // soft green tint
    "f0f0f5",
    // soft blue tint
    "f5f0f5",
    // soft purple tint
    "f5f5f0",
    // soft yellow tint
    "f0f5f5"
    // soft cyan tint
  ];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
};
const useAvatar = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const getAvatarUrl = (userData, size = 128) => {
    if (!userData) return generateFromName("User", size);
    const avatarPath = userData.avatar || userData.profile_photo_path || userData.profile_photo_url;
    if (avatarPath) {
      if (avatarPath.startsWith("http")) return avatarPath;
      if (avatarPath.startsWith("/storage")) return `${apiBase}${avatarPath}`;
      if (avatarPath.startsWith("/")) return avatarPath;
      return `${apiBase}/storage/${avatarPath}`;
    }
    const name = userData.name || userData.username || "User";
    return generateFromName(name, size);
  };
  const generateFromName = (name, size = 128) => {
    const initials = getInitials(name);
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&color=7F9CF5&background=EBF4FF`;
  };
  const getNameInitials = (name) => {
    return getInitials(name);
  };
  const getNameColor = (name) => {
    return getColorFromName(name);
  };
  return {
    getAvatarUrl,
    generateFromName,
    getNameInitials,
    getNameColor
  };
};

export { useAvatar as u };
//# sourceMappingURL=useAvatar-C8DTKR1c.mjs.map
