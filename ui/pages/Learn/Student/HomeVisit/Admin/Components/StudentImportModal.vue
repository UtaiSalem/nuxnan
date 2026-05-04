<script setup>
import { ref } from 'vue'

const emit = defineEmits(['import', 'cancel'])

const file = ref(null)
const fileName = ref('')

const handleFileChange = (event) => {
  const selectedFile = event.target.files[0]
  if (selectedFile) {
    file.value = selectedFile
    fileName.value = selectedFile.name
  }
}

const handleImport = () => {
  if (file.value) {
    emit('import', file.value)
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="emit('cancel')"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
              <i class="fas fa-file-import text-blue-600"></i>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                นำเข้าข้อมูลนักเรียน
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  เลือกไฟล์ Excel (.xlsx, .xls) หรือ CSV เพื่อนำเข้าข้อมูลนักเรียนเข้าสู่ระบบ
                </p>
              </div>
            </div>
          </div>

          <div class="mt-6">
            <div class="flex items-center justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
              <div class="space-y-1 text-center">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                <div class="flex text-sm text-gray-600">
                  <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                    <span>อัปโหลดไฟล์</span>
                    <input id="file-upload" name="file-upload" type="file" class="sr-only" @change="handleFileChange" accept=".csv, .xlsx, .xls" />
                  </label>
                  <p class="pl-1">หรือลากและวาง</p>
                </div>
                <p class="text-xs text-gray-500">
                  CSV, XLSX สูงสุด 10MB
                </p>
              </div>
            </div>
            <div v-if="fileName" class="mt-2 text-sm text-gray-600">
              ไฟล์ที่เลือก: <span class="font-medium text-gray-900">{{ fileName }}</span>
            </div>
          </div>

          <div class="mt-4 bg-blue-50 p-4 rounded-md">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">คำแนะนำ</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li>ไฟล์ต้องมีหัวตารางตามรูปแบบที่กำหนด</li>
                    <li>รองรับไฟล์นามสกุล .xlsx, .xls และ .csv</li>
                    <li>หากรหัสนักเรียนซ้ำ ระบบจะทำการปรับปรุงข้อมูลเดิม</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="handleImport"
            :disabled="!file"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            นำเข้าข้อมูล
          </button>
          <button
            @click="emit('cancel')"
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            ยกเลิก
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
