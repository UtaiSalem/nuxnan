/**
 * School Store Management Composable
 * ระบบร้านค้าของโรงเรียน/สถาบัน
 */
export const useSchoolStore = () => {
  const api = useApi()

  // Helper to safely extract data from response
  const extractData = (response: any) => {
    if (!response) return []
    if (Array.isArray(response)) return response
    if (response.data) return response.data
    return response
  }

  // ============================================================
  // STORE SETTINGS
  // ============================================================

  const getStore = async (academyId: number) => {
    const response = await api.get(`/api/academies/${academyId}/store`)
    return response
  }

  const createStore = (academyId: number, data: Record<string, any>) =>
    api.post(`/api/academies/${academyId}/store`, data)

  const updateStore = (academyId: number, data: Record<string, any>) =>
    api.patch(`/api/academies/${academyId}/store`, data)

  const getStoreStats = async (academyId: number) => {
    const response = await api.get(`/api/academies/${academyId}/store/stats`)
    return response
  }

  const uploadStoreLogo = (academyId: number, formData: FormData) =>
    api.post(`/api/academies/${academyId}/store/logo`, formData)

  const uploadStoreBanner = (academyId: number, formData: FormData) =>
    api.post(`/api/academies/${academyId}/store/banner`, formData)

  // ============================================================
  // CATEGORIES
  // ============================================================

  const getCategories = async (academyId: number) => {
    const response = await api.get(`/api/academies/${academyId}/store/categories`)
    return extractData(response)
  }

  const createCategory = (academyId: number, data: Record<string, any>) =>
    api.post(`/api/academies/${academyId}/store/categories`, data)

  const updateCategory = (academyId: number, categoryId: number, data: Record<string, any>) =>
    api.patch(`/api/academies/${academyId}/store/categories/${categoryId}`, data)

  const deleteCategory = (academyId: number, categoryId: number) =>
    api.call(`/api/academies/${academyId}/store/categories/${categoryId}`, { method: 'DELETE' })

  // ============================================================
  // PRODUCTS
  // ============================================================

  const getProducts = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/store/products`, { params })
    return response
  }

  const getProduct = async (academyId: number, productId: number) => {
    const response = await api.get(`/api/academies/${academyId}/store/products/${productId}`)
    return response
  }

  const createProduct = (academyId: number, data: FormData | Record<string, any>) =>
    api.post(`/api/academies/${academyId}/store/products`, data)

  const updateProduct = (academyId: number, productId: number, data: Record<string, any>) =>
    api.patch(`/api/academies/${academyId}/store/products/${productId}`, data)

  const deleteProduct = (academyId: number, productId: number) =>
    api.call(`/api/academies/${academyId}/store/products/${productId}`, { method: 'DELETE' })

  const uploadProductImages = (academyId: number, productId: number, formData: FormData) =>
    api.post(`/api/academies/${academyId}/store/products/${productId}/images`, formData)

  const deleteProductImage = (academyId: number, productId: number, imageIndex: number) =>
    api.call(`/api/academies/${academyId}/store/products/${productId}/images`, {
      method: 'DELETE',
      body: { image_index: imageIndex },
    })

  // ============================================================
  // REVIEWS
  // ============================================================

  const getProductReviews = async (academyId: number, productId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/store/products/${productId}/reviews`, { params })
    return response
  }

  const createReview = (academyId: number, productId: number, data: Record<string, any>) =>
    api.post(`/api/academies/${academyId}/store/products/${productId}/reviews`, data)

  const deleteReview = (academyId: number, productId: number, reviewId: number) =>
    api.call(`/api/academies/${academyId}/store/products/${productId}/reviews/${reviewId}`, { method: 'DELETE' })

  // ============================================================
  // ORDERS
  // ============================================================

  const getOrders = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/store/orders`, { params })
    return response
  }

  const getMyOrders = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/store/orders/my`, { params })
    return response
  }

  const getOrder = async (academyId: number, orderId: number) => {
    const response = await api.get(`/api/academies/${academyId}/store/orders/${orderId}`)
    return response
  }

  const createOrder = (academyId: number, data: Record<string, any>) =>
    api.post(`/api/academies/${academyId}/store/orders`, data)

  const confirmOrder = (academyId: number, orderId: number) =>
    api.post(`/api/academies/${academyId}/store/orders/${orderId}/confirm`, {})

  const processOrder = (academyId: number, orderId: number) =>
    api.post(`/api/academies/${academyId}/store/orders/${orderId}/process`, {})

  const markOrderReady = (academyId: number, orderId: number) =>
    api.post(`/api/academies/${academyId}/store/orders/${orderId}/ready`, {})

  const completeOrder = (academyId: number, orderId: number) =>
    api.post(`/api/academies/${academyId}/store/orders/${orderId}/complete`, {})

  const cancelOrder = (academyId: number, orderId: number, reason: string) =>
    api.post(`/api/academies/${academyId}/store/orders/${orderId}/cancel`, { reason })

  // ============================================================
  // REPORTS
  // ============================================================

  const getSalesReport = async (academyId: number, params?: Record<string, any>) => {
    const response = await api.get(`/api/academies/${academyId}/store/reports/sales`, { params })
    return response
  }

  return {
    // Store
    getStore,
    createStore,
    updateStore,
    getStoreStats,
    uploadStoreLogo,
    uploadStoreBanner,
    // Categories
    getCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    // Products
    getProducts,
    getProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    uploadProductImages,
    deleteProductImage,
    // Reviews
    getProductReviews,
    createReview,
    deleteReview,
    // Orders
    getOrders,
    getMyOrders,
    getOrder,
    createOrder,
    confirmOrder,
    processOrder,
    markOrderReady,
    completeOrder,
    cancelOrder,
    // Reports
    getSalesReport,
  }
}
