<script setup>

import { ref, onMounted, defineComponent, h, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';

// Icon definitions using Iconify
const icons = {
  home: 'lucide:home',
  briefcase: 'lucide:briefcase',
  helpCircle: 'lucide:help-circle',
  info: 'lucide:info',
  fileText: 'lucide:file-text',
  mail: 'lucide:mail',
  shield: 'lucide:shield',
  radio: 'lucide:radio',
  barChart3: 'lucide:bar-chart-3',
  users: 'lucide:users',
  user: 'lucide:user',
  award: 'lucide:award',
  target: 'lucide:target',
  video: 'lucide:video',
  calendar: 'lucide:calendar',
  messageSquare: 'lucide:message-square',
  shoppingBag: 'lucide:shopping-bag',
  userCircle: 'lucide:user-circle',
  lock: 'lucide:lock',
  settings: 'lucide:settings',
  usersRound: 'lucide:users-round',
  send: 'lucide:send',
  store: 'lucide:store',
  dollarSign: 'lucide:dollar-sign',
  package: 'lucide:package',
  download: 'lucide:download',
  search: 'lucide:search',
  logOut: 'lucide:log-out',
  menu: 'lucide:menu',
  x: 'lucide:x',
  sparkles: 'lucide:sparkles',
  zap: 'lucide:zap',
  chevronLeft: 'lucide:chevron-left',
  chevronRight: 'lucide:chevron-right',
  star: 'lucide:star',
  check: 'lucide:check-circle-2',
  trophy: 'lucide:trophy'
};

const page = usePage();
const user = computed(() => page.props.auth.user);

// Reactive state
const activeMenu = ref('primary');
const isMobileMenuOpen = ref(false);
const searchQuery = ref('');
const currentXP = ref(0);
const nextLevelXP = ref(1000);
const hoveredItem = ref(null);
const activeItem = ref('newsfeed');
const showTooltip = ref(null);
const isCollapsed = ref(true); // Start in collapsed state
const isHovering = ref(false);

// Tabs
const tabs = [
  { key: 'global', label: '🌐 ทั่วไป' },
  { key: 'primary', label: '🎯 ชุมชน' },
  { key: 'account', label: '👤 บัญชี' }
];

// Navigation items
const globalNavItems = [
  { icon: icons.home, label: 'หน้าแรก', href: '#home', color: 'from-blue-500 to-cyan-500', tooltip: 'กลับสู่หน้าหลัก', id: 'home' },
  { icon: icons.briefcase, label: 'ตำแหน่งงาน', href: '#careers', color: 'from-purple-500 to-pink-500', tooltip: 'โอกาสในการทำงาน', id: 'careers' },
  { icon: icons.helpCircle, label: 'คำถาม', href: '#faqs', color: 'from-green-500 to-emerald-500', tooltip: 'คำถามที่พบบ่อย', id: 'faqs' },
  { icon: icons.info, label: 'เกี่ยวกับเรา', href: '#about', color: 'from-orange-500 to-red-500', tooltip: 'เกี่ยวกับเรา', id: 'about' },
  { icon: icons.fileText, label: 'บล็อก', href: '#blog', color: 'from-indigo-500 to-purple-500', tooltip: 'บทความและข่าวสาร', id: 'blog' },
  { icon: icons.mail, label: 'ติดต่อ', href: '#contact', color: 'from-pink-500 to-rose-500', tooltip: 'ติดต่อเรา', id: 'contact' },
  { icon: icons.shield, label: 'นโยบายความเป็นส่วนตัว', href: '#privacy', color: 'from-teal-500 to-cyan-500', tooltip: 'นโยบายความเป็นส่วนตัว', id: 'privacy' }
];

const primaryNavItems = [
  { icon: icons.radio, label: 'ฟีดข่าว', href: '#newsfeed', color: 'from-cyan-400 to-blue-500', tooltip: 'ฟีดข่าวสารล่าสุด', id: 'newsfeed' },
  { icon: icons.barChart3, label: 'ภาพรวม', href: '#overview', color: 'from-violet-500 to-purple-500', tooltip: 'ภาพรวมของคุณ', id: 'overview' },
  { icon: icons.users, label: 'กลุ่ม', href: '#groups', color: 'from-emerald-500 to-teal-500', tooltip: 'กลุ่มและชุมชน', id: 'groups' },
  { icon: icons.user, label: 'สมาชิก', href: '#members', color: 'from-blue-500 to-indigo-500', tooltip: 'สมาชิกทั้งหมด', id: 'members' },
  { icon: icons.award, label: 'เหรียญ', href: '#badges', color: 'from-amber-500 to-orange-500', tooltip: 'ตราสัญลักษณ์และความสำเร็จ', id: 'badges' },
  { icon: icons.target, label: 'ภารกิจ', href: '#quests', color: 'from-red-500 to-pink-500', tooltip: 'ภารกิจและความท้าทาย', id: 'quests' },
  { icon: icons.video, label: 'ไลฟ์สด', href: '#streams', color: 'from-fuchsia-500 to-pink-500', tooltip: 'การถ่ายทอดสด', id: 'streams' },
  { icon: icons.calendar, label: 'กิจกรรม', href: '#events', color: 'from-green-500 to-emerald-500', tooltip: 'กิจกรรมต่างๆ', id: 'events' },
  { icon: icons.messageSquare, label: 'ฟอรั่ม', href: '#forums', color: 'from-indigo-500 to-blue-500', tooltip: 'กระดานสนทนา', id: 'forums' },
  { icon: icons.shoppingBag, label: 'ตลาด', href: '#marketplace', color: 'from-orange-500 to-red-500', tooltip: 'ตลาดซื้อขาย', id: 'marketplace' }
];

const accountNavItems = [
  { icon: icons.userCircle, label: 'ข้อมูลบัญชี', href: '#account-info', color: 'from-blue-500 to-cyan-500', tooltip: 'ข้อมูลบัญชี', id: 'account-info' },
  { icon: icons.lock, label: 'เปลี่ยนรหัสผ่าน', href: '#change-password', color: 'from-red-500 to-orange-500', tooltip: 'เปลี่ยนรหัสผ่าน', id: 'password' },
  { icon: icons.settings, label: 'ตั้งค่าทั่วไป', href: '#settings', color: 'from-purple-500 to-pink-500', tooltip: 'ตั้งค่าทั่วไป', id: 'settings' },
  { icon: icons.usersRound, label: 'กลุ่มของฉัน', href: '#my-groups', color: 'from-green-500 to-teal-500', tooltip: 'จัดการกลุ่มของคุณ', id: 'my-groups' },
  { icon: icons.send, label: 'คำเชิญ', href: '#invitations', color: 'from-indigo-500 to-purple-500', tooltip: 'คำเชิญ', id: 'invitations' },
  { icon: icons.store, label: 'ร้านของฉัน', href: '#my-store', color: 'from-amber-500 to-orange-500', tooltip: 'ร้านค้าของคุณ', id: 'store' },
  { icon: icons.dollarSign, label: 'รายงานการขาย', href: '#sales', color: 'from-emerald-500 to-green-500', tooltip: 'รายงานการขาย', id: 'sales' },
  { icon: icons.package, label: 'จัดการสินค้า', href: '#manage-items', color: 'from-cyan-500 to-blue-500', tooltip: 'จัดการสินค้า', id: 'items' },
  { icon: icons.download, label: 'ดาวน์โหลด', href: '#downloads', color: 'from-violet-500 to-purple-500', tooltip: 'ดาวน์โหลด', id: 'downloads' }
];

const featureCards = [
  { icon: icons.radio, title: 'ฟีดข่าว', desc: 'อัพเดทโพสต์ล่าสุด 🚀', color: 'from-cyan-50 to-blue-100', border: 'border-cyan-200 hover:border-cyan-400', iconColor: 'text-cyan-600' },
  { icon: icons.award, title: 'เหรียญ', desc: 'ปลดล็อคความสำเร็จ 🏆', color: 'from-purple-50 to-pink-100', border: 'border-purple-200 hover:border-purple-400', iconColor: 'text-purple-600' },
  { icon: icons.target, title: 'ภารกิจ', desc: 'ทำภารกิจให้สำเร็จ 🎯', color: 'from-green-50 to-emerald-100', border: 'border-green-200 hover:border-green-400', iconColor: 'text-green-600' }
];

// Methods
const setActiveItem = (id) => {
  activeItem.value = id;
};

const setHoveredItem = (id) => {
  hoveredItem.value = id;
};

const setShowTooltip = (id) => {
  showTooltip.value = id;
};

const handleLogout = () => {
  alert('กำลังออกจากระบบ...');
};

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
};

