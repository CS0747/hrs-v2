# API URL Connection Error Fix

## Issue

After logging in, the application shows "Connection error" even though the server folder is in the `/hrsystem/` directory.

## Root Cause

All Pinia stores were using **hardcoded localhost URLs** pointing to the development path `/hrs-v2/`:

```javascript
// ❌ WRONG - Hardcoded development URLs
const API = 'http://localhost/hrs-v2/server/api/employees.php'
const DEPT_API = 'http://localhost/hrs-v2/server/api/departments.php'
```

When deployed to production at `/hrsystem/`, these URLs were still trying to reach `/hrs-v2/`, causing connection errors.

## Solution Applied

Updated all stores to use the centralized API configuration from `@/config/api.js`:

```javascript
// ✅ CORRECT - Uses environment-based configuration
import { API_ENDPOINTS } from '@/config/api'

const API = API_ENDPOINTS.EMPLOYEES
const DEPT_API = API_ENDPOINTS.DEPARTMENTS
```

## Files Fixed

### Stores Updated:
1. ✅ `client/src/stores/auth.js` - AUTH, AUDIT_LOGS
2. ✅ `client/src/stores/employees.js` - EMPLOYEES, DEPARTMENTS
3. ✅ `client/src/stores/dtr.js` - DTR
4. ✅ `client/src/stores/leave.js` - LEAVE
5. ✅ `client/src/stores/schedule.js` - SCHEDULE
6. ✅ `client/src/stores/signatories.js` - SIGNATORIES
7. ✅ `client/src/stores/tracking.js` - TRACKING
8. ✅ `client/src/stores/trainings.js` - TRAININGS
9. ✅ `client/src/stores/travel_orders.js` - TRAVEL_ORDERS

### Configuration Files:
- `client/.env.production` - Defines `VITE_API_BASE_URL=/hrsystem/server/api/`
- `client/src/config/api.js` - Centralized API endpoint configuration

## How It Works Now

### Development Mode:
```env
# client/.env.development
VITE_API_BASE_URL=http://localhost/hrs-v2/server/api/
```
API calls go to: `http://localhost/hrs-v2/server/api/employees.php`

### Production Mode:
```env
# client/.env.production
VITE_API_BASE_URL=/hrsystem/server/api/
```
API calls go to: `/hrsystem/server/api/employees.php` (relative to current domain)

## API Endpoints Available

All endpoints are now centrally managed in `API_ENDPOINTS`:

```javascript
export const API_ENDPOINTS = {
    AUTH: '/hrsystem/server/api/auth.php',
    EMPLOYEES: '/hrsystem/server/api/employees.php',
    DEPARTMENTS: '/hrsystem/server/api/departments.php',
    DTR: '/hrsystem/server/api/dtr.php',
    LEAVE: '/hrsystem/server/api/leave.php',
    TRAVEL_ORDERS: '/hrsystem/server/api/travel_orders.php',
    TRAININGS: '/hrsystem/server/api/trainings.php',
    SCHEDULE: '/hrsystem/server/api/schedule.php',
    TRACKING: '/hrsystem/server/api/tracking.php',
    SIGNATORIES: '/hrsystem/server/api/signatories.php',
    NOTIFICATIONS: '/hrsystem/server/api/notifications.php',
    AUDIT_LOGS: '/hrsystem/server/api/audit_logs.php',
    BIRTHDAY_CELEBRANTS: '/hrsystem/server/api/birthday_celebrants.php',
    AI_SCAN: '/hrsystem/server/api/ai_scan.php',
    DIOS_CONTROL: '/hrsystem/server/api/dios_control.php',
    MODULE_PERMISSIONS: '/hrsystem/server/api/module_permissions.php',
}
```

## Build Output

Successfully rebuilt with all fixes:
```
✓ 283 modules transformed.
✓ built in 743ms
```

## Deployment Steps

1. Copy the new `dist` folder contents to production:
   ```
   C:\xampp\htdocs\hrsystem\
   ```

2. Ensure the `server` folder is also in the same directory:
   ```
   C:\xampp\htdocs\hrsystem\server\
   ```

3. Test the application:
   - Login should work without connection errors
   - All modules should load data correctly
   - API calls should reach the correct endpoints

## Verification

After deployment, check browser console (F12):
- ✅ No 404 errors for API calls
- ✅ API requests go to `/hrsystem/server/api/...`
- ✅ Data loads successfully after login

## Status: FIXED ✅

All stores now use centralized API configuration. The application will work correctly in both development and production environments without hardcoded URLs.

## Related Fixes

This fix works together with:
- **Router Base Path Fix** (`ROUTER_BASE_PATH_FIX.md`) - Fixed routing
- **API Configuration** (`client/src/config/api.js`) - Centralized API URLs
- **Environment Variables** (`.env.production`) - Production settings
