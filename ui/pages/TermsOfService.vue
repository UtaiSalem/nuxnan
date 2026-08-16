<script setup lang="ts">
import { Icon } from '@iconify/vue'

useHead({
  title: 'ข้อกำหนดการใช้งาน | nuxnan',
  meta: [{ name: 'description', content: 'ข้อกำหนดการใช้งานระบบจัดการการเรียนรู้ nuxnan' }],
})

const title = 'ข้อกำหนดการใช้งาน'
const updatedAt = '16 สิงหาคม 2569'

const sections = [
  { id: 'acceptance', title: 'การยอมรับข้อกำหนด' },
  { id: 'user-account', title: 'บัญชีผู้ใช้' },
  { id: 'acceptable-use', title: 'การใช้งานที่ยอมรับได้และข้อห้าม' },
  { id: 'content-copyright', title: 'เนื้อหาของผู้ใช้และลิขสิทธิ์' },
  { id: 'courses-fees', title: 'บทเรียน คอร์ส และค่าธรรมเนียม' },
  { id: 'points-wallet', title: 'แต้ม กระเป๋าเงิน และการสนับสนุน' },
  { id: 'termination', title: 'การระงับและยกเลิกบัญชี' },
  { id: 'limitations', title: 'ข้อจำกัดความรับผิดและการรับประกัน' },
  { id: 'changes-law', title: 'การแก้ไขข้อกำหนด กฎหมายที่ใช้บังคับ และช่องทางติดต่อ' },
]
</script>

