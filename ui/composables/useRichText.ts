import DOMPurify from 'dompurify'

export const useRichText = () => {
  /**
   * Utility function to convert plain text to HTML
   * Handles line breaks, multiple spaces, and preserves formatting
   */
  const convertPlainTextToHtml = (text: string | null | undefined): string => {
    if (!text) return ''
    
    // If the text already contains HTML tags, return as-is
    if (/<[a-z][\s\S]*>/i.test(text)) {
      return text
    }
    
    // Convert plain text to HTML
    // Split by double line breaks for paragraphs
    const paragraphs = text.split(/\n\n+/)
    
    return paragraphs.map(paragraph => {
      // Convert single line breaks to <br>
      const lines = paragraph.split(/\n/)
      const content = lines.map(line => {
        // Preserve multiple spaces
        return line.replace(/  +/g, match => '&nbsp;'.repeat(match.length))
      }).join('<br>')
      
      // Wrap in paragraph tag
      return `<p>${content}</p>`
    }).join('')
  }

  /**
   * Sanitize HTML content to prevent XSS
   * Allows specific tags and attributes for TipTap and YouTube
   */
  const sanitizeHtml = (html: string | null | undefined): string => {
    if (!html) return ''

    return DOMPurify.sanitize(html, {
      ADD_TAGS: ['iframe'],
      ADD_ATTR: [
        'allow', 
        'allowfullscreen', 
        'frameborder', 
        'data-type', 
        'data-checked',
        'target',
        'rel',
        'sandbox'
      ],
      FORCE_BODY: true
    })
  }

  /**
   * ห่อ <table> ทุกตัวด้วยกล่อง .rtv-table-scroll
   * ตารางกว้างเกินจอจะเลื่อนแนวนอนในกล่องตัวเอง แทนที่จะดันทั้งหน้าให้เลื่อน
   * หรือถูกบีบจนหัวคอลัมน์ภาษาไทยแตกเป็นหลายบรรทัด
   * (ใช้ string replace ไม่ใช่ DOM เพราะต้องทำงานตอน SSR ด้วย — ตารางซ้อนกันไม่ได้อยู่แล้ว)
   */
  const wrapTablesForScroll = (html: string | null | undefined): string => {
    if (!html) return ''
    if (!/<table[\s>]/i.test(html)) return html
    if (html.includes('rtv-table-scroll')) return html

    return html
      .replace(/<table(\s|>)/gi, '<div class="rtv-table-scroll"><table$1')
      .replace(/<\/table\s*>/gi, '</table></div>')
  }

  return {
    convertPlainTextToHtml,
    sanitizeHtml,
    wrapTablesForScroll
  }
}
