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

/**
 * งบขนาดไฟล์รูปบัตรหลังย่อฝั่ง client (8 MiB)
 *
 * ต้องเดินคู่กับสามค่านี้เสมอ ไม่งั้นครูจะเจอ error ที่อ่านไม่รู้เรื่อง:
 *   - validation 'photo' => max:8192 ใน StudentCardController::updateImage()
 *   - upload_max_filesize ใน php.ini (ตั้งไว้ 10M เผื่อ overhead)
 *   - post_max_size ใน php.ini (ตั้งไว้ 12M — ต้องมากกว่า upload_max_filesize)
 */
export const MAX_STUDENT_PHOTO_BYTES = 8 * 1024 * 1024
