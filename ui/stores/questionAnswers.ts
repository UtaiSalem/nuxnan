
import { defineStore } from 'pinia';

export const useQuestionAnswersStore = defineStore('questionAnswers', {
    state: () => ({
        // Stores CONFIRMED answers: { quizId: { questionId: answerId } }
        answeredQuestions: {}, // User's confirmed answers (persistent/saved)
        
        // Stores SUBMITTING status: { quizId: { questionId: boolean } }
        submittingQuestions: {}, // Prevent double submission
        
        // Stores TEMPORARY answers (selected but not confirmed): { quizId: { questionId: answerId } }
        temporaryAnswers: {}, // UI state before saving
        
        // Stores ERRORS: { quizId: { questionId: errorMsg } }
        questionErrors: {},
    }),
    
    actions: {
        // Check if a question is CONFIRMED
        isQuestionAnswered(quizId: number | string, questionId: number | string) {
            return this.answeredQuestions[quizId]?.hasOwnProperty(questionId) || false;
        },
        
        // Save a CONFIRMED answer
        setAnsweredQuestion(quizId: number | string, questionId: number | string, answerId: number | string) {
            if (!this.answeredQuestions[quizId]) {
                this.answeredQuestions[quizId] = {};
            }
            this.answeredQuestions[quizId][questionId] = answerId;
            
            // Clear temporary state on confirmation
            if (this.temporaryAnswers[quizId]) {
                delete this.temporaryAnswers[quizId][questionId];
            }
            if (this.questionErrors[quizId]) {
                delete this.questionErrors[quizId][questionId];
            }
        },
        
        // Check submission status
        isQuestionSubmitting(quizId: number | string, questionId: number | string) {
            return this.submittingQuestions[quizId]?.hasOwnProperty(questionId) || false;
        },
        
        // Set submission status
        setQuestionSubmitting(quizId: number | string, questionId: number | string, isSubmitting: boolean) {
            if (!this.submittingQuestions[quizId]) {
                this.submittingQuestions[quizId] = {};
            }
            
            if (isSubmitting) {
                this.submittingQuestions[quizId][questionId] = true;
            } else {
                delete this.submittingQuestions[quizId][questionId];
            }
        },
        
        // Set TEMPORARY answer (Selection)
        setTemporaryAnswer(quizId: number | string, questionId: number | string, answerId: number | string) {
            if (!this.temporaryAnswers[quizId]) {
                this.temporaryAnswers[quizId] = {};
            }
            this.temporaryAnswers[quizId][questionId] = answerId;
        },
        
        // Get TEMPORARY answer
        getTemporaryAnswer(quizId: number | string, questionId: number | string) {
            return this.temporaryAnswers[quizId]?.[questionId] || null;
        },
        
        setQuestionError(quizId: number | string, questionId: number | string, error: any) {
            if (!this.questionErrors[quizId]) {
                this.questionErrors[quizId] = {};
            }
            this.questionErrors[quizId][questionId] = error;
        },
        
        clearQuestionError(quizId: number | string, questionId: number | string) {
            if (this.questionErrors[quizId]) {
                delete this.questionErrors[quizId][questionId];
            }
        },
        
        getQuestionError(quizId: number | string, questionId: number | string) {
            return this.questionErrors[quizId]?.[questionId] || null;
        },
        
        // Clear specific question state (e.g. for re-attempt or edit)
        clearAnsweredQuestion(quizId: number | string, questionId: number | string) {
            if (this.answeredQuestions[quizId]) {
                delete this.answeredQuestions[quizId][questionId];
            }
            // Keep temporary answer if any? Usually clear all.
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
        
        clearQuizAnswers(quizId: number | string) {
            delete this.answeredQuestions[quizId];
            delete this.submittingQuestions[quizId];
            delete this.temporaryAnswers[quizId];
            delete this.questionErrors[quizId];
        },
        
        clearAllAnswers() {
            this.answeredQuestions = {};
            this.submittingQuestions = {};
            this.temporaryAnswers = {};
            this.questionErrors = {};
        },
        
        // For editing/updating an answer
        updateAnsweredQuestion(quizId: number | string, questionId: number | string, newAnswerId: number | string) {
            if (this.answeredQuestions[quizId]?.hasOwnProperty(questionId)) {
                this.answeredQuestions[quizId][questionId] = newAnswerId;
            }
        }
    },
    
    getters: {
        getAnswerForQuestion: (state) => (quizId: number | string, questionId: number | string) => {
            return state.answeredQuestions[quizId]?.[questionId] || null;
        },
        
        answeredQuestionsCount: (state) => (quizId: number | string) => {
            const answeredCount = state.answeredQuestions[quizId] ? Object.keys(state.answeredQuestions[quizId]).length : 0;
            // Count unique temporary answers not yet confirmed? 
            // Logic: Count confirmed + (temporary unique from confirmed)?
            // User provided logic seems to imply tracking total engaged questions
             const uniqueTemporaryCount = state.temporaryAnswers[quizId] ? Object.keys(state.temporaryAnswers[quizId]).filter(
                questionId => !state.answeredQuestions[quizId]?.hasOwnProperty(questionId)
            ).length : 0;
            return answeredCount + uniqueTemporaryCount;
        },
        
        submittingQuestionsCount: (state) => (quizId: number | string) => {
            return state.submittingQuestions[quizId] ? Object.keys(state.submittingQuestions[quizId]).length : 0;
        }
    }
});
