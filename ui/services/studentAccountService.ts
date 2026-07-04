export const useStudentAccountService = () => {
  const api = useApi()

  /**
   * สร้างลิงก์เชิญ (Activation Link) สำหรับนักเรียนที่รอเปิดบัญชี
   */
  const generateInvitation = async (academyName: string, studentId: number) => {
    try {
      const res: any = await api.post(`/api/academies/${academyName}/students/${studentId}/invite`)
      return res
    } catch (error) {
      console.error('Failed to generate invitation', error)
      throw error
    }
  }

  /**
   * ส่งออก (Export) ลิงก์เชิญทั้งหมดที่ pending เป็นไฟล์ CSV
   */
  const exportInvitations = async (academyName: string) => {
    try {
      const res: any = await api.get(`/api/academies/${academyName}/student-invitations/export`, { responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([res]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `invitations_${academyName}.csv`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    } catch (error) {
      console.error('Failed to export invitations', error)
      throw error
    }
  }

  return {
    generateInvitation,
    exportInvitations
  }
}