const handleSidebarMouseEnter = () => {
  if (isCollapsed.value) {
    isHovering.value = true;
  }
};

const handleSidebarMouseLeave = () => {
  if (isCollapsed.value) {
    isHovering.value = false;
  }
};

const isExpanded = () => !isCollapsed.value || isHovering.value;

// XP Animation on mount
onMounted(() => {
  let start = 0;
  const end = 850;
  const duration = 2000;
  const increment = end / (duration / 16);
  
  const timer = setInterval(() => {
    start += increment;
    if (start >= end) {
      currentXP.value = end;
      clearInterval(timer);
    } else {
      currentXP.value = Math.floor(start);
    }
  }, 16);
});

// NavItem Component
const NavItem = defineComponent({
  name: 'NavItem',
  props: {
    icon: { type: String, required: true },
    label: { type: String, required: true },
    href: { type: String, required: true },
    color: { type: String, required: true },
    tooltip: { type: String, required: true },
    id: { type: String, required: true },
    highlight: { type: Boolean, default: false },
    activeItem: { type: String, required: true },
    hoveredItem: { type: String, default: null },
    showTooltip: { type: String, default: null },
    isCollapsed: { type: Boolean, default: false },
    isExpanded: { type: Boolean, default: true }
  },
  emits: ['set-active', 'set-hovered', 'set-tooltip'],
  setup(props, { emit }) {
    const isActive = () => props.activeItem === props.id;
    const isHovered = () => props.hoveredItem === props.id;
    const isMobile = () => typeof window !== 'undefined' && window.innerWidth < 1024;
    
    const handleClick = (e) => {
      e.preventDefault();
      emit('set-active', props.id);
    };
    
    const handleMouseEnter = () => {
      emit('set-hovered', props.id);
      emit('set-tooltip', props.id);
    };
    
    const handleMouseLeave = () => {
      emit('set-hovered', null);
      emit('set-tooltip', null);
    };

    return () => h('div', { class: 'relative group' }, [
      h('a', {
        href: props.href,
        onClick: handleClick,
        onMouseenter: handleMouseEnter,
        onMouseleave: handleMouseLeave,
        class: [
          'relative flex items-center rounded-xl transition-all duration-300 overflow-hidden',
          props.isCollapsed && !props.isExpanded
            ? 'justify-center px-3 py-3'
            : 'gap-3 px-4 py-3',
          isActive() || props.highlight
            ? `bg-gradient-to-r ${props.color} text-white shadow-lg transform scale-105`
            : 'text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-100 hover:scale-105'
        ]
      }, [
        h('div', {
          class: ['relative transition-transform duration-300 flex-shrink-0', isHovered() ? 'rotate-12 scale-110' : '']
        }, [
          h(Icon, {
            icon: props.icon,
            class: ['w-5 h-5 transition-all duration-300', isActive() || props.highlight ? 'text-white' : 'text-gray-500']
          })
        ]),
        ...(props.isExpanded || !props.isCollapsed ? [
          h('span', {
            class: ['relative font-medium transition-all duration-300 whitespace-nowrap', isHovered() ? 'translate-x-1' : '']
          }, props.label)
        ] : []),
        ...(isActive() && (props.isExpanded || !props.isCollapsed) ? [
          h('div', { class: 'absolute right-2 w-2 h-2 bg-white rounded-full animate-ping' }),
          h(Icon, { icon: icons.sparkles, class: 'absolute right-4 w-4 h-4 text-white animate-pulse' })
        ] : [])
      ]),
      h(Tooltip, {
        text: props.tooltip,
        show: props.showTooltip === props.id && (props.isCollapsed && !props.isExpanded),
        isMobile: isMobile()
      })
    ]);
  }
});