<template>
  <div class="bg-gray-50 min-h-screen font-sans text-gray-900 antialiased">
    <!-- Header -->
    <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-gray-200">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center justify-between">
        <NuxtLink to="/" class="flex items-center gap-2 flex-shrink-0 min-h-[44px]">
          <img src="/images/plearnd-logo.png" alt="nuxnan" class="w-10 h-10 sm:w-12 sm:h-12" />
          <span class="font-bold text-xl sm:text-2xl text-gray-900">nuxnan</span>
        </NuxtLink>
        <NuxtLink to="/" class="flex items-center gap-1 text-gray-600 hover:text-indigo-600 min-h-[44px] px-2 sm:px-3 flex-shrink-0 transition-colors">
          <Icon icon="heroicons:arrow-left" class="w-5 h-5" />
          <span class="hidden sm:inline">กลับหน้าแรก</span>
          <span class="sm:hidden">กลับ</span>
        </NuxtLink>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8 sm:py-12 lg:flex lg:gap-8 lg:items-start">
      <!-- TOC -->
      <aside class="hidden lg:block lg:w-64 flex-shrink-0 sticky top-24">
        <nav class="space-y-1">
          <h3 class="font-semibold text-gray-900 mb-3 px-3">สารบัญ</h3>
          <a v-for="section in sections" :key="section.id" :href="`#${section.id}`" class="block px-3 py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
            {{ section.title }}
          </a>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 max-w-3xl mx-auto w-full bg-white border border-gray-200 rounded-xl p-4 sm:p-6 lg:p-8">
        <!-- Hero -->
        <div class="mb-8 border-b border-gray-200 pb-8">
          <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-4">{{ title }}</h1>
          <p class="text-gray-600 text-sm sm:text-base leading-relaxed mb-4">
            nuxnan คือระบบจัดการการเรียนรู้ (LMS) สำหรับโรงเรียน/สถาบัน ผู้ใช้สร้างและเข้าร่วม Academy และ Course ทำแบบทดสอบ ส่งงาน มีฟีดโพสต์ ระบบแต้ม (points) กระเป๋าเงิน (wallet) ตลาดแลกเปลี่ยน และแชทเรียลไทม์
          </p>
          <p class="text-sm text-gray-500">ปรับปรุงล่าสุด: {{ updatedAt }}</p>
        </div>

        <!-- Sections -->
        <div class="space-y-8 sm:space-y-12">
          <section id="acceptance" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">1. การยอมรับข้อกำหนด</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>การสมัครสมาชิกและการเข้าใช้งานระบบ nuxnan ถือว่าผู้ใช้งานได้อ่าน ทำความเข้าใจ และตกลงยอมรับที่จะปฏิบัติตามข้อกำหนดและเงื่อนไขการใช้งานเหล่านี้ทุกประการ</p>
            </div>
          </section>

          <section id="user-account" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">2. บัญชีผู้ใช้</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <ul class="list-disc pl-5 space-y-2">
                <li>ผู้ใช้งานจะต้องให้ข้อมูลที่เป็นความจริงและถูกต้องในการสมัครบัญชี</li>
                <li>ผู้ใช้งานต้องรับผิดชอบในการเก็บรักษารหัสผ่านของตนเองให้ปลอดภัย</li>
                <li>ไม่อนุญาตให้ใช้บัญชีของผู้อื่นโดยเด็ดขาด</li>
                <li>กำหนดให้ผู้ใช้งาน 1 คน สามารถมีบัญชีผู้ใช้ได้เพียง 1 บัญชีเท่านั้น</li>
              </ul>
            </div>
          </section>

          <section id="acceptable-use" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">3. การใช้งานที่ยอมรับได้และข้อห้าม</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ผู้ใช้งานตกลงที่จะไม่กระทำการดังต่อไปนี้:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>โพสต์หรือเผยแพร่เนื้อหาที่ผิดกฎหมาย ลามกอนาจาร หรือสร้างความเกลียดชัง</li>
                <li>คุกคาม กลั่นแกล้ง หรือล่วงละเมิดสิทธิของผู้อื่น</li>
                <li>ทุจริตในการทำแบบทดสอบ หรือการส่งงานต่างๆ</li>
                <li>พยายามเจาะระบบ แทรกแซงการทำงานของระบบ หรือดึงข้อมูลออกจากระบบโดยอัตโนมัติ (Scraping)</li>
              </ul>
            </div>
          </section>

          <section id="content-copyright" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">4. เนื้อหาของผู้ใช้และลิขสิทธิ์</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ผู้ใช้ยังคงเป็นเจ้าของสิทธิในเนื้อหาใดๆ ที่อัปโหลดเข้าสู่ระบบ อย่างไรก็ตาม ผู้ใช้ยินยอมให้สิทธิ nuxnan ในการใช้ จัดเก็บ และแสดงผลเนื้อหาดังกล่าวในระบบอย่างถูกต้องตามวัตถุประสงค์ของการให้บริการ</p>
              <p class="font-medium text-gray-900">ผู้ใช้งานจะต้องไม่อัปโหลดหรือเผยแพร่เนื้อหาที่ละเมิดลิขสิทธิ์หรือสิทธิในทรัพย์สินทางปัญญาของบุคคลอื่น</p>
            </div>
          </section>

          <section id="courses-fees" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">5. บทเรียน คอร์ส และค่าธรรมเนียม</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ครูผู้สอนมีสิทธิในการกำหนดค่าเล่าเรียนและส่วนลดของบทเรียนหรือคอร์สได้ด้วยตนเอง การชำระเงินค่าธรรมเนียมต่างๆ จะดำเนินการผ่านกระเป๋าเงิน (Wallet) และแต้ม (Points) ในระบบ nuxnan</p>
            </div>
          </section>

          <section id="points-wallet" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">6. แต้ม กระเป๋าเงิน และการสนับสนุน</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>แต้ม (Points) และยอดคงเหลือในกระเป๋าเงิน (Wallet) เป็นเพียงหน่วยการแลกเปลี่ยนภายในระบบ nuxnan เท่านั้น โดยมีไว้เพื่อใช้แลกเปลี่ยนสิทธิประโยขน์ตามที่ระบบกำหนด</p>
              <p class="font-medium text-gray-900">แต้มและยอดในกระเป๋าเงินไม่ใช่เงินตราตามกฎหมาย และทาง nuxnan ไม่รับประกันหรือรับผิดชอบในการแลกเปลี่ยนคืนเป็นเงินสดไม่ว่าในกรณีใดๆ</p>
            </div>
          </section>

          <section id="termination" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">7. การระงับและยกเลิกบัญชี</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>nuxnan ขอสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีผู้ใช้งานทันทีโดยไม่ต้องแจ้งให้ทราบล่วงหน้า หากพบว่าผู้ใช้งานมีการละเมิดข้อกำหนดและเงื่อนไขการใช้งานเหล่านี้</p>
            </div>
          </section>

          <section id="limitations" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">8. ข้อจำกัดความรับผิดและการรับประกัน</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ระบบ nuxnan ให้บริการ "ตามสภาพที่เป็นอยู่" (As is) โดยเราไม่สามารถรับประกันได้ว่าระบบจะทำงานได้อย่างสมบูรณ์แบบ ปราศจากข้อขัดข้อง หรือปราศจากข้อผิดพลาดตลอดเวลา</p>
            </div>
          </section>

          <section id="changes-law" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">9. การแก้ไขข้อกำหนด กฎหมายที่ใช้บังคับ และช่องทางติดต่อ</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ข้อกำหนดการใช้งานนี้อยู่ภายใต้การบังคับใช้กฎหมายของประเทศไทย nuxnan อาจมีการแก้ไขเปลี่ยนแปลงข้อกำหนดนี้เป็นระยะตามความเหมาะสม หากคุณมีข้อสงสัยหรือต้องการติดต่อเรา สามารถส่งอีเมลมาได้ที่:</p>
              <p><a href="mailto:contact@plearnd.com" class="text-indigo-600 hover:text-indigo-700 font-medium min-h-[44px] inline-flex items-center">contact@plearnd.com</a></p>
            </div>
          </section>
        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8 mt-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-gray-500">© {{ new Date().getFullYear() }} nuxnan. All rights reserved.</p>
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
          <NuxtLink to="/PrivacyPolicy" class="text-indigo-600 hover:text-indigo-700 min-h-[44px] flex items-center">นโยบายความเป็นส่วนตัว</NuxtLink>
          <a href="mailto:contact@plearnd.com" class="text-gray-600 hover:text-gray-900 min-h-[44px] flex items-center">ติดต่อเรา: contact@plearnd.com</a>
        </div>
      </div>
    </footer>
  </div>
</template>
