import { defineStore } from 'pinia';

export const useQuestionAnswersStore = defineStore('questionAnswers', {
    state: () => ({
        // เก็บข้อมูลคำถามที่ผู้ใช้ตอบแล้วแยกตาม quiz ID ในรูปแบบ { quizId: { questionId: answerId } }
        answeredQuestions: {},
        // เก็บข้อมูลการกำลังการส่งคำตอบแยกตาม quiz ID เพื่อป้องกันการส่งซ้ำ
        submittingQuestions: {},
        // เก็บข้อมูลคำตอบชั่วคราวแยกตาม quiz ID (ก่อนยืนยัน)
        temporaryAnswers: {},
        // เก็บข้อมูลข้อผิดพลาดสำหรับแต่ละคำถามแยกตาม quiz ID
        questionErrors: {},
    }),
    
    actions: {
        // ตรวจสอบว่าคำถามถูกตอบแล้วหรือไม่ (แยกตาม quiz ID)
        isQuestionAnswered(quizId, questionId) {
            return this.answeredQuestions[quizId]?.hasOwnProperty(questionId) || false;
        },
        
        // บันทึกคำตอบที่ตอบแล้ว (แยกตาม quiz ID)
        setAnsweredQuestion(quizId, questionId, answerId) {
            // สร้าง object สำหรับ quiz นี้ถ้ายังไม่มี
            if (!this.answeredQuestions[quizId]) {
                this.answeredQuestions[quizId] = {};
            }
            this.answeredQuestions[quizId][questionId] = answerId;
            
            // Clear any temporary answer and errors when answer is confirmed
            if (this.temporaryAnswers[quizId]) {
                delete this.temporaryAnswers[quizId][questionId];
            }
            if (this.questionErrors[quizId]) {
                delete this.questionErrors[quizId][questionId];
            }
        },
        
        // ตรวจสอบว่ากำลังส่งคำตอบหรือไม่ (แยกตาม quiz ID)
        isQuestionSubmitting(quizId, questionId) {
            return this.submittingQuestions[quizId]?.hasOwnProperty(questionId) || false;
        },
        
        // ตั้งค่าสถานะการส่งคำตอบ (แยกตาม quiz ID)
        setQuestionSubmitting(quizId, questionId, isSubmitting) {
            // สร้าง object สำหรับ quiz นี้ถ้ายังไม่มี
            if (!this.submittingQuestions[quizId]) {
                this.submittingQuestions[quizId] = {};
            }
            
            if (isSubmitting) {
                this.submittingQuestions[quizId][questionId] = true;
            } else {
                delete this.submittingQuestions[quizId][questionId];
            }
        },
        
        // บันทึกคำตอบชั่วคราว (ก่อนยืนยัน) (แยกตาม quiz ID)
        setTemporaryAnswer(quizId, questionId, answerId) {
            // สร้าง object สำหรับ quiz นี้ถ้ายังไม่มี
            if (!this.temporaryAnswers[quizId]) {
                this.temporaryAnswers[quizId] = {};
            }
            this.temporaryAnswers[quizId][questionId] = answerId;
        },
        
        // ดึงคำตอบชั่วคราว (แยกตาม quiz ID)
        getTemporaryAnswer(quizId, questionId) {
            return this.temporaryAnswers[quizId]?.[questionId] || null;
        },
        
        // ตั้งค่าข้อผิดพลาดสำหรับคำถาม (แยกตาม quiz ID)
        setQuestionError(quizId, questionId, error) {
            // สร้าง object สำหรับ quiz นี้ถ้ายังไม่มี
            if (!this.questionErrors[quizId]) {
                this.questionErrors[quizId] = {};
            }
            this.questionErrors[quizId][questionId] = error;
        },
        
        // ลบข้อผิดพลาดสำหรับคำถาม (แยกตาม quiz ID)
        clearQuestionError(quizId, questionId) {
            if (this.questionErrors[quizId]) {
                delete this.questionErrors[quizId][questionId];
            }
        },
        
        // ดึงข้อผิดพลาดสำหรับคำถาม (แยกตาม quiz ID)
        getQuestionError(quizId, questionId) {
            return this.questionErrors[quizId]?.[questionId] || null;
        },
        
        // ลบข้อมูลคำตอบเมื่อจำเป็นที่ต้องการ (เช่น เมื่อเริ่มแบบทดสอบใหม่) (แยกตาม quiz ID)
        clearAnsweredQuestion(quizId, questionId) {
            if (this.answeredQuestions[quizId]) {
                delete this.answeredQuestions[quizId][questionId];
            }
            if (this.submittingQuestions[quizId]) {
                delete this.submittingQuestions[quizId][questionId];
            }
            if (this.temporaryAnswers[quizId]) {
                delete this.temporaryAnswers[quizId][questionId];
            }
            if (this.questionErrors[quizId]) {
                delete this.questionErrors[quizId][questionId];
            }
        },
        
        // ล้างข้อมูลทั้งหมดของ quiz หนึ่งๆ
        clearQuizAnswers(quizId) {
            delete this.answeredQuestions[quizId];
            delete this.submittingQuestions[quizId];
            delete this.temporaryAnswers[quizId];
            delete this.questionErrors[quizId];
        },
        
        // ล้างข้อมูลทั้งหมด (เช่น เมื่อ logout)
        clearAllAnswers() {
            this.answeredQuestions = {};
            this.submittingQuestions = {};
            this.temporaryAnswers = {};
            this.questionErrors = {};
        },
        
        // อัพเดทคำตอบเดิม (สำหรับการแก้ไข) (แยกตาม quiz ID)
        updateAnsweredQuestion(quizId, questionId, newAnswerId) {
            if (this.answeredQuestions[quizId]?.hasOwnProperty(questionId)) {
                this.answeredQuestions[quizId][questionId] = newAnswerId;
            }
        }
    },
    
    getters: {
        // ดึงค่าคำตอบของคำถาม (แยกตาม quiz ID)
        getAnswerForQuestion: (state) => (quizId, questionId) => {
            return state.answeredQuestions[quizId]?.[questionId] || null;
        },
        
        // นับจำนวนคำถามที่ตอบแล้วสำหรับ quiz หนึ่งๆ (รวมทั้งที่ยืนยันแล้วและที่เลือกชั่วคราว)
        answeredQuestionsCount: (state) => (quizId) => {
            const answeredCount = state.answeredQuestions[quizId] ? Object.keys(state.answeredQuestions[quizId]).length : 0;
            const temporaryCount = state.temporaryAnswers[quizId] ? Object.keys(state.temporaryAnswers[quizId]).length : 0;
            // นับเฉพาะคำตอบชั่วคราวที่ยังไม่ได้ยืนยันจริง
            const uniqueTemporaryCount = state.temporaryAnswers[quizId] ? Object.keys(state.temporaryAnswers[quizId]).filter(
                questionId => !state.answeredQuestions[quizId]?.hasOwnProperty(questionId)
            ).length : 0;
            return answeredCount + uniqueTemporaryCount;
        },
        
        // นับจำนวนคำถามที่กำลังส่งสำหรับ quiz หนึ่งๆ
        submittingQuestionsCount: (state) => (quizId) => {
            return state.submittingQuestions[quizId] ? Object.keys(state.submittingQuestions[quizId]).length : 0;
        }
    }
});