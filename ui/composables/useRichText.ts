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

  return {
    convertPlainTextToHtml,
    sanitizeHtml
  }
}
