export default defineEventHandler(async (event) => {
  const path = event.path
  
  // Only handle /storage/** paths
  if (!path.startsWith('/storage/')) {
    return
  }

  // Forward to Laravel backend
  const apiBase = useRuntimeConfig(event).public.apiBase || 'http://localhost:8000'
  const backendUrl = `${String(apiBase).replace(/\/$/, '')}${path}`
  
  try {
    const response = await $fetch.raw(backendUrl, {
      method: 'GET',
      responseType: 'arrayBuffer',
    })
    
    // Determine content type based on file extension
    const ext = path.split('.').pop()?.toLowerCase()
    const contentTypes: Record<string, string> = {
      'jpg': 'image/jpeg',
      'jpeg': 'image/jpeg',
      'png': 'image/png',
      'gif': 'image/gif',
      'webp': 'image/webp',
      'svg': 'image/svg+xml',
    }
    
    const contentType = response.headers.get('content-type') || contentTypes[ext || ''] || 'application/octet-stream'
    
    const buffer = Buffer.from(response._data as ArrayBuffer)
    
    event.node.res.setHeader('Content-Type', contentType)
    event.node.res.setHeader('Content-Length', String(buffer.byteLength))
    event.node.res.setHeader('Cache-Control', 'public, max-age=31536000')
    
    return send(event, buffer)
  } catch (error) {
    console.error('Error proxying storage request:', error)
    throw createError({
      statusCode: 404,
      statusMessage: 'File not found',
    })
  }
})
