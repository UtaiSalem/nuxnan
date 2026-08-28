<script setup lang="ts">
import { Icon } from '@iconify/vue'

useHead({
  title: 'ลืมรหัสผ่าน | nuxnan',
  meta: [{ name: 'description', content: 'วิธีขอรีเซ็ตรหัสผ่านสำหรับผู้ใช้ nuxnan' }],
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 px-0 sm:px-4 py-8 sm:py-12 flex flex-col justify-center items-center">
    <div class="w-full max-w-lg mx-auto bg-white border border-gray-200 rounded-2xl p-4 sm:p-6 lg:p-8">
      
      <!-- หัวการ์ด -->
      <div class="flex flex-col items-center mb-6 sm:mb-8 text-center">
        <div class="flex items-center justify-center gap-2 mb-4">
          <img src="/images/plearnd-logo.png" alt="nuxnan" class="w-14 h-14 sm:w-16 sm:h-16" />
          <span class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">nuxnan</span>
        </div>
        <div class="flex items-center justify-center w-12 h-12 bg-indigo-50 rounded-full mb-4">
          <Icon icon="heroicons:key" class="w-6 h-6 text-indigo-600" />
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">ลืมรหัสผ่าน?</h1>
        <p class="text-sm sm:text-base text-gray-600">
          ขณะนี้ระบบยังไม่รองรับการรีเซ็ตรหัสผ่านด้วยตัวเอง ต้องให้ผู้ดูแลระบบเป็นผู้ดำเนินการให้
        </p>
      </div>

      <!-- ขั้นตอน -->
      <div class="space-y-4 mb-6 sm:mb-8">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-bold rounded-full text-sm sm:text-base">1</div>
          <div class="min-w-0 flex-1 break-words text-sm sm:text-base text-gray-700 pt-0.5 sm:pt-1">
            ติดต่อครูประจำชั้น ผู้ดูแล Academy หรือผู้ดูแลระบบของโรงเรียนที่คุณสังกัด
          </div>
        </div>
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-bold rounded-full text-sm sm:text-base">2</div>
          <div class="min-w-0 flex-1 break-words text-sm sm:text-base text-gray-700 pt-0.5 sm:pt-1">
            แจ้ง <strong>ชื่อ-นามสกุล และอีเมลหรือรหัสประจำตัว</strong> ที่ใช้สมัคร เพื่อให้ผู้ดูแลค้นหาบัญชีของคุณได้
          </div>
        </div>
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-bold rounded-full text-sm sm:text-base">3</div>
          <div class="min-w-0 flex-1 break-words text-sm sm:text-base text-gray-700 pt-0.5 sm:pt-1">
            ผู้ดูแลจะตั้งรหัสผ่านใหม่ให้ จากนั้นให้เข้าสู่ระบบแล้ว <strong>เปลี่ยนรหัสผ่านทันที</strong> ที่หน้าตั้งค่าบัญชี
          </div>
        </div>
      </div>

      <!-- กล่องหมายเหตุ -->
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 sm:mb-8 flex gap-3 items-start">
        <Icon icon="heroicons:information-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 flex-shrink-0 mt-0.5" />
        <div class="min-w-0 flex-1 break-words text-sm sm:text-base text-amber-800">
          การรีเซ็ตรหัสผ่านโดยผู้ดูแลระบบมีค่าดำเนินการ 4,800 แต้ม โดยหักจากแต้มของเจ้าของบัญชี และสามารถใช้ยอดในกระเป๋าเงินร่วมด้วยได้ (อัตรา 1 บาท = 1,080 แต้ม)
        </div>
      </div>

      <!-- ช่องทางติดต่อ -->
      <div class="flex flex-col items-center mb-6 sm:mb-8 text-sm sm:text-base text-gray-700">
        <p class="mb-2 text-center">หากมีข้อสงสัยเพิ่มเติม สามารถติดต่อเราได้ที่</p>
        <a href="mailto:contact@plearnd.com" class="inline-flex items-center justify-center gap-2 text-indigo-600 hover:text-indigo-700 font-medium transition-colors px-2 py-3 min-h-[44px]">
          <Icon icon="heroicons:envelope" class="w-5 h-5 flex-shrink-0" />
          <span>contact@plearnd.com</span>
        </a>
      </div>

      <!-- ปุ่ม/ลิงก์ท้ายการ์ด -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 border-t border-gray-100 pt-6 sm:pt-8">
        <NuxtLink to="/auth" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 min-h-[44px] border border-transparent text-sm sm:text-base font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
          กลับไปหน้าเข้าสู่ระบบ
        </NuxtLink>
        <NuxtLink to="/nuxnan-admin/login" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 min-h-[44px] text-sm sm:text-base font-medium text-gray-600 hover:text-gray-900 transition-colors">
          เข้าสู่ระบบสำหรับผู้ดูแล
        </NuxtLink>
      </div>

    </div>

    <!-- ลิงก์ใต้การ์ด -->
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4 text-xs sm:text-sm text-gray-500">
      <NuxtLink to="/PrivacyPolicy" class="hover:text-gray-900 transition-colors py-3 px-3 min-h-[44px] sm:min-h-0 sm:py-0 inline-flex items-center justify-center">นโยบายความเป็นส่วนตัว</NuxtLink>
      <span class="hidden sm:inline text-gray-300">&bull;</span>
      <NuxtLink to="/TermsOfService" class="hover:text-gray-900 transition-colors py-3 px-3 min-h-[44px] sm:min-h-0 sm:py-0 inline-flex items-center justify-center">เงื่อนไขการให้บริการ</NuxtLink>
    </div>
  </div>
</template>
