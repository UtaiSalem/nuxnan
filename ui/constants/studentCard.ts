/**
 * ตัวตนโรงเรียนที่พิมพ์ลงหน้าบัตรนักเรียน
 *
 * ค่าเริ่มต้นคือค่าที่หน้าชั่วคราว /student-card ใช้อยู่เดิม เพื่อให้หน้านั้น
 * แสดงผลเหมือนเดิมทุกจุดโดยไม่ต้องแก้ ส่วนหน้าจัดการบัตรใน /academies ต้องส่ง
 * ค่าของโรงเรียนตาม URL เข้ามาเสมอ ไม่งั้นโรงเรียนอื่นจะเห็นชื่อโรงเรียนผิด
 *
 * อยู่แยกไฟล์เพราะ defineProps() อ้างถึงตัวแปรที่ประกาศใน <script setup>
 * ไม่ได้ (มันถูกยกออกไปนอก setup()) — อ้างได้เฉพาะสิ่งที่ import เข้ามา
 */
export interface StudentCardSchool {
    name_th: string
    name_en: string
    address: string
    logo_url: string | null
}

export const DEFAULT_STUDENT_CARD_SCHOOL: StudentCardSchool = {
    name_th: 'โรงเรียนจริยธรรมศึกษามูลนิธิ',
    name_en: 'CHARIYATHAMSUKSA FOUNDATION SCHOOL',
    address: '148 ม.8 ต.สะกอม อ.จะนะ จ.สงขลา 90130 โทร.081-5412281',
    logo_url: null,
}
