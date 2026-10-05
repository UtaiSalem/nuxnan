import { ref } from 'vue'

/**
 * Shared "cancel / claw back" flow for the admin transaction screens.
 *
 * One endpoint per ledger table handles both peer transfers and intra-user
 * conversions — the backend inspects the row and dispatches. The caller passes
 * which table the row belongs to ('points' | 'wallet') plus the row (which must
 * already carry the server-computed `reversible` / `reversal_kind` flags).
 */
export const useReverseTransaction = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  const swal = useSweetAlert()
  const { parseApiError } = useApiError()

  const reversingId = ref<number | null>(null)

  const formatUnit = (n: any, unit: string) => {
    const num = typeof n === 'string' ? parseFloat(n) : (n || 0)
    if (unit === 'THB') {
      return '฿' + new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)
    }
    return new Intl.NumberFormat('th-TH').format(num) + ' แต้ม'
  }

  const reverse = async (
    kind: 'points' | 'wallet',
    tx: any,
    onDone?: () => void | Promise<void>
  ) => {
    if (reversingId.value !== null) return

    const isConversion = tx.reversal_kind === 'conversion'
    const title = isConversion
      ? `ยกเลิกรายการแปลงแต้ม/เงิน (รายการ #${tx.id})`
      : `ยกเลิก + ดึงคืน (รายการ #${tx.id})`

    const reason = await swal.input(title, {
      inputType: 'textarea',
      placeholder: 'ระบุเหตุผล เช่น ตรวจพบการทุจริต — ระบบจะดึงยอดคืนเท่าที่มีแล้วคืนให้อีกฝั่ง',
      confirmText: 'ยืนยันการยกเลิก',
      inputValidator: (v: string) => (!v || !v.trim() ? 'กรุณาระบุเหตุผล' : null)
    })
    if (!reason || !reason.trim()) return

    reversingId.value = tx.id
    try {
      const token = useCookie('token')
      const path = kind === 'points'
        ? `points-transactions/${tx.id}/reverse`
        : `transactions/${tx.id}/reverse`
      const res = await $fetch<any>(`${apiBase}/api/admin/wallet/${path}`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
        body: { reason: reason.trim() }
      })
      if (res.success) {
        const d = res.data || {}
        const unit = d.unit || (kind === 'points' ? 'points' : 'THB')
        const reversed = formatUnit(d.reversed, unit)
        const shortfall = Number(d.shortfall || 0)
        const extra = shortfall > 0 ? ` (ขาด ${formatUnit(shortfall, unit)} เพราะถูกใช้ไปแล้ว)` : ''
        const restored = d.restored != null && d.restored_unit
          ? ` • คืนอีกฝั่ง ${formatUnit(d.restored, d.restored_unit)}`
          : ''
        swal.success(`ยกเลิกรายการสำเร็จ: ดึงคืน ${reversed}${extra}${restored}`)
        if (onDone) await onDone()
      } else {
        swal.error(res.message || 'ยกเลิกรายการไม่สำเร็จ')
      }
    } catch (err: any) {
      console.error('Reverse transaction failed:', err)
      const parsed = parseApiError(err, 'ยกเลิกรายการไม่สำเร็จ')
      swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
    } finally {
      reversingId.value = null
    }
  }

  return { reverse, reversingId }
}
