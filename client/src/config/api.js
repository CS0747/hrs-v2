/**
 * API Configuration
 * Centralized API base URL configuration for development and production
 */

const defaultBaseUrl = import.meta.env.DEV ? 'http://localhost/hrs-v2/server/api/' : '/hrsystem/server/api/'
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || defaultBaseUrl

// Ensure the URL ends with a slash
const normalizedBaseUrl = API_BASE_URL.endsWith('/') ? API_BASE_URL : `${API_BASE_URL}/`

/**
 * Get the full API URL for an endpoint
 * @param {string} endpoint - The API endpoint (e.g., 'auth.php', 'employees.php')
 * @returns {string} - The full API URL
 */
export function getApiUrl(endpoint) {
    // Remove leading slash from endpoint if present
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint
    return `${normalizedBaseUrl}${cleanEndpoint}`
}

/**
 * API endpoints
 */
export const API_ENDPOINTS = {
    AUTH: getApiUrl('auth.php'),
    EMPLOYEES: getApiUrl('employees.php'),
    DEPARTMENTS: getApiUrl('departments.php'),
    DTR: getApiUrl('dtr.php'),
    LEAVE: getApiUrl('leave.php'),
    TRAVEL_ORDERS: getApiUrl('travel_orders.php'),
    TRAININGS: getApiUrl('trainings.php'),
    SCHEDULE: getApiUrl('schedule.php'),
    TRACKING: getApiUrl('tracking.php'),
    SIGNATORIES: getApiUrl('signatories.php'),
    NOTIFICATIONS: getApiUrl('notifications.php'),
    AUDIT_LOGS: getApiUrl('audit_logs.php'),
    BIRTHDAY_CELEBRANTS: getApiUrl('birthday_celebrants.php'),
    AI_SCAN: getApiUrl('ai_scan.php'),
    AI_SCAN_DESIGNATE: getApiUrl('ai_scan_designate.php'),
    DIOS_CONTROL: getApiUrl('dios_control.php'),
    MODULE_PERMISSIONS: getApiUrl('module_permissions.php'),
    PAYROLL: getApiUrl('payroll.php'),
}

export default {
    getApiUrl,
    API_ENDPOINTS,
    BASE_URL: normalizedBaseUrl
}
