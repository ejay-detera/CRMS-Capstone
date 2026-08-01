/**
 * Helper to build clean API headers without sending literal "Bearer null" or "Bearer undefined".
 */
export function getAuthHeaders(token?: string | null, customHeaders: Record<string, string> = {}): Record<string, string> {
  const headers: Record<string, string> = {
    'Accept': 'application/json',
    ...customHeaders,
  }

  const sessionId = localStorage.getItem('session_id')
  if (sessionId && !headers['X-Session-ID']) {
    headers['X-Session-ID'] = sessionId
  }

  // Only attach Authorization header if token is non-empty and valid
  if (token && token !== 'null' && token !== 'undefined' && token.trim() !== '') {
    headers['Authorization'] = `Bearer ${token}`
  }

  return headers
}

/**
 * Returns standard fetch options ensuring credentials are included for same-origin cookie auth.
 */
export function getFetchOptions(token?: string | null, options: RequestInit = {}): RequestInit {
  const customHeaders = (options.headers as Record<string, string>) || {}
  return {
    ...options,
    credentials: 'same-origin',
    headers: getAuthHeaders(token, customHeaders),
  }
}
