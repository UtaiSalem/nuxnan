export interface PublicAcademy { id: number; name: string; logo?: string | null }
export interface PublicCourse {
  id: number; slug: string; name: string; title?: string; description?: string | null; cover?: string | null
  academy?: PublicAcademy | null; teacher_display_name?: string | null; donation_enabled: boolean
  total_donated_points: number; total_donors: number; active_campaign_count: number
  support_summary?: PublicSupportSummary | null
}
export interface PublicSupportSummary {
  total_donated_points: number; total_donated_cash?: number; total_donors: number; active_campaigns_count: number
  campaign_progress?: Array<{ campaign_id: number; title: string; total_budget?: number | null; spent_budget: number; remaining_budget?: number | null }>
  recent_donors?: Array<{ display_name: string; avatar?: string | null }>
}
export interface PublicCourseListResponse { data: PublicCourse[]; meta?: Record<string, any>; links?: Record<string, any> }
export interface PublicCourseDetailResponse { data: PublicCourse }
export interface PublicSupportSummaryResponse { data: PublicSupportSummary }
export type PublicCourseListParams = { q?: string; academy_id?: number; active_campaign?: boolean; sort?: 'recent' | 'most_supported' | 'most_active'; page?: number }

export const usePublicCourses = () => {
  const config = useRuntimeConfig()
  const base = String(config.public.apiBase || '').replace(/\/$/, '')
  const request = <T>(path: string, query?: Record<string, any>) => $fetch<T>(`${base}${path}`, { query })
  return {
    list: (params: PublicCourseListParams = {}) => request<PublicCourseListResponse>('/api/public/courses', Object.fromEntries(Object.entries(params).filter(([, value]) => value !== undefined && value !== ''))),
    detail: (slugOrId: string | number) => request<PublicCourseDetailResponse>(`/api/public/courses/${encodeURIComponent(String(slugOrId))}`),
    supportSummary: (slugOrId: string | number) => request<PublicSupportSummaryResponse>(`/api/public/courses/${encodeURIComponent(String(slugOrId))}/support-summary`),
  }
}
