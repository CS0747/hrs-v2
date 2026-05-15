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
