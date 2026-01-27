import { computed, ref } from 'vue';
import axios from 'axios';

/**
 * Composable สำหรับคำนวณความคืบหน้าของสมาชิก
 * @param {Ref<Object>} member - Member object
 * @param {Ref<Number>} totalScore - Total course score
 * @returns {Object} Progress data and utilities
 */
export function useMemberProgress(member, totalScore, courseId = null) {
  // สถานะการตรวจสอบคะแนน
  const scoreValidation = ref({
    isValid: true,
    errorMessage: '',
    hasError: false,
    severity: 'none' // 'none', 'warning', 'error', 'critical'
  });

  // สถานะการประมวลผลคะแนน
  const isProcessingGrades = ref(false);
  const processGradesMessage = ref('');

  /**
   * ฟังก์ชันตรวจสอบความถูกต้องของคะแนน
   * @param {Number} achievedScore - คะแนนที่นักเรียนทำได้
   * @param {Number} totalScore - คะแนนรวมสูงสุดของรายวิชา
   * @returns {Object} ผลการตรวจสอบ
   */
  function validateScore(achievedScore, total) {
    // ตรวจสอบว่ามีค่าที่จำเป็นครบถ้วน
    if (!achievedScore || !total) {
      return {
        isValid: true,
        errorMessage: '',
        hasError: false,
        severity: 'none'
      };
    }

    // ตรวจสอบค่าติดลบ
    if (achievedScore < 0 || total < 0) {
      const errorMessage = `ข้อผิดพลาด: พบค่าคะแนนติดลบ (คะแนนที่ทำได้: ${achievedScore}, คะแนนรวม: ${total})`;
      
      console.error('Score Validation Error (Negative Values):', {
        achievedScore,
        totalScore: total,
        timestamp: new Date().toISOString(),
        member: member.value
      });

      return {
        isValid: false,
        errorMessage,
        hasError: true,
        severity: 'critical'
      };
    }

    // ตรวจสอบว่าคะแนนที่ทำได้ไม่เกินคะแนนรวม
    if (achievedScore > total) {
      const percentage = Math.round((achievedScore / total) * 100);
      const errorMessage = `ข้อผิดพลาด: คะแนนที่บันทึก (${achievedScore}) สูงกว่าคะแนนรวมสูงสุด (${total}) คิดเป็น ${percentage}%`;
      
      // กำหนดระดับความรุนแรงตามส่วนต่างของคะแนน
      const severity = achievedScore > total * 1.5 ? 'critical' : 'error';
      
      // บันทึกข้อผิดพลาดลงใน console
      console.error('Score Validation Error (Score Exceeds Total):', {
        achievedScore,
        totalScore: total,
        percentage,
        exceedBy: achievedScore - total,
        severity,
        timestamp: new Date().toISOString(),
        member: member.value
      });

      return {
        isValid: false,
        errorMessage,
        hasError: true,
        severity
      };
    }

    return {
      isValid: true,
      errorMessage: '',
      hasError: false,
      severity: 'none'
    };
  }

  /**
   * ฟังก์ชันสำหรับเรียกใช้งาน Backend API เพื่อแก้ไขคะแนน
   * @param {Number} courseId - รหัสคอร์ส
   * @param {Number} memberId - รหัสสมาชิก
   * @returns {Promise<Object>} ผลการดำเนินการ
   */
  async function fixScoreIssues(courseId, memberId) {
    if (!courseId || !memberId) {
      return {
        success: false,
        message: 'ไม่สามารถแก้ไขข้อมูลได้: ไม่พบรหัสคอร์สหรือรหัสสมาชิก'
      };
    }

    isProcessingGrades.value = true;
    processGradesMessage.value = 'กำลังดำเนินการแก้ไขคะแนน...';

    try {
      const response = await axios.post(`/courses/${courseId}/members/${memberId}/process-grades`);
      
      if (response.data.success) {
        processGradesMessage.value = response.data.message || 'แก้ไขคะแนนเรียบร้อยแล้ว';
        
        // อัปเดตคะแนนใน member object หากมีการเปลี่ยนแปลง
        if (response.data.total_score !== undefined && member.value) {
          member.value.achieved_score = response.data.total_score;
        }
        
        return {
          success: true,
          message: response.data.message,
          data: response.data
        };
      } else {
        processGradesMessage.value = 'ไม่สามารถแก้ไขคะแนนได้';
        return {
          success: false,
          message: response.data.message || 'เกิดข้อผิดพลาดในการแก้ไขคะแนน'
        };
      }
    } catch (error) {
      const errorMessage = error.response?.data?.error || error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
      processGradesMessage.value = errorMessage;
      
      console.error('Error fixing score issues:', {
        error,
        courseId,
        memberId,
        timestamp: new Date().toISOString()
      });
      
      return {
        success: false,
        message: errorMessage
      };
    } finally {
      isProcessingGrades.value = false;
    }
  }

  // คำนวณ percentage พร้อมการตรวจสอบความถูกต้อง
  const percentage = computed(() => {
    const achievedScore = member.value?.achieved_score || 0;
    const total = totalScore.value || 0;
    
    if (!achievedScore || !total) return 0;
    
    // ตรวจสอบความถูกต้องของคะแนน
    const validation = validateScore(achievedScore, total);
    scoreValidation.value = validation;
    
    // หากคะแนนผิดพลาด ให้คืนค่า 0 เพื่อป้องกันการแสดงผลที่ไม่ถูกต้อง
    if (!validation.isValid) {
      return 0;
    }
    
    return Math.min(100, Math.round((achievedScore / total) * 100));
  });

  // สถานะ progress
  const progressStatus = computed(() => {
    const pct = percentage.value;
    if (pct >= 80) return 'excellent';
    if (pct >= 50) return 'good';
    if (pct >= 30) return 'fair';
    return 'poor';
  });

  // สี progress bar (รองรับทั้ง Tailwind classes และ CSS variables)
  const progressColor = computed(() => {
    const status = progressStatus.value;
    const colorMap = {
      excellent: { 
        bg: 'bg-success', 
        text: 'text-success-600', 
        ring: 'ring-success-200',
        var: 'var(--success-color, #22c55e)',
        gradient: 'from-green-400 to-emerald-500'
      },
      good: { 
        bg: 'bg-primary', 
        text: 'text-primary-600', 
        ring: 'ring-primary-200',
        var: 'var(--primary-color, #3b82f6)',
        gradient: 'from-blue-400 to-indigo-500'
      },
      fair: { 
        bg: 'bg-warning', 
        text: 'text-warning-600', 
        ring: 'ring-warning-200',
        var: 'var(--warning-color, #eab308)',
        gradient: 'from-yellow-400 to-orange-500'
      },
      poor: { 
        bg: 'bg-danger', 
        text: 'text-danger-600', 
        ring: 'ring-danger-200',
        var: 'var(--danger-color, #ef4444)',
        gradient: 'from-red-400 to-rose-500'
      }
    };
    return colorMap[status];
  });

  // ข้อความแสดงสถานะ
  const statusMessage = computed(() => {
    const messages = {
      excellent: 'ยอดเยี่ยม! 🎉',
      good: 'ดีมาก! 👍',
      fair: 'พอใช้ 💪',
      poor: 'ต้องพยายามเพิ่ม 📚'
    };
    return messages[progressStatus.value];
  });

  // ไอคอนสถานะ
  const statusIcon = computed(() => {
    const icons = {
      excellent: 'heroicons:trophy-solid',
      good: 'heroicons:star-solid',
      fair: 'heroicons:chart-bar-solid',
      poor: 'heroicons:arrow-trending-up-solid'
    };
    return icons[progressStatus.value];
  });

  // คำนวณคะแนนที่เหลือ
  const remainingScore = computed(() => {
    return Math.max(0, totalScore.value - (member.value?.achieved_score || 0));
  });

  // เปอร์เซ็นต์ที่เหลือ
  const remainingPercentage = computed(() => {
    return Math.max(0, 100 - percentage.value);
  });

  // Style สำหรับ progress bar (รองรับ CSS variables)
  const progressBarStyle = computed(() => {
    return {
      width: `${percentage.value}%`,
      backgroundColor: progressColor.value.var
    };
  });

  return {
    percentage,
    progressStatus,
    progressColor,
    statusMessage,
    statusIcon,
    remainingScore,
    remainingPercentage,
    progressBarStyle,
    scoreValidation,
    validateScore,
    fixScoreIssues,
    isProcessingGrades,
    processGradesMessage
  };
}
