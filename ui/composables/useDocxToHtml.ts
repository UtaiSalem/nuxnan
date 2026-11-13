import type { DocxConversionResult } from '../types/lessonImport'

export const DOCX_MAX_BYTES = 15 * 1024 * 1024

export const useDocxToHtml = () => {
  const convert = async (file: File): Promise<DocxConversionResult> => {
    if (!file.name.toLowerCase().endsWith('.docx')) {
      throw new Error('รองรับเฉพาะไฟล์ .docx')
    }
    if (file.size > DOCX_MAX_BYTES) {
      throw new Error('ไฟล์ใหญ่เกิน 15MB')
    }

    // dynamic import เท่านั้น — ไม่ให้ mammoth ถูกโหลดตอน SSR หรือในหน้าอื่น
    // @ts-ignore
    const mod: any = await import('mammoth/mammoth.browser.js')
    const mammoth = mod.default ?? mod

    const arrayBuffer = await file.arrayBuffer()
    const result = await mammoth.convertToHtml({ arrayBuffer }, {
      styleMap: [
        "p[style-name='Title'] => h1:fresh",
        "p[style-name='Subtitle'] => p:fresh",
        "p[style-name='heading 1'] => h1:fresh",
        "p[style-name='heading 2'] => h2:fresh",
        "p[style-name='heading 3'] => h3:fresh",
        "p[style-name='heading 4'] => h4:fresh",
      ],
      includeDefaultStyleMap: true,
    })

    return {
      html: result.value,
      messages: (result.messages || []).map((m: any) => String(m.message)),
    }
  }

  return { convert }
}
