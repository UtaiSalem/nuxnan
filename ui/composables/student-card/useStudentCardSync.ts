import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'

export const useStudentCardSync = () => {
    const api = useApi()
    const toast = useToast()
    
    const isSyncing = ref(false)
    const isPreviewing = ref(false)
    const syncPreviewData = ref<any>(null)
    
    const previewSync = async (academyId: number, academicYearId?: number) => {
        isPreviewing.value = true
        syncPreviewData.value = null
        try {
            const url = `/api/academies/${academyId}/student-cards/admin/sync/preview`
            const params = academicYearId ? { academic_year_id: academicYearId } : {}
            const response: any = await api.get(url, { params })
            if (response?.success) {
                syncPreviewData.value = response.preview
                return response.preview
            }
        } catch (e) {
            console.error('Failed to preview sync', e)
            toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: 'ไม่สามารถดึงข้อมูลตัวอย่างการซิงค์ได้', life: 3000 })
        } finally {
            isPreviewing.value = false
        }
        return null
    }

    const commitSync = async (academyId: number, academicYearId?: number) => {
        isSyncing.value = true
        try {
            const url = `/api/academies/${academyId}/student-cards/admin/sync/commit`
            const body = { ...(academicYearId ? { academic_year_id: academicYearId } : {}), confirmation: 'SYNC' }
            const response: any = await api.post(url, body)
            if (response?.success) {
                toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ซิงค์ข้อมูลบัตรนักเรียนเรียบร้อยแล้ว', life: 3000 })
                return response.result
            }
        } catch (e) {
            console.error('Failed to commit sync', e)
            toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: 'ไม่สามารถซิงค์ข้อมูลได้', life: 3000 })
        } finally {
            isSyncing.value = false
        }
        return null
    }
    
    const getAuditReport = async (academyId: number, academicYearId?: number) => {
        try {
            const url = `/api/academies/${academyId}/student-cards/admin/audit`
            const params = academicYearId ? { academic_year_id: academicYearId } : {}
            const response: any = await api.get(url, { params })
            if (response?.success) {
                return response.report
            }
        } catch (e) {
            console.error('Failed to fetch audit report', e)
            toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: 'ไม่สามารถดึงรายงานตรวจสอบได้', life: 3000 })
        }
        return null
    }

    return {
        isSyncing,
        isPreviewing,
        syncPreviewData,
        previewSync,
        commitSync,
        getAuditReport
    }
}
