export default defineEventHandler(async (event) => {
  const path = event.path
  
  // Only handle /storage/** paths
  if (!path.startsWith('/storage/')) {
    return
  }

  // Forward to Laravel backend
  const backendUrl = `http://localhost:8000${path}`
  
  try {
    const response = await $fetch(backendUrl, {
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
    
    const contentType = contentTypes[ext || ''] || 'application/octet-stream'
    
    event.node.res.setHeader('Content-Type', contentType)
    event.node.res.setHeader('Cache-Control', 'public, max-age=31536000')
    
    return response
  } catch (error) {
    console.error('Error proxying storage request:', error)
    throw createError({
      statusCode: 404,
      statusMessage: 'File not found',
    })
  }
})
