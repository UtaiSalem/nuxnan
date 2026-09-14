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
   * ด่านสำรองตอนไม่มี DOM (SSR/Nitro)
   *
   * DOMPurify ที่ไม่มี window จะ `return dirty` คืนค่าเดิมทั้งก้อนโดยไม่กรองอะไรเลย
   * (ดูใน dompurify/dist/purify.cjs.js: `if (!DOMPurify.isSupported) { return dirty; }`)
   * ⇒ ถ้า render ฝั่งเซิร์ฟเวอร์ HTML อันตรายจะหลุดลงหน้าเว็บก่อน hydrate
   *
   * ตัวนี้ตัดของอันตรายหลัก ๆ ด้วย regex เป็น defense-in-depth เท่านั้น
   * **ไม่ใช่ตัวกรองที่สมบูรณ์** — ตัวกรองจริงคือ DOMPurify ที่รันบนเบราว์เซอร์
   */
  const stripDangerousMarkup = (html: string): string => {
    return html
      // แท็กที่รันโค้ด/ดึงของนอกได้ ตัดทั้งบล็อกรวมเนื้อใน
      .replace(/<\s*(script|style|object|embed|iframe|form)\b[\s\S]*?<\s*\/\s*\1\s*>/gi, '')
      // แท็กเดี่ยวที่เหลือ (ไม่มีปิด) รวม base/link/meta
      .replace(/<\s*(script|style|object|embed|base|link|meta|form)\b[^>]*>/gi, '')
      // event handler ทุกแบบ: onclick="..." / onerror='...' / onload=alert(1)
      .replace(/\son[a-z-]+\s*=\s*"[^"]*"/gi, '')
      .replace(/\son[a-z-]+\s*=\s*'[^']*'/gi, '')
      .replace(/\son[a-z-]+\s*=\s*[^\s>]+/gi, '')
      // javascript: / data:text/html ใน URL
      .replace(/\s(href|src|xlink:href|action|formaction)\s*=\s*(["'])\s*(javascript|data)\s*:[^"']*\2/gi, ' $1="#"')
      .replace(/\s(href|src|xlink:href|action|formaction)\s*=\s*(javascript|data)\s*:[^\s>]*/gi, ' $1="#"')
  }

  /**
   * property ใน inline style ที่ "ยึดธีม" ไว้กับพื้นขาว — ต้องตัดทิ้งเสมอ
   *
   * เนื้อหาที่ผู้สอน paste มาจาก Gemini / ChatGPT / Google Docs จะติดสีเข้ม
   * มาด้วย (เจอจริง: rgb(27,27,28), rgb(55,65,81), rgb(31,41,55), rgb(17,24,39))
   * inline style ชนะ class เสมอ ⇒ `dark:prose-invert` ทับไม่ได้
   * ผลคือโหมดกลางคืนได้ตัวหนังสือเข้มบนพื้นเข้ม contrast ต่ำถึง 1.00:1
   *
   * รวมถึง --tw-* ที่ติดมาเป็นพรวน (ring/shadow/gradient ฯลฯ) ซึ่งเป็นขยะล้วน
   * และทับตัวแปรธีมของเราได้ถ้าเผลอมี utility class ไปลงที่ element เดียวกัน
   */
  const THEME_LOCKED_DECLARATION =
    /(?:^|;)\s*(?:--tw-[\w-]*|(?:-(?:webkit|moz|ms|o)-)?(?:background-color|background|border-color|border-(?:top|right|bottom|left)-color|outline-color|text-decoration-color|caret-color|column-rule-color|color|fill|stroke))\s*:[^;]*/gi

  /**
   * ถอดสี/พื้นหลัง/สีเส้นที่ฝังมาใน style="" ออก แต่เก็บ property อื่นไว้
   * (เช่น text-align, white-space, width — พวกนี้ไม่เกี่ยวกับธีม)
   *
   * ทำเฉพาะ "ในแท็กจริง" เท่านั้น — regex ชั้นนอกจับ `<tag ...>`
   * เพราะบทเรียนสอน HTML/CSS มีข้อความตัวอย่างอย่าง
   * `&lt;p style="color:red;"&gt;` ที่ escape `<` `>` ไว้แต่เครื่องหมายคำพูดเป็นตัวจริง
   * ถ้า replace ทั้งก้อนจะไปกินตัวอย่างสอนของเขาหายไปด้วย
   */
  const stripThemeLockedStyles = (html: string | null | undefined): string => {
    if (!html || !/\sstyle\s*=/i.test(html)) return html || ''

    return html.replace(/<[a-z][a-z0-9-]*\s[^>]*>/gi, (tag) =>
      tag.replace(/\sstyle\s*=\s*(["'])([\s\S]*?)\1/gi, (_full, quote: string, value: string) => {
        // ตัด "เฉพาะชิ้นที่ไม่เอา" ออกคาที่ ไม่ได้ split แล้วประกอบใหม่
        // เพราะค่าที่เก็บไว้อาจมี entity ที่ข้างในมี ; อยู่ เช่น
        // font-family: &quot;Google Sans Text&quot;, sans-serif
        // ถ้า split(';') จะพัง — ส่วนที่เก็บไว้ต้องออกมาเหมือนเดิมทุกตัวอักษร
        const kept = value
          .replace(THEME_LOCKED_DECLARATION, ';')
          .replace(/;{2,}/g, ';')
          .replace(/^\s*;\s*|\s*;\s*$/g, '')
          .trim()

        return kept ? ` style=${quote}${kept}${quote}` : ''
      })
    )
  }

  /**
   * Sanitize HTML content to prevent XSS
   * Allows specific tags and attributes for TipTap and YouTube
   */
  const sanitizeHtml = (html: string | null | undefined): string => {
    if (!html) return ''

    // ไม่มี DOM (SSR) → DOMPurify ทำงานไม่ได้ ใช้ด่านสำรองแทน
    if (!DOMPurify.isSupported) {
      return stripThemeLockedStyles(stripDangerousMarkup(html))
    }

    return stripThemeLockedStyles(DOMPurify.sanitize(html, {
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
      // เนื้อหา rich text ไม่มีเหตุให้มีฟอร์ม/สไตล์ของตัวเอง
      // (DOMPurify ตัด action="javascript:" ให้อยู่แล้ว แต่ปล่อย <form> ค้างไว้
      //  ซึ่งเปิดทางทำฟอร์มล็อกอินปลอมซ้อนในบทเรียนได้)
      // หมายเหตุ: ห้ามใส่ input/label ในนี้ — TipTap task list ใช้ checkbox จริง
      FORBID_TAGS: ['form', 'style'],
      FORCE_BODY: true
    }))
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
    stripThemeLockedStyles,
    stripDangerousMarkup,
    wrapTablesForScroll
  }
}
