/**
 * ย่อรูปฝั่ง client ก่อนอัพโหลด
 *
 * ครูส่วนใหญ่ถ่ายรูปนักเรียนจากมือถือ ซึ่งได้ไฟล์ 3–12 MB ทะลุเพดานของ PHP
 * (upload_max_filesize / post_max_size) แล้วเด้งกลับมาเป็น error ที่อ่านไม่รู้เรื่อง
 * จึงบีบให้อยู่ใต้งบไบต์ตั้งแต่ก่อนออกจากเบราว์เซอร์
 *
 * หลักการ: ลด quality ก่อน (เสียรายละเอียดน้อยกว่า) ถ้ายังไม่พอค่อยลดขนาดภาพ
 */

export interface CompressImageOptions {
    /** งบไบต์สูงสุดของไฟล์ผลลัพธ์ — ต้องต่ำกว่าเพดานฝั่งเซิร์ฟเวอร์เสมอ */
    maxBytes?: number
    /** ด้านที่ยาวที่สุดเริ่มต้น (px) รูปที่ใหญ่กว่านี้จะถูกย่อลงก่อนรอบแรก */
    maxDimension?: number
    /** คุณภาพ JPEG เริ่มต้น */
    quality?: number
    /** ไม่ลด quality ต่ำกว่านี้ ต่ำกว่านี้หน้าคนเริ่มเละจนใช้ทำบัตรไม่ได้ */
    minQuality?: number
    /** ด้านยาวที่สุดที่ยอมย่อลงไปได้ต่ำสุด (px) */
    minDimension?: number
}

export interface CompressImageResult {
    file: File
    /** true เมื่อมีการ re-encode จริง — false แปลว่าไฟล์เดิมอยู่ในงบอยู่แล้ว */
    compressed: boolean
    originalBytes: number
    bytes: number
}

const DEFAULTS: Required<CompressImageOptions> = {
    maxBytes: 8 * 1024 * 1024,
    maxDimension: 1600,
    quality: 0.9,
    minQuality: 0.5,
    minDimension: 600,
}

export function formatBytes(bytes: number): string {
    if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
    if (bytes >= 1024) return `${Math.round(bytes / 1024)} KB`
    return `${bytes} bytes`
}

function loadImage(file: File): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file)
        const img = new Image()
        img.onload = () => {
            URL.revokeObjectURL(url)
            resolve(img)
        }
        img.onerror = () => {
            URL.revokeObjectURL(url)
            reject(new Error('decode-failed'))
        }
        img.src = url
    })
}

function canvasToBlob(canvas: HTMLCanvasElement, type: string, quality: number): Promise<Blob | null> {
    return new Promise(resolve => canvas.toBlob(resolve, type, quality))
}

/** เปลี่ยนนามสกุลให้ตรงกับชนิดไฟล์จริง — backend ตั้งชื่อไฟล์ที่เก็บจากนามสกุลเดิมของไฟล์ */
function withJpegExtension(name: string): string {
    const base = name.replace(/\.[^./\\]+$/, '')

    return `${base || 'photo'}.jpg`
}

/**
 * คืนไฟล์ที่ขนาดไม่เกิน maxBytes
 *
 * ถ้าไฟล์ต้นทางอยู่ในงบอยู่แล้วจะคืนไฟล์เดิมโดยไม่แตะ เพื่อไม่ทำให้รูปที่ดีอยู่แล้วแย่ลง
 * ถ้าเบราว์เซอร์ decode ไม่ได้ (เช่น HEIC บางรุ่น) จะคืนไฟล์เดิมให้ฝั่งเซิร์ฟเวอร์ตัดสินแทน
 */
export async function compressImage(file: File, options: CompressImageOptions = {}): Promise<CompressImageResult> {
    const opts = { ...DEFAULTS, ...options }
    const originalBytes = file.size

    const unchanged = (): CompressImageResult => ({
        file,
        compressed: false,
        originalBytes,
        bytes: originalBytes,
    })

    if (!file.type.startsWith('image/') || originalBytes <= opts.maxBytes) return unchanged()

    let img: HTMLImageElement
    try {
        img = await loadImage(file)
    } catch {
        return unchanged()
    }

    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    if (!ctx) return unchanged()

    const longestSide = Math.max(img.width, img.height)
    let scale = longestSide > opts.maxDimension ? opts.maxDimension / longestSide : 1
    let quality = opts.quality
    let best: Blob | null = null

    // ลด quality จนถึงพื้น แล้วค่อยหดขนาดภาพลงทีละ 20% วนจนเข้างบ
    while (true) {
        const width = Math.max(1, Math.round(img.width * scale))
        const height = Math.max(1, Math.round(img.height * scale))
        canvas.width = width
        canvas.height = height

        // JPEG ไม่มี alpha — ถ้าไม่รองพื้นขาวไว้ก่อน รูป PNG โปร่งใสจะกลายเป็นพื้นดำ
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, width, height)
        ctx.drawImage(img, 0, 0, width, height)

        const blob = await canvasToBlob(canvas, 'image/jpeg', quality)
        if (!blob) return unchanged()
        best = blob

        if (blob.size <= opts.maxBytes) break

        if (quality > opts.minQuality) {
            quality = Math.max(opts.minQuality, quality - 0.1)
            continue
        }

        const nextLongest = Math.round(longestSide * scale * 0.8)
        if (nextLongest < opts.minDimension) break
        scale = (nextLongest / longestSide)
    }

    // บีบเท่าที่ทำได้แล้วยังไม่เข้างบ — ส่งตัวที่เล็กที่สุดที่ได้ ดีกว่าส่งต้นฉบับที่ใหญ่กว่า
    const output = new File([best], withJpegExtension(file.name), {
        type: 'image/jpeg',
        lastModified: Date.now(),
    })

    return {
        file: output,
        compressed: true,
        originalBytes,
        bytes: output.size,
    }
}

export function useImageCompressor(defaults: CompressImageOptions = {}) {
    return {
        compressImage: (file: File, options: CompressImageOptions = {}) =>
            compressImage(file, { ...defaults, ...options }),
        formatBytes,
    }
}
