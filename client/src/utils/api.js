import { getApiUrl } from '@/config/api'

// API utility with automatic X-User-Id header injection
export function createAuthFetch() {
    const originalFetch = window.fetch

    window.fetch = function (url, options = {}) {
        // Only add header for API requests to our backend
        if (typeof url === 'string' && url.includes('/server/api/')) {
            // Get user from session storage
            const userStr = sessionStorage.getItem('hris_user')
            if (userStr) {
                try {
                    const user = JSON.parse(userStr)
                    if (user && user.id) {
                        options.headers = options.headers || {}
                        if (options.headers instanceof Headers) {
                            options.headers.set('X-User-Id', String(user.id))
                        } else {
                            options.headers['X-User-Id'] = String(user.id)
                        }
                    }
                } catch (e) {
                    console.warn('Failed to parse user from session:', e)
                }
            }
        }

        return originalFetch.call(this, url, options)
    }
}

// API client for making requests
function getUserId() {
    const userStr = sessionStorage.getItem('hris_user')
    if (userStr) {
        try {
            const user = JSON.parse(userStr)
            return user?.id || null
        } catch (e) {
            return null
        }
    }
    return null
}

async function request(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : getApiUrl(endpoint)

    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    }

    const userId = getUserId()
    if (userId) {
        headers['X-User-ID'] = String(userId)
    }

    const config = {
        ...options,
        headers
    }

    const response = await fetch(url, config)

    if (!response.ok) {
        const error = await response.json().catch(() => ({ error: 'Request failed' }))
        throw new Error(error.error || `HTTP ${response.status}`)
    }

    return response.json()
}

const api = {
    get: (endpoint, options = {}) => request(endpoint, { ...options, method: 'GET' }),
    post: (endpoint, data, options = {}) => request(endpoint, { ...options, method: 'POST', body: JSON.stringify(data) }),
    put: (endpoint, data, options = {}) => request(endpoint, { ...options, method: 'PUT', body: JSON.stringify(data) }),
    delete: (endpoint, options = {}) => request(endpoint, { ...options, method: 'DELETE' })
}

export default api
