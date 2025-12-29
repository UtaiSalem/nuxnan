<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import MemberCard from '~/components/course/MemberCard.vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

// State
const members = ref<any[]>([])
const groups = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')
const selectedGroup = ref<number | null>(null)
const selectedRole = ref<string>('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingMember = ref<any>(null)

// Fetch members
const fetchMembers = async () => {
  loading.value = true
  try {
    const response = await api.get(`/courses/${props.courseId}/members`, {
      params: {
        search: searchQuery.value || undefined,
        group_id: selectedGroup.value || undefined,
        role: selectedRole.value || undefined
      }
    })
    members.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching members:', error)
  } finally {
    loading.value = false
  }
}

// Fetch groups for filter
const fetchGroups = async () => {
  try {
    const response = await api.get(`/courses/${props.courseId}/groups`)
    groups.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching groups:', error)
  }
}

// Group members by role
const groupedMembers = computed(() => {
  const teachers = members.value.filter(m => ['teacher', 'admin', 'owner', 'co-teacher', 'assistant'].includes(m.role))
  const students = members.value.filter(m => !['teacher', 'admin', 'owner', 'co-teacher', 'assistant'].includes(m.role))
  
  return {
    teachers: teachers.sort((a, b) => {
      const priority = { owner: 0, admin: 1, teacher: 2, 'co-teacher': 3, assistant: 4 }
      return (priority[a.role] || 5) - (priority[b.role] || 5)
    }),
    students
  }
})

// Handle search
const handleSearch = useDebounceFn(() => {
  fetchMembers()
}, 300)

// Edit member
const openEditModal = (member: any) => {
  editingMember.value = { ...member }
  showEditModal.value = true
}

// Update member
const updateMember = async () => {
  if (!editingMember.value) return
  
  try {
    await api.put(`/courses/${props.courseId}/members/${editingMember.value.id}`, {
      role: editingMember.value.role,
      group_id: editingMember.value.group_id
    })
    showEditModal.value = false
    fetchMembers()
  } catch (error) {
    console.error('Error updating member:', error)
  }
}

// Remove member
const removeMember = async (memberId: number) => {
  if (!confirm('คุณต้องการลบสมาชิกออกจากคอร์สหรือไม่?')) return
  
  try {
    await api.delete(`/courses/${props.courseId}/members/${memberId}`)
    fetchMembers()
  } catch (error) {
    console.error('Error removing member:', error)
  }
}

// View member profile
const viewMember = (member: any) => {
  navigateTo(`/profile/${member.user?.username}`)
}

// Init
onMounted(() => {
  fetchMembers()
  fetchGroups()
})

// Watch filters
watch([selectedGroup, selectedRole], () => {
  fetchMembers()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          สมาชิกในคอร์ส
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          ทั้งหมด {{ members.length }} คน
        </p>
      </div>
      
      <button
        v-if="isCourseAdmin"
        @click="showAddModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
        <span>เพิ่มสมาชิก</span>
      </button>
    </div>
    
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- Search -->
      <div class="relative flex-1">
        <Icon 
          icon="fluent:search-24-regular" 
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
        />
        <input
          v-model="searchQuery"
          @input="handleSearch"
          type="text"
          placeholder="ค้นหาสมาชิก..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
      
      <!-- Group filter -->
      <select
        v-model="selectedGroup"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
      >
        <option :value="null">ทุกกลุ่ม</option>
        <option v-for="group in groups" :key="group.id" :value="group.id">
          {{ group.name }}
        </option>
      </select>
      
      <!-- Role filter -->
      <select
        v-model="selectedRole"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
      >
        <option value="">ทุกบทบาท</option>
        <option value="teacher">ผู้สอน</option>
        <option value="student">ผู้เรียน</option>
        <option value="assistant">ผู้ช่วยสอน</option>
      </select>
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Members List -->
    <div v-else-if="members.length > 0" class="space-y-6">
      <!-- Teachers Section -->
      <div v-if="groupedMembers.teachers.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-100 dark:border-purple-800">
          <h4 class="font-medium text-purple-900 dark:text-purple-300 flex items-center gap-2">
            <Icon icon="fluent:person-board-24-regular" class="w-5 h-5" />
            ผู้สอน ({{ groupedMembers.teachers.length }})
          </h4>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <MemberCard
            v-for="member in groupedMembers.teachers"
            :key="member.id"
            :member="member"
            :course-id="courseId"
            :is-course-admin="isCourseAdmin"
            @edit="openEditModal"
            @remove="removeMember"
            @click="viewMember"
          />
        </div>
      </div>
      
      <!-- Students Section -->
      <div v-if="groupedMembers.students.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
          <h4 class="font-medium text-gray-900 dark:text-gray-200 flex items-center gap-2">
            <Icon icon="fluent:people-24-regular" class="w-5 h-5" />
            ผู้เรียน ({{ groupedMembers.students.length }})
          </h4>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <MemberCard
            v-for="member in groupedMembers.students"
            :key="member.id"
            :member="member"
            :course-id="courseId"
            :is-course-admin="isCourseAdmin"
            @edit="openEditModal"
            @remove="removeMember"
            @click="viewMember"
          />
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
      <Icon icon="fluent:people-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
        {{ searchQuery ? 'ไม่พบสมาชิก' : 'ยังไม่มีสมาชิก' }}
      </h3>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'ลองค้นหาด้วยคำอื่น' : 'เพิ่มสมาชิกเข้าคอร์สเพื่อเริ่มต้น' }}
      </p>
    </div>
    
    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="showEditModal = false">
      <template #title>แก้ไขสมาชิก</template>
      
      <template #content>
        <div v-if="editingMember" class="space-y-4">
          <!-- Member info -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <img
              :src="editingMember.user?.avatar || '/images/default-avatar.png'"
              :alt="editingMember.user?.name"
              class="w-12 h-12 rounded-full"
            />
            <div>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ editingMember.user?.name }}
              </p>
              <p class="text-sm text-gray-500">@{{ editingMember.user?.username }}</p>
            </div>
          </div>
          
          <!-- Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              บทบาท
            </label>
            <select
              v-model="editingMember.role"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            >
              <option value="student">ผู้เรียน</option>
              <option value="assistant">ผู้ช่วยสอน</option>
              <option value="teacher">ผู้สอน</option>
            </select>
          </div>
          
          <!-- Group -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              กลุ่ม
            </label>
            <select
              v-model="editingMember.group_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            >
              <option :value="null">ไม่มีกลุ่ม</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }}
              </option>
            </select>
          </div>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showEditModal = false"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          ยกเลิก
        </button>
        <button
          @click="updateMember"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          บันทึก
        </button>
      </template>
    </DialogModal>
  </div>
</template>
