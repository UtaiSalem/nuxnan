<template>
  <div class="w-[640px] h-[400px] bg-white shadow-lg relative overflow-hidden font-thai">
    <!-- Background -->
    <div class="absolute inset-0">
      <img
        src="/your-background-image.jpg"
        alt="bg"
        class="w-full h-full object-cover opacity-20"
      />
    </div>

    <!-- Header Bar -->
    <div class="bg-blue-800 h-4 w-full absolute top-0 left-0"></div>

    <!-- Content -->
    <div class="relative z-10 p-6 flex flex-col gap-3">
      <!-- Logo and School Name -->
      <div class="flex items-center space-x-4">
        <img
          src="/logo.png"
          alt="logo"
          class="w-16 h-16 object-contain"
        />
        <div>
          <h1 class="text-xl font-bold text-gray-800">โรงเรียนจริยธรรมศึกษามูลนิธิ</h1>
          <p class="text-sm text-gray-700">
            148 ม.8 ต.สะกอม อ.จะนะ จ. สงขลา 90130 โทร. 081-5412281
          </p>
        </div>
      </div>

      <!-- Terms Box -->
      <div class="mt-2">
        <div class="bg-blue-600 text-white px-3 py-1 w-fit rounded-t-md text-sm">
          เงื่อนไขการใช้บัตร
        </div>
        <ul class="list-disc pl-6 text-sm text-gray-800 bg-white py-2 rounded-b-md border border-t-0 border-blue-600">
          <li>บัตรนี้ใช้เฉพาะผู้เป็นเจ้าของบัตรเท่านั้น</li>
          <li>ให้พกบัตรนี้ตลอดเวลาที่อยู่ในโรงเรียนและนอกโรงเรียน</li>
          <li>เป็นเอกสารของทางโรงเรียน เพื่อแสดงสถานภาพ การเป็นนักเรียนของโรงเรียนจริยธรรมศึกษามูลนิธิ</li>
          <li>ผู้ใดเก็บบัตรนี้ได้ กรุณานำส่งคืนโรงเรียน</li>
        </ul>
      </div>

      <!-- QR Code and Signature -->
      <div class="flex justify-between items-end mt-4">
        <img src="/qr-code.png" alt="QR" class="w-20 h-20" />
        <div class="text-right text-sm text-gray-700">
          <div class="h-10 mb-1">
            <img src="/signature.png" alt="signature" class="h-full" />
          </div>
          <div>(นางซารีนา ลาเก๊าะ)</div>
          <div>ผู้อำนวยการโรงเรียน</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');

.font-thai {
  font-family: 'Noto Sans Thai', sans-serif;
}
</style>

