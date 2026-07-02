export type LessonScoreStatus =
  | 'none'              // ไม่มี graded activity ในบทเรียนนี้เลย
  | 'not_attempted'     // มี graded activity แต่ยังไม่ส่ง/ทำ
  | 'submitted'         // ส่งแล้ว (assignment) — รอครูตรวจ ไม่รู้คะแนน
  | 'awaiting_grading'  // quiz/assignment ส่งแล้วแต่ยังไม่มี points
  | 'scored'            // มีคะแนน แต่ไม่มี threshold → แสดงแค่ earned/max
  | 'passed'            // คะแนน >= passing_threshold
  | 'failed'            // คะแนน < passing_threshold

export interface LessonProgressSummary {
  id: number
  title: string
  completed: boolean
  progress_percentage: number
  status_label: string
  score_status: LessonScoreStatus
  has_graded_activity: boolean
  score: number | null
  max_score: number | null
  score_percentage: number | null
  activity_counts: {
    assignments: number
    quizzes: number
    questions: number
  }
}
