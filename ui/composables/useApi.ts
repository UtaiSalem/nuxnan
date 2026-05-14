/**
 * Custom composable for authenticated API calls
 * Automatically includes JWT token in headers
 * Features: retry logic with exponential backoff, standardized error reporting, request timeouts
 */

// ============================================================================
// TypeScript Interfaces and Types
// ============================================================================

/**
 * Configuration for retry logic
 */
export interface RetryConfig {
  /** Maximum number of retry attempts */
  maxRetries: number;
  /** Initial delay in milliseconds before first retry */
  initialDelay: number;
  /** Maximum delay in milliseconds between retries */
  maxDelay: number;
  /** HTTP status codes that should trigger a retry */
  retryStatusCodes: number[];
  /** HTTP methods that are safe to retry (idempotent) */
  retryableMethods: string[];
  /** Whether to retry on network errors */
  retryOnNetworkError: boolean;
}

/**
 * Configuration for API call options
 */
export interface ApiCallOptions {
  /** HTTP method (GET, POST, PUT, PATCH, DELETE, etc.) */
  method?: string;
  /** Request body */
  body?: any;
  /** Request headers */
  headers?: Record<string, string>;
  /** Request timeout in milliseconds */
  timeout?: number;
  /** Retry configuration */
  retryConfig?: Partial<RetryConfig>;
  /** Enable debug logging */
  debug?: boolean;
  /** Additional options to pass to $fetch */
  [key: string]: any;
}

/**
 * Type of API error
 */
export type ApiErrorType =
  | 'networkError'      // Network connectivity issues
  | 'serverError'       // 5xx server errors
  | 'clientError'       // 4xx client errors
  | 'timeoutError'      // Request timeout
  | 'authError'         // Authentication/authorization errors
  | 'validationError'   // 422 validation errors
  | 'rateLimitError'    // 429 rate limiting
  | 'notFoundError'     // 404 not found
  | 'forbiddenError'    // 403 forbidden
  | 'unknownError';     // Other errors

/**
 * Standardized API error object
 */
export interface ApiError extends Error {
  /** Unique error ID for tracking */
  id: string;
  /** API endpoint (without base URL) */
  endpoint: string;
  /** Full URL that was requested */
  url: string;
  /** HTTP method used */
  method: string;
  /** HTTP status code (if available) */
  status?: number;
  /** Response data (if available) */
  data?: any;
  /** Request headers (sanitized) */
  requestHeaders?: Record<string, string>;
  /** Request body (sanitized, if available) */
  requestBody?: any;
  /** Error type classification */
  type: ApiErrorType;
  /** Timestamp when the error occurred */
  timestamp: string;
  /** Number of retry attempts made */
  retryCount: number;
  /** Whether this error is retryable */
  isRetryable: boolean;
  /** Original error object */
  originalError?: any;
}

/**
 * Response interceptor result
 */
export interface InterceptorResult<T = any> {
  data: T;
  headers: Headers;
  status: number;
}

// ============================================================================
// Default Configurations
// ============================================================================

/**
 * Default retry configuration
 */
const defaultRetryConfig: RetryConfig = {
  maxRetries: 3,
  initialDelay: 1000,
  maxDelay: 10000,
  retryStatusCodes: [408, 429, 500, 502, 503, 504],
  retryableMethods: ['GET', 'HEAD', 'OPTIONS'],
  retryOnNetworkError: true,
};

/**
 * Default request timeout in milliseconds
 */
const DEFAULT_TIMEOUT = 30000;

// ============================================================================
// Utility Functions
// ============================================================================

/**
 * Generate a unique error ID
 */
function generateErrorId(): string {
  return `api_err_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Sanitize headers by removing sensitive information
 */
function sanitizeHeaders(headers: Record<string, string>): Record<string, string> {
  const sanitized = { ...headers };
  const sensitiveKeys = ['authorization', 'cookie', 'set-cookie', 'x-api-key'];
  
  for (const key of Object.keys(sanitized)) {
    if (sensitiveKeys.includes(key.toLowerCase())) {
      sanitized[key] = '[REDACTED]';
    }
  }
  
  return sanitized;
}

/**
 * Sanitize request body by removing sensitive fields
 */
function sanitizeBody(body: any): any {
  if (!body) return undefined;
  
  const sensitiveFields = ['password', 'current_password', 'new_password', 'token', 'secret'];
  
  if (body instanceof FormData) {
    // FormData cannot be easily sanitized, return placeholder
    return '[FormData]';
  }
  
  if (typeof body === 'object') {
    const sanitized = { ...body };
    for (const field of sensitiveFields) {
      if (field in sanitized) {
        sanitized[field] = '[REDACTED]';
      }
    }
    return sanitized;
  }
  
  return body;
}

/**
 * Calculate delay with exponential backoff and jitter
 */
function calculateRetryDelay(
  attempt: number,
  initialDelay: number,
  maxDelay: number
): number {
  // Exponential backoff: delay = initialDelay * 2^(attempt - 1)
  const exponentialDelay = initialDelay * Math.pow(2, attempt - 1);
  
  // Add jitter (±25% random variation) to prevent thundering herd
  const jitter = exponentialDelay * 0.25 * (Math.random() * 2 - 1);
  
  // Cap at maxDelay
  return Math.min(Math.max(exponentialDelay + jitter, 0), maxDelay);
}

/**
 * Classify error based on status code and other factors
 */
function classifyError(
  statusCode: number | undefined,
  errorMessage: string,
  isTimeout: boolean
): ApiErrorType {
  if (isTimeout) return 'timeoutError';
  
  if (!statusCode) {
    // Network error or timeout
    if (errorMessage.includes('fetch') || errorMessage.includes('network')) {
      return 'networkError';
    }
    return 'unknownError';
  }
  
  // Classify based on status code
  if (statusCode === 401 || statusCode === 407) return 'authError';
  if (statusCode === 403) return 'forbiddenError';
  if (statusCode === 404) return 'notFoundError';
  if (statusCode === 422) return 'validationError';
  if (statusCode === 429) return 'rateLimitError';
  if (statusCode >= 400 && statusCode < 500) return 'clientError';
  if (statusCode >= 500) return 'serverError';
  
  return 'unknownError';
}

/**
 * Determine if an error is retryable
 */
function isRetryableError(
  statusCode: number | undefined,
  errorType: ApiErrorType,
  retryConfig: RetryConfig
): boolean {
  // Check if status code is in retryable list
  if (statusCode && retryConfig.retryStatusCodes.includes(statusCode)) {
    return true;
  }
  
  // Network errors are retryable if configured
  if (errorType === 'networkError' && retryConfig.retryOnNetworkError) {
    return true;
  }
  
  return false;
}

/**
 * Create a standardized ApiError object
 */
function createApiError(
  endpoint: string,
  url: string,
  method: string,
  error: any,
  retryCount: number,
  requestHeaders?: Record<string, string>,
  requestBody?: any
): ApiError {
  const statusCode = error.statusCode || error.response?.status;
  const isTimeout = error.name === 'TimeoutError' || error.message?.includes('timeout');
  
  const errorType: ApiErrorType = classifyError(statusCode, error.message, isTimeout);
  
  const apiError: ApiError = {
    id: generateErrorId(),
    endpoint,
    url,
    method,
    status: statusCode,
    data: error.data || error.response?._data,
    requestHeaders: requestHeaders ? sanitizeHeaders(requestHeaders) : undefined,
    requestBody: requestBody ? sanitizeBody(requestBody) : undefined,
    message: error.message || 'An unknown error occurred',
    type: errorType,
    timestamp: new Date().toISOString(),
    retryCount,
    isRetryable: false, // Will be set by caller
    originalError: error,
    name: 'ApiError',
  };
  
  // Set prototype for instanceof checks  
  Object.setPrototypeOf(apiError, Error.prototype);
  
  return apiError;
}

/**
 * Sleep/delay function for retry logic
 */
function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Create a timeout promise
 */
function createTimeoutPromise(timeoutMs: number, url: string): Promise<never> {
  return new Promise((_, reject) => {
    setTimeout(() => {
      reject(new Error(`Request timeout after ${timeoutMs}ms for ${url}`));
    }, timeoutMs);
  });
}

// ============================================================================
// Main Composable
// ============================================================================

export const useApi = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  /**
   * Request interceptor - called before each request
   * Can be used to modify headers, log requests, etc.
   */
  const requestInterceptor = async (
    url: string,
    options: ApiCallOptions
  ): Promise<{ url: string; options: ApiCallOptions }> => {
    // Add common headers
    const enhancedOptions: ApiCallOptions = {
      ...options,
      headers: {
        ...options.headers,
        'X-Request-ID': generateErrorId().replace('api_err_', 'req_'),
      },
    };
    
    return { url, options: enhancedOptions };
  };

  /**
   * Response interceptor - called after each response
   * Can be used to log responses, transform data, etc.
   */
  const responseInterceptor = async <T>(
    response: T,
    url: string,
    options: ApiCallOptions
  ): Promise<InterceptorResult<T>> => {
    return {
      data: response,
      headers: new Headers(),
      status: 200,
    };
  };

  /**
   * Make an authenticated API call with retry logic and improved error handling
   * 
   * @param endpoint - API endpoint (e.g., '/api/newsfeed')
   * @param options - API call options including method, body, timeout, retry config, etc.
   * @returns Promise with the response data
   * @throws ApiError - Standardized error object with detailed information
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * 
   * // Basic GET request
   * const data = await api.get('/api/users')
   * 
   * // POST with custom timeout
   * const result = await api.post('/api/users', { name: 'John' }, { timeout: 5000 })
   * 
   * // GET with custom retry config
   * const items = await api.get('/api/items', {
   *   retryConfig: {
   *     maxRetries: 5,
   *     initialDelay: 2000
   *   }
   * })
   * 
   * // Enable debug logging
   * const debugData = await api.get('/api/debug', { debug: true })
   * ```
   */
  const call = async <T = any>(
    endpoint: string,
    options: ApiCallOptions = {}
  ): Promise<T> => {
    const token = authStore.token
    const apiBase = config.public.apiBase as string

    if (!token) {
      throw createApiError(
        endpoint,
        endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`,
        options.method || 'GET',
        new Error('No authentication token available'),
        0
      );
    }

    // Build full URL
    const url = endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`

    // Merge retry config with defaults
    const retryConfig: RetryConfig = {
      ...defaultRetryConfig,
      ...options.retryConfig,
    };

    // Set default timeout
    const timeout = options.timeout ?? DEFAULT_TIMEOUT;

    // Check if body is FormData - don't set Content-Type for FormData
    const isFormData = options.body instanceof FormData;

    // Extract debug flag
    const debug = options.debug ?? false;

    // Apply request interceptor
    const interceptorResult = await requestInterceptor(url, options);
    const finalUrl = interceptorResult.url;
    const finalOptions = interceptorResult.options;

    // Determine HTTP method
    const method = (finalOptions.method || 'GET').toUpperCase();

    // Check if request is retryable based on method
    const isRetryableMethod = retryConfig.retryableMethods.includes(method);

     // Log request if debug mode is enabled
     if (debug) {
       // Dev-only logging
     }
     

    let lastError: ApiError | null = null;
    let retryCount = 0;

    // Retry loop
    while (retryCount <= retryConfig.maxRetries) {
      try {
        // Create timeout promise
        const timeoutPromise = createTimeoutPromise(timeout, finalUrl);

        // Prepare fetch options
        const fetchOptions = {
          ...finalOptions,
          headers: {
            ...finalOptions.headers,
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            // Don't set Content-Type for FormData - browser will set it with boundary
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
          },
          // Remove API-specific options before passing to $fetch
          timeout: undefined,
          retryConfig: undefined,
          debug: undefined,
        };

        // Make the request with timeout
        const response = await Promise.race([
          $fetch<T>(finalUrl, fetchOptions),
          timeoutPromise,
        ]);

        // Apply response interceptor
        const intercepted = await responseInterceptor(response, finalUrl, finalOptions);

         // Log response if debug mode is enabled
         if (debug) {
           // Dev-only logging
         }
         

        return intercepted.data;

      } catch (error: any) {
        // Create standardized error
        const apiError = createApiError(
          endpoint,
          finalUrl,
          method,
          error,
          retryCount,
          finalOptions.headers,
          finalOptions.body
        );

        // Determine if error is retryable
        const shouldRetry =
          isRetryableMethod &&
          retryCount < retryConfig.maxRetries &&
          isRetryableError(apiError.status, apiError.type, retryConfig);

        apiError.isRetryable = shouldRetry;
        lastError = apiError;

        // Handle 401 errors - try to refresh token (only on first attempt)
        if ((apiError.status === 401) && retryCount === 0) {
        if (debug || process.env.NODE_ENV === 'development') {
          // Dev-only logging
        }

          const refreshed = await authStore.refreshToken();

          if (refreshed) {
          if (debug || process.env.NODE_ENV === 'development') {
            // Dev-only logging
          }
            
            // Update token and retry immediately (don't count as retry)
            continue;
          } else {
            // Refresh failed, logout
            await authStore.logout();
            return undefined as any;
          }
        }

        // Check if we should retry
        if (shouldRetry) {
          retryCount++;
          const delay = calculateRetryDelay(
            retryCount,
            retryConfig.initialDelay,
            retryConfig.maxDelay
          );

          if (debug || process.env.NODE_ENV === 'development') {
            // Dev-only logging
          }

          // Wait before retrying
          await sleep(delay);
          continue;
        }

         // Log error only in debug mode or development
         if (debug || process.env.NODE_ENV === 'development') {
           // Dev-only logging
         }

        // Throw the error
        throw apiError;
      }
    }

    // This should never be reached, but TypeScript needs it
    throw lastError || new Error('Unknown error occurred');
  };

  /**
   * Make a GET request
   * 
   * @param endpoint - API endpoint
   * @param options - Additional options
   * @returns Promise with the response data
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * const users = await api.get('/api/users')
   * const user = await api.get('/api/users/1', { timeout: 5000 })
   * ```
   */
  const get = <T = any>(endpoint: string, options: ApiCallOptions = {}): Promise<T> => {
    return call<T>(endpoint, { ...options, method: 'GET' });
  };

  /**
   * Make a POST request
   * 
   * @param endpoint - API endpoint
   * @param body - Request body
   * @param options - Additional options
   * @returns Promise with the response data
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * const newUser = await api.post('/api/users', { name: 'John', email: 'john@example.com' })
   * ```
   */
  const post = <T = any>(
    endpoint: string,
    body: any,
    options: ApiCallOptions = {}
  ): Promise<T> => {
    return call<T>(endpoint, { ...options, method: 'POST', body });
  };

  /**
   * Make a PUT request
   * 
   * @param endpoint - API endpoint
   * @param body - Request body
   * @param options - Additional options
   * @returns Promise with the response data
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * const updatedUser = await api.put('/api/users/1', { name: 'John Updated' })
   * ```
   */
  const put = <T = any>(
    endpoint: string,
    body: any,
    options: ApiCallOptions = {}
  ): Promise<T> => {
    return call<T>(endpoint, { ...options, method: 'PUT', body });
  };

  /**
   * Make a PATCH request
   * 
   * @param endpoint - API endpoint
   * @param body - Request body
   * @param options - Additional options
   * @returns Promise with the response data
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * const partialUpdate = await api.patch('/api/users/1', { name: 'John' })
   * ```
   */
  const patch = <T = any>(
    endpoint: string,
    body: any,
    options: ApiCallOptions = {}
  ): Promise<T> => {
    return call<T>(endpoint, { ...options, method: 'PATCH', body });
  };

  /**
   * Make a DELETE request
   * 
   * @param endpoint - API endpoint
   * @param options - Additional options
   * @returns Promise with the response data
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * await api.delete('/api/users/1')
   * ```
   */
  const del = <T = any>(endpoint: string, options: ApiCallOptions = {}): Promise<T> => {
    return call<T>(endpoint, { ...options, method: 'DELETE' });
  };

  /**
   * Download a file as Blob (for PDF/Excel exports)
   * 
   * @param endpoint - API endpoint
   * @param options - Additional options
   * @returns Promise with blob and filename
   * 
   * @example
   * ```typescript
   * const api = useApi()
   * const { blob, filename } = await api.getBlob('/api/export/pdf')
   * ```
   */
  const getBlob = async (
    endpoint: string,
    options: ApiCallOptions = {}
  ): Promise<{ blob: Blob; filename: string }> => {
    const token = authStore.token;
    const apiBase = config.public.apiBase as string;

    if (!token) {
      throw createApiError(
        endpoint,
        endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`,
        'GET',
        new Error('No authentication token available'),
        0
      );
    }

    const url = endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`;
    const timeout = options.timeout ?? DEFAULT_TIMEOUT;
    const debug = options.debug ?? false;

    try {
      const timeoutPromise = createTimeoutPromise(timeout, url);
      
      const response = await Promise.race([
        fetch(url, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/octet-stream, application/pdf, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ...options.headers,
          },
        }),
        timeoutPromise,
      ]) as Response;

      if (!response.ok) {
        // Try to get error message from response
        const contentType = response.headers.get('content-type');
        let errorData: any;
        
        if (contentType && contentType.includes('application/json')) {
          errorData = await response.json();
        }
        
        const apiError = createApiError(
          endpoint,
          url,
          'GET',
          new Error(errorData?.message || `HTTP error! status: ${response.status}`),
          0,
          options.headers
        );
        apiError.status = response.status;
        apiError.data = errorData;
        
       if (debug || process.env.NODE_ENV === 'development') {
         // Dev-only logging
        }
        
        throw apiError;
      }

      const blob = await response.blob();
      
      // Get filename from Content-Disposition header if available
      const contentDisposition = response.headers.get('Content-Disposition');
      let filename = 'download';
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

       if (debug || process.env.NODE_ENV === 'development') {
         // Dev-only logging
      }

      return { blob, filename };
    } catch (error: any) {
      // Re-throw ApiError if already created
      if (error.id) {
        throw error;
      }
      
      const apiError = createApiError(
        endpoint,
        url,
        'GET',
        error,
        0,
        options.headers
      );
      
      if (debug || process.env.NODE_ENV === 'development') {
        console.error('[API Blob Error]', {
          id: apiError.id,
          endpoint: apiError.endpoint,
          url: apiError.url,
          message: apiError.message,
        });
      }
      
      throw apiError;
    }
  };

  return {
    call,
    get,
    post,
    put,
    patch,
    delete: del,
    getBlob,
  };
};

// ============================================================================
// Type Exports for External Use
// ============================================================================

export type { ApiCallOptions, RetryConfig, InterceptorResult };
