import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useWallet } from '../composables/useWallet'

// Mock useRuntimeConfig
vi.stubGlobal('useRuntimeConfig', () => ({
  public: {
    apiBase: 'http://localhost:8000'
  }
}))

// Mock Pinia auth store
const mockSetWallet = vi.fn()
const mockUser = { wallet: 100 }
vi.mock('~/stores/auth', () => ({
  useAuthStore: () => ({
    token: 'test-token',
    user: mockUser,
    setWallet: mockSetWallet
  })
}))

// Mock global $fetch
const mockFetch = vi.fn()
vi.stubGlobal('$fetch', mockFetch)

describe('useWallet.ts unit tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should parse total_balance correctly', async () => {
    mockFetch.mockResolvedValueOnce({
      success: true,
      data: {
        total_balance: 150.50,
        cash_balance: 100
      }
    })

    const { getBalance } = useWallet()
    const result = await getBalance()

    expect(result.total_balance).toBe(150.50)
    expect(mockSetWallet).toHaveBeenCalledWith(150.50)
  })

  it('should fallback to cash_balance if total_balance is missing', async () => {
    mockFetch.mockResolvedValueOnce({
      success: true,
      data: {
        cash_balance: 99.99
      }
    })

    const { getBalance } = useWallet()
    await getBalance()

    expect(mockSetWallet).toHaveBeenCalledWith(99.99)
  })

  it('should support actual 0 balance', async () => {
    mockFetch.mockResolvedValueOnce({
      success: true,
      data: {
        total_balance: 0
      }
    })

    const { getBalance } = useWallet()
    await getBalance()

    expect(mockSetWallet).toHaveBeenCalledWith(0)
  })

  it('should not overwrite store if response contains no valid balance keys', async () => {
    mockFetch.mockResolvedValueOnce({
      success: true,
      data: {
        some_other_field: 'hello'
      }
    })

    const { getBalance } = useWallet()
    
    await expect(getBalance()).rejects.toThrow('API response does not contain any valid balance keys')
    expect(mockSetWallet).not.toHaveBeenCalled()
  })
})
