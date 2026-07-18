export interface AdRewardSplits {
  student: number
  course: number
  academy: number
  platform: number
  policy_id: number
  policy_version: number
}

export interface AdCompletionResult {
  valid: boolean
  reason: string | null
  reward?: { splits: AdRewardSplits }
}

export const useAdDelivery = () => {
  const api = useApi()

  const fingerprint = async (): Promise<string> => {
    const value = `${navigator.userAgent}|${screen.width}x${screen.height}`
    if (globalThis.crypto?.subtle) {
      const bytes = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(value))
      return Array.from(new Uint8Array(bytes)).map(byte => byte.toString(16).padStart(2, '0')).join('')
    }
    return value
  }

  const start = async (advertId: number) => {
    const response = await api.post<{ token: string; delivery_id: number; required_duration: number }>(
      `/api/adverts/${advertId}/deliveries/start`,
      { session_id: crypto.randomUUID(), device_fingerprint: await fingerprint() },
    )
    return { token: response.token, deliveryId: response.delivery_id, requiredDuration: response.required_duration }
  }

  const heartbeat = async (deliveryId: number, token: string, visibilityRatio: number): Promise<void> => {
    await api.post(`/api/ad-deliveries/${deliveryId}/heartbeat`, { token, visibility_ratio: visibilityRatio })
  }

  const complete = async (deliveryId: number, token: string): Promise<AdCompletionResult> => {
    try {
      const response = await api.post<any>(`/api/ad-deliveries/${deliveryId}/complete`, { token })
      return { valid: response.valid, reason: response.reason ?? null, reward: response.reward }
    } catch (error: any) {
      if (error.status === 409 || error.originalError?.status === 409) return { valid: false, reason: 'replayed' }
      throw error
    }
  }

  return { start, heartbeat, complete }
}
