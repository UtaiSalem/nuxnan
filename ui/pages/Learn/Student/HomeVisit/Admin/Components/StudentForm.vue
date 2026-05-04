<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  student: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['save', 'cancel'])

const formData = ref({
  student_id: '',
  first_name: '',
  last_name: '',
  grade: '',
  room: '',
  status: 'active'
})

onMounted(() => {
  if (props.student) {
    formData.value = { ...props.student }
  }
})

const handleSubmit = () => {
  emit('save', { ...formData.value })
}
</script>

<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="emit('cancel')"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="handleSubmit">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
              {{ student ? 'แก้ไขข้อมูลนักเรียน' : 'เพิ่มนักเรียนใหม่' }}
            </h3>
            
            <div class="space-y-4">
              <div>
                <label for="student_id" class="block text-sm font-medium text-gray-700">รหัสนักเรียน</label>
                <input
                  v-model="formData.student_id"
                  type="text"
                  id="student_id"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="first_name" class="block text-sm font-medium text-gray-700">ชื่อ</label>
                  <input
                    v-model="formData.first_name"
                    type="text"
                    id="first_name"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>
                <div>
                  <label for="last_name" class="block text-sm font-medium text-gray-700">นามสกุล</label>
                  <input
                    v-model="formData.last_name"
                    type="text"
                    id="last_name"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="grade" class="block text-sm font-medium text-gray-700">ชั้นปี</label>
                  <input
                    v-model="formData.grade"
                    type="text"
                    id="grade"
                    required
                    placeholder="เช่น ม.1"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>
                <div>
                  <label for="room" class="block text-sm font-medium text-gray-700">ห้อง</label>
                  <input
                    v-model="formData.room"
                    type="text"
                    id="room"
                    required
                    placeholder="เช่น 1"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>
              </div>

              <div>
                <label for="status" class="block text-sm font-medium text-gray-700">สถานะ</label>
                <select
                  v-model="formData.status"
                  id="status"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="active">กำลังเรียน</option>
                  <option value="inactive">พ้นสภาพ</option>
                </select>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
            >
              บันทึก
            </button>
            <button
              @click="emit('cancel')"
              type="button"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              ยกเลิก
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
