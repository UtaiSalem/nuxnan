import { describe, expect, it } from 'vitest'
import { parseQRCode, QR_TYPES } from '../types/qr'

describe('activity QR parsing', () => {
  it('parses a complete activity payload', () => {
    const result = parseQRCode('CHECKIN:ACTIVITY:7:12:34:abc123')
    expect(result.type).toBe('activity_checkin')
    expect(result.isValid).toBe(true)
    expect(result.data).toEqual(['7', '12', '34', 'abc123'])
  })
  it('rejects a three-field activity payload', () => expect(parseQRCode('CHECKIN:ACTIVITY:7:34:abc123').isValid).toBe(false))
  it('keeps school check-in parsing intact', () => expect(parseQRCode('CHECKIN:SCHOOL:7:34:abc123').type).toBe('school_checkin'))
  it('keeps generic class check-in parsing intact', () => expect(parseQRCode('CHECKIN:class_123:sess_1').type).toBe('checkin'))
  it('preserves activity token case', () => {
    const result = parseQRCode('checkin:activity:7:12:34:AbC123')
    expect(result.type).toBe('activity_checkin')
    expect(result.data[3]).toBe('AbC123')
  })
  it('rejects unknown QR content', () => {
    const result = parseQRCode('SOMETHINGELSE:1')
    expect(result.type).toBe('unknown')
    expect(result.isValid).toBe(false)
  })
  it('documents the shared CHECKIN prefix', () => expect(QR_TYPES.activity_checkin.prefix).toBe('CHECKIN'))
})
