<script setup lang="ts">
import { Icon } from '@iconify/vue'

useHead({
  title: 'นโยบายความเป็นส่วนตัว | nuxnan',
  meta: [{ name: 'description', content: 'นโยบายความเป็นส่วนตัวของระบบจัดการการเรียนรู้ nuxnan' }],
})

const title = 'นโยบายความเป็นส่วนตัว'
const updatedAt = '16 สิงหาคม 2569'

const sections = [
  { id: 'data-collected', title: 'ข้อมูลที่เราเก็บ' },
  { id: 'data-usage', title: 'ข้อมูลที่เกิดจากการใช้งาน' },
  { id: 'purpose', title: 'วัตถุประสงค์ในการใช้ข้อมูล' },
  { id: 'disclosure', title: 'การเปิดเผยข้อมูล' },
  { id: 'cookies', title: 'คุกกี้และเทคโนโลยีที่คล้ายกัน' },
  { id: 'retention', title: 'ระยะเวลาเก็บรักษา' },
  { id: 'rights', title: 'สิทธิของเจ้าของข้อมูล' },
  { id: 'minors', title: 'ข้อมูลของผู้เยาว์' },
  { id: 'changes-contact', title: 'การเปลี่ยนแปลงนโยบายและช่องทางติดต่อ' },
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
          <section id="data-collected" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">1. ข้อมูลที่เราเก็บ</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เรามีการเก็บรวบรวมข้อมูลส่วนบุคคลของคุณเพื่อให้สามารถให้บริการระบบจัดการการเรียนรู้ได้อย่างสมบูรณ์ ข้อมูลที่เราเก็บรวบรวมประกอบด้วย:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>ชื่อ-นามสกุล, อีเมล และรูปโปรไฟล์</li>
                <li>ข้อมูลสถานศึกษา, ห้องเรียน และระดับชั้น</li>
                <li>ข้อมูลบัตรนักเรียน (เช่น เลขประจำตัวนักเรียน ข้อมูลผู้ปกครอง) สำหรับผู้ใช้ที่เป็นนักเรียนในโรงเรียนที่เปิดใช้ฟีเจอร์นี้</li>
              </ul>
            </div>
          </section>

          <section id="data-usage" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">2. ข้อมูลที่เกิดจากการใช้งาน</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ระหว่างการใช้งานระบบ nuxnan เราจะมีการเก็บรวบรวมข้อมูลที่เกิดจากการทำกิจกรรมต่างๆ ภายในแพลตฟอร์มของคุณ ได้แก่:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>คะแนนแบบทดสอบและงานที่ส่ง</li>
                <li>ความคืบหน้าบทเรียน</li>
                <li>โพสต์และคอมเมนต์ต่างๆ ในระบบ</li>
                <li>ข้อความแชท</li>
                <li>ประวัติแต้มและธุรกรรมในกระเป๋าเงิน (Wallet)</li>
                <li>บันทึก (Log) การเข้าใช้งาน เช่น หมายเลขไอพี (IP Address), เบราว์เซอร์ที่ใช้งาน และเวลาที่เข้าใช้งาน</li>
              </ul>
            </div>
          </section>

          <section id="purpose" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">3. วัตถุประสงค์ในการใช้ข้อมูล</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เราใช้ข้อมูลส่วนบุคคลและข้อมูลการใช้งานของคุณเพื่อวัตถุประสงค์ดังต่อไปนี้:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>ให้บริการระบบเรียนและแพลตฟอร์มการจัดการการเรียนรู้</li>
                <li>แสดงผลการเรียนและความคืบหน้าแก่ผู้เรียนและครูผู้สอน</li>
                <li>คำนวณแต้มและรางวัลภายในระบบ</li>
                <li>รักษาความปลอดภัยของบัญชีผู้ใช้งาน</li>
                <li>วิเคราะห์และปรับปรุงบริการให้ดียิ่งขึ้น</li>
              </ul>
            </div>
          </section>

          <section id="disclosure" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">4. การเปิดเผยข้อมูล</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เราเคารพในความเป็นส่วนตัวของคุณและจะทำการเปิดเผยข้อมูลเท่าที่จำเป็นเท่านั้น โดยมีรายละเอียดดังนี้:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>เปิดเผยต่อครูผู้สอนหรือผู้ดูแล Academy ที่ผู้ใช้เป็นสมาชิก เท่าที่จำเป็นต่อการเรียนการสอน</li>
                <li>เปิดเผยต่อผู้ให้บริการโครงสร้างพื้นฐานที่เกี่ยวข้องกับการให้บริการระบบ</li>
                <li>เปิดเผยตามที่กฎหมายกำหนด หรือเมื่อได้รับการร้องขอจากหน่วยงานของรัฐที่มีอำนาจ</li>
              </ul>
              <p class="font-medium text-gray-900">เราขอยืนยันว่า เราไม่มีนโยบายการขายข้อมูลส่วนบุคคลของคุณให้แก่บุคคลที่สามโดยเด็ดขาด</p>
            </div>
          </section>

          <section id="cookies" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">5. คุกกี้และเทคโนโลยีที่คล้ายกัน</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เรามีการใช้คุกกี้ (Cookies) และเทคโนโลยีที่คล้ายกัน เพื่อเพิ่มประสิทธิภาพในการใช้งานและให้บริการที่เหมาะสมที่สุด โดยใช้เพื่อเก็บสถานะการเข้าสู่ระบบ (Token) และการตั้งค่าการแสดงผลส่วนตัวของคุณ</p>
            </div>
          </section>

          <section id="retention" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">6. ระยะเวลาเก็บรักษา</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เราจะเก็บรักษาข้อมูลส่วนบุคคลของคุณตราบเท่าที่บัญชีผู้ใช้งานของคุณยังคงสถานะการใช้งานอยู่ และจะเก็บต่อไปเท่าที่จำเป็นตามที่กฎหมายกำหนด หรือเพื่อความจำเป็นในการระงับข้อพิพาทที่อาจเกิดขึ้น</p>
            </div>
          </section>

          <section id="rights" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">7. สิทธิของเจ้าของข้อมูล (ตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562)</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>ในฐานะเจ้าของข้อมูลส่วนบุคคล คุณมีสิทธิตามกฎหมายที่เกี่ยวข้อง ดังนี้:</p>
              <ul class="list-disc pl-5 space-y-2">
                <li>สิทธิในการเข้าถึงและขอรับสำเนาข้อมูลส่วนบุคคลของคุณ</li>
                <li>สิทธิในการขอแก้ไขข้อมูลส่วนบุคคลให้ถูกต้อง เป็นปัจจุบัน และสมบูรณ์</li>
                <li>สิทธิในการขอให้ลบ หรือทำลายข้อมูลส่วนบุคคลของคุณ</li>
                <li>สิทธิในการคัดค้านการประมวลผลข้อมูลส่วนบุคคล</li>
                <li>สิทธิในการขอให้ระงับการใช้ข้อมูลส่วนบุคคล</li>
                <li>สิทธิในการถอนความยินยอมในการประมวลผลข้อมูลส่วนบุคคลที่คุณได้ให้ไว้</li>
              </ul>
            </div>
          </section>

          <section id="minors" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">8. ข้อมูลของผู้เยาว์</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เนื่องจากระบบ nuxnan ถูกนำไปใช้งานในสถานศึกษา ผู้ใช้ที่มีอายุต่ำกว่าเกณฑ์ที่กฎหมายกำหนด จำเป็นต้องได้รับความยินยอมจากผู้ปกครองหรือสถานศึกษาก่อนการใช้งานระบบ เพื่อให้สอดคล้องกับข้อกำหนดทางกฎหมาย</p>
            </div>
          </section>

          <section id="changes-contact" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-4">9. การเปลี่ยนแปลงนโยบายและช่องทางติดต่อ</h2>
            <div class="text-gray-700 text-sm sm:text-base leading-relaxed space-y-3">
              <p>เราอาจมีการปรับปรุงแก้ไขนโยบายความเป็นส่วนตัวนี้เป็นครั้งคราว โดยจะมีการแจ้งให้ผู้ใช้งานทราบถึงการเปลี่ยนแปลง หากคุณมีคำถามหรือข้อสงสัยเกี่ยวกับนโยบายความเป็นส่วนตัว สามารถติดต่อเราได้ที่อีเมล:</p>
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
          <NuxtLink to="/TermsOfService" class="text-indigo-600 hover:text-indigo-700 min-h-[44px] flex items-center">ข้อกำหนดการใช้งาน</NuxtLink>
          <a href="mailto:contact@plearnd.com" class="text-gray-600 hover:text-gray-900 min-h-[44px] flex items-center">ติดต่อเรา: contact@plearnd.com</a>
        </div>
      </div>
    </footer>
  </div>
</template>
