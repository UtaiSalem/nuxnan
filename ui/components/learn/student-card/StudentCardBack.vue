<script setup lang="ts">
/**
 * StudentCardBack - Reusable student card back face component
 * Used in: student card view and print pages
 */

interface Props {
  academyName?: string
  academyLogo?: string
  academyAddress?: string
  directorName?: string
  directorSignature?: string
  cardId?: string
}

withDefaults(defineProps<Props>(), {
  academyName: 'โรงเรียน',
  academyLogo: '/images/default-school-logo.png',
  academyAddress: '',
  directorName: '',
  directorSignature: '',
  cardId: 'student-card-back',
})
</script>

<template>
  <div
    :id="cardId"
    class="student-card-back w-full max-w-[640px] aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300 bg-white font-sarabun"
  >
    <!-- Header Bar -->
    <div class="bg-blue-700 h-[3%] w-full"></div>

    <!-- Content -->
    <div class="p-[4%] flex flex-col h-[97%]">
      <!-- Logo and School Name -->
      <div class="flex items-center gap-[3%] mb-[2%]">
        <img :src="academyLogo" alt="Logo" class="w-[12%] aspect-square object-contain rounded-full" />
        <div>
          <h1 class="text-[3vw] md:text-xl font-bold text-gray-800">{{ academyName }}</h1>
          <p v-if="academyAddress" class="text-[2vw] md:text-sm text-gray-700">{{ academyAddress }}</p>
        </div>
      </div>

      <!-- Terms Box -->
      <div class="flex-1">
        <div class="bg-blue-600 text-white px-[2%] py-[1%] w-fit rounded-t-md text-[2vw] md:text-sm font-semibold">
          เงื่อนไขการใช้บัตร
        </div>
        <ul class="list-disc pl-[5%] text-[2vw] md:text-sm text-gray-800 bg-white py-[2%] rounded-b-md border border-t-0 border-blue-600 space-y-[1%]">
          <li>บัตรนี้ใช้เฉพาะผู้เป็นเจ้าของบัตรเท่านั้น</li>
          <li>ให้พกบัตรนี้ตลอดเวลาที่อยู่ในโรงเรียนและนอกโรงเรียน</li>
          <li>เป็นเอกสารของทางโรงเรียน เพื่อแสดงสถานภาพการเป็นนักเรียนของ{{ academyName }}</li>
          <li>ผู้ใดเก็บบัตรนี้ได้ กรุณานำส่งคืนโรงเรียน</li>
        </ul>
      </div>

      <!-- Signature Section -->
      <div class="flex justify-between items-end mt-[3%]">
        <div></div>
        <div class="text-right text-[2vw] md:text-sm text-gray-700">
          <div v-if="directorSignature" class="h-[40px] mb-1">
            <img :src="directorSignature" alt="ลายเซ็น" class="h-full ml-auto" />
          </div>
          <div v-else class="h-[40px] mb-1 flex items-end justify-end">
            <div class="w-[120px] border-b border-gray-400"></div>
          </div>
          <div v-if="directorName">({{ directorName }})</div>
          <div>ผู้อำนวยการโรงเรียน</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-sarabun {
  font-family: 'Sarabun', 'Noto Sans Thai', sans-serif;
}
</style>
