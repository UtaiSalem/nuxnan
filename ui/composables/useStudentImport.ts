import { ref, reactive, computed } from 'vue'
import { useStudentImportService } from '../services/studentImportService'
import type { StudentImportBatch } from '../types/studentImport'

// --- Shared State (Module level) ---
const currentBatch = ref<StudentImportBatch | null>(null)
const isUploading = ref(false)
const isConfirming = ref(false)
const pollInterval = ref<ReturnType<typeof setInterval> | null>(null)

export const useStudentImport = (academyName?: string) => {
  const service = useStudentImportService()

  const stopPolling = () => {
    if (pollInterval.value) {
      clearInterval(pollInterval.value)
      pollInterval.value = null
    }
  }

  const startPolling = (batchId: number) => {
    if (!academyName) return
    stopPolling() // Ensure only one poll is active
    
    pollInterval.value = setInterval(async () => {
      try {
        const batch = await service.getBatch(academyName, batchId)
        currentBatch.value = batch
        
        if (['validated', 'completed', 'partial', 'failed', 'cancelled'].includes(batch.status)) {
          stopPolling()
        }
      } catch (error) {
        console.error('Polling failed:', error)
        stopPolling()
      }
    }, 3000)
  }

  const uploadFile = async (
    file: File, 
    academicYearId?: number | null, 
    defaults?: Record<string, any>
  ): Promise<StudentImportBatch | null> => {
    if (!academyName) throw new Error('Academy name is required')
    isUploading.value = true
    try {
      const batch = await service.uploadBatch(academyName, file, academicYearId, defaults)
      currentBatch.value = batch
      if (batch.status === 'validating' || batch.status === 'uploaded') {
        startPolling(batch.id)
      }
      return batch
    } catch (error) {
      console.error('Upload failed:', error)
      throw error
    } finally {
      isUploading.value = false
    }
  }

  const fetchBatch = async (batchId: number) => {
    if (!academyName) throw new Error('Academy name is required')
    try {
      const batch = await service.getBatch(academyName, batchId)
      currentBatch.value = batch
      return batch
    } catch (error) {
      console.error('Fetch batch failed:', error)
      throw error
    }
  }

  const confirmImport = async () => {
    if (!academyName) throw new Error('Academy name is required')
    if (!currentBatch.value) throw new Error('No batch selected')
    
    isConfirming.value = true
    try {
      await service.confirmBatch(academyName, currentBatch.value.id)
      // Optimistically update status
      currentBatch.value.status = 'processing'
      startPolling(currentBatch.value.id)
    } catch (error) {
      console.error('Confirm failed:', error)
      throw error
    } finally {
      isConfirming.value = false
    }
  }

  const resetState = () => {
    stopPolling()
    currentBatch.value = null
    isUploading.value = false
    isConfirming.value = false
  }

  return {
    currentBatch,
    isUploading,
    isConfirming,
    uploadFile,
    fetchBatch,
    confirmImport,
    resetState,
    startPolling,
    stopPolling
  }
}