// Tooltip Component
const Tooltip = defineComponent({
  name: 'Tooltip',
  props: {
    text: { type: String, required: true },
    show: { type: Boolean, default: false },
    isMobile: { type: Boolean, default: false }
  },
  setup(props) {
    return () => h('div', {
      class: [
        'absolute px-3 py-2 bg-gradient-to-r from-gray-900 to-gray-800 text-white text-xs rounded-lg shadow-2xl z-[9999] whitespace-nowrap pointer-events-none transition-all duration-300',
        'bottom-full mb-2 left-1/2 -translate-x-1/2',
        props.show
          ? props.isMobile
            ? 'opacity-100 scale-100 animate-fadeInUp'
            : 'opacity-100 scale-100 animate-fadeInDown'
          : 'opacity-0 scale-95 invisible'
      ]
    }, [
      props.text,
      h('div', {
        class: [
          'absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 transition-all duration-300',
          props.show ? 'opacity-100' : 'opacity-0'
        ]
      })
    ]);
  }
});

</script>

<template>
  <!-- Mobile Menu Button -->
  <button
    @click="isMobileMenuOpen = !isMobileMenuOpen"
    class="lg:hidden fixed top-4 left-4 z-50 p-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-xl shadow-2xl transition-all duration-300 hover:scale-110"
  >
    <Icon :icon="isMobileMenuOpen ? icons.x : icons.menu" class="w-6 h-6" />
  </button>

  <!-- Navigation Sidebar -->
  <aside
    @mouseenter="handleSidebarMouseEnter"
    @mouseleave="handleSidebarMouseLeave"
    :class="[
      'fixed lg:relative top-0 left-0 h-full bg-white/80 backdrop-blur-xl shadow-2xl overflow-y-auto overflow-x-hidden transition-all duration-500 z-40 border-r border-white/20',
      isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      isCollapsed && !isHovering ? 'w-16' : 'w-80'
    ]"
  >
    <!-- Toggle Button -->
    <button
      @click="toggleSidebar"
      :class="[
        'absolute top-4 right-4 z-50 p-2 rounded-lg transition-all duration-300',
        isCollapsed && !isHovering
          ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg hover:scale-110'
          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
      ]"
    >
      <Icon :icon="isCollapsed && !isHovering ? icons.chevronRight : icons.chevronLeft" class="w-4 h-4" />
    </button>

    <div :class="['p-6', isCollapsed && !isHovering ? 'px-2' : '']">
      <!-- User Profile Card -->
      <div v-if="user" :class="['mb-6 relative', isCollapsed && !isHovering ? 'hidden' : '']">
          <div class="relative flex flex-col items-center bg-[#1e293b] rounded-2xl p-4 text-white overflow-hidden shadow-xl border border-slate-700/50">
             
             <!-- Avatar -->
             <div class="relative mb-3">
                 <div class="w-24 h-24 rounded-full p-1 bg-gradient-to-tr from-cyan-400 to-blue-600">
                    <img :src="user.profile_photo_url || user.avatar || 'https://ui-avatars.com/api/?name='+(user.name)" 
                         class="w-full h-full rounded-full object-cover border-4 border-[#1e293b]" alt="Profile" />
                 </div>
                 <div class="absolute bottom-0 right-0 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full border-2 border-[#1e293b]">
                    {{ user.level || '1' }}
                 </div>
             </div>

             <!-- Name & Email -->
             <div class="text-center mb-4 w-full">
                 <h3 class="text-xl font-bold text-white truncate px-2">
                    {{ user.name }}
                 </h3>
                 <p class="text-slate-400 text-sm truncate px-2">{{ user.email }}</p>
             </div>

             <!-- Action Buttons -->
             <div class="flex gap-3 mb-6 justify-center w-full">
                 <button class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-500 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-colors">
                    <Icon :icon="icons.trophy" class="w-5 h-5" />
                 </button>
                 <button class="w-10 h-10 rounded-lg bg-indigo-500/20 text-indigo-500 flex items-center justify-center hover:bg-indigo-500 hover:text-white transition-colors">
                    <Icon :icon="icons.shield" class="w-5 h-5" />
                 </button>
                 <button class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-colors">
                    <Icon :icon="icons.check" class="w-5 h-5" />
                 </button>
                 <button class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-500 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-colors">
                    <Icon :icon="icons.star" class="w-5 h-5" />
                 </button>
             </div>

             <!-- Stats -->
             <div class="flex justify-between w-full px-2 border-t border-slate-700/50 pt-4">
                 <div class="text-center">
                     <div class="text-lg font-bold text-white">{{ user.stats?.posts || '930' }}</div>
                     <div class="text-[10px] uppercase tracking-wider text-slate-400">POSTS</div>
                 </div>
                 <div class="text-center">
                     <div class="text-lg font-bold text-white">{{ user.stats?.friends || '82' }}</div>
                     <div class="text-[10px] uppercase tracking-wider text-slate-400">FRIENDS</div>
                 </div>
                 <div class="text-center">
                     <div class="text-lg font-bold text-white">{{ user.stats?.visits || '5.7K' }}</div>
                     <div class="text-[10px] uppercase tracking-wider text-slate-400">VISITS</div>
                 </div>
             </div>
          </div>
      </div>

      <!-- Menu Tabs -->
      <div :class="['flex gap-2 mb-6 p-1 bg-gray-100 rounded-xl', isCollapsed && !isHovering ? 'hidden' : '']">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeMenu = tab.key"
          :class="[
            'flex-1 py-3 text-sm font-medium transition-all duration-300 rounded-lg',
            activeMenu === tab.key
              ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg scale-105'
              : 'text-gray-500 hover:text-gray-700 hover:bg-white'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Navigation Sections -->
      <nav class="space-y-2">
        <div v-if="activeMenu === 'global'">
          <h2 :class="['text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center gap-2', isCollapsed && !isHovering ? 'hidden' : 'px-4 py-2']">
            <div class="w-1 h-4 bg-gradient-to-b from-cyan-500 to-blue-500 rounded-full" />
            ลิงก์หลัก
          </h2>
          <NavItem
            v-for="item in globalNavItems"
            :key="item.id"
            v-bind="item"
            :active-item="activeItem"
            :hovered-item="hoveredItem"
            :show-tooltip="showTooltip"
            :is-collapsed="isCollapsed"
            :is-expanded="isExpanded()"
            @set-active="setActiveItem"
            @set-hovered="setHoveredItem"
            @set-tooltip="setShowTooltip"
          />
        </div>

        <div v-if="activeMenu === 'primary'">
          <h2 :class="['text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center gap-2', isCollapsed && !isHovering ? 'hidden' : 'px-4 py-2']">
            <div class="w-1 h-4 bg-gradient-to-b from-cyan-500 to-blue-500 rounded-full" />
            เนื้อหาชุมชน
          </h2>
          <NavItem
            v-for="item in primaryNavItems"
            :key="item.id"
            v-bind="item"
            :highlight="item.id === 'newsfeed' && activeItem === 'newsfeed'"
            :active-item="activeItem"
            :hovered-item="hoveredItem"
            :show-tooltip="showTooltip"
            :is-collapsed="isCollapsed"
            :is-expanded="isExpanded()"
            @set-active="setActiveItem"
            @set-hovered="setHoveredItem"
            @set-tooltip="setShowTooltip"
          />
        </div>

        <div v-if="activeMenu === 'account'">
          <h2 :class="['text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center gap-2', isCollapsed && !isHovering ? 'hidden' : 'px-4 py-2']">
            <div class="w-1 h-4 bg-gradient-to-b from-cyan-500 to-blue-500 rounded-full" />
            จัดการบัญชี
          </h2>
          <NavItem
            v-for="item in accountNavItems"
            :key="item.id"
            v-bind="item"
            :active-item="activeItem"
            :hovered-item="hoveredItem"
            :show-tooltip="showTooltip"
            :is-collapsed="isCollapsed"
            :is-expanded="isExpanded()"
            @set-active="setActiveItem"
            @set-hovered="setHoveredItem"
            @set-tooltip="setShowTooltip"
          />
        </div>
      </nav>

      <!-- Logout Button -->
      <div :class="['mt-6 pt-6 border-t-2 border-gray-200', isCollapsed && !isHovering ? 'hidden' : '']">
        <button
          @click="handleLogout"
          class="group flex items-center gap-3 w-full px-4 py-3 text-red-600 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 rounded-xl transition-all duration-300 hover:scale-105 relative overflow-hidden"
        >
          <Icon :icon="icons.logOut" class="w-5 h-5 transition-transform duration-300 group-hover:rotate-12" />
          <span class="font-medium relative">ออกจากระบบ</span>
        </button>
      </div>
    </div>
  </aside>

  <!-- Mobile Overlay -->
  <div
    v-if="isMobileMenuOpen"
    @click="isMobileMenuOpen = false"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden"
  />
</template>

<style scoped>
@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateX(-10px) translateY(-50%);
  }
  to {
    opacity: 1;
    transform: translateX(0) translateY(-50%);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.animate-fadeInLeft {
  animation: fadeInLeft 0.3s ease-out;
}

.animate-fadeInUp {
  animation: fadeInUp 0.3s ease-out;
}

.animate-fadeInDown {
  animation: fadeInDown 0.3s ease-out;
}

/* Custom scrollbar for collapsed sidebar */
aside {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

aside::-webkit-scrollbar {
  width: 4px;
}

aside::-webkit-scrollbar-track {
  background: transparent;
}

aside::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 2px;
}

aside::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.7);
}

/* Smooth text fade in/out for collapsed state */
.transition-text {
  transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
}

/* Ensure proper spacing in collapsed state */
.collapsed-spacing {
  min-height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Prevent text selection during transitions */
.no-select {
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Smooth width transition for sidebar */
.sidebar-transition {
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced hover effects for collapsed state */
.nav-item-collapsed {
  position: relative;
}

.nav-item-collapsed::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, transparent, rgba(6, 182, 212, 0.1));
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.nav-item-collapsed:hover::after {
  opacity: 1;
}
</style>
