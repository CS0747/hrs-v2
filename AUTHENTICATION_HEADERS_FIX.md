# Authentication Headers Fix

## Problem
After implementing auto-refresh, data was still not fetching. The employee dropdown showed "No employees found" even though employees exist in the database.

## Root Cause
The backend APIs require the `X-User-ID` header for permission checking, but the stores were not sending this header with their fetch requests. This caused all API calls to fail with 403 Forbidden errors.

## Solution Implemented

### 1. Employee Store (`client/src/stores/employees.js`)

Added authentication header to all API calls:

**Functions Updated:**
- `fetchEmployees()` - GET request to fetch all employees
- `fetchDepartments()` - GET request to fetch all departments
- `addEmployee()` - POST request to create employee
- `updateEmployee()` - PUT request to update employee
- `deleteEmployee()` - DELETE request to remove employee

**Implementation Pattern:**
```javascript
// Get user ID from session storage for authentication
const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
const headers = {
  'Content-Type': 'application/json'
}
if (user?.id) {
  headers['X-User-ID'] = String(user.id)
}

const res = await fetch(API, { headers })
```

### 2. Schedule Store (`client/src/stores/schedule.js`)

Added authentication header to all API calls:

**Functions Updated:**
- `fetchSchedules()` - GET request to fetch all schedules
- `addSchedule()` - POST request to create schedule
- `updateSchedule()` - PUT request to update schedule
- `deleteSchedule()` - DELETE request to remove schedule
- `getSchedulesByDepartment()` - GET request with department filter
- `getSchedulesByEmployee()` - GET request with employee filter

**Implementation Pattern:**
```javascript
// Get user ID from session storage for authentication
const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
const headers = {}
if (user?.id) {
  headers['X-User-ID'] = String(user.id)
}

const res = await fetch(API, { headers })
```

### 3. API Utility (`client/src/utils/api.js`)

**Already Implemented** - The api utility already includes automatic X-User-ID header injection:

```javascript
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
    // ... rest of implementation
}
```

### 4. Legend Store (`client/src/stores/legend.js`)

**No Changes Needed** - Already uses the `api` utility which includes authentication headers automatically.

## Backend Permission Checking

All backend APIs check permissions using the X-User-ID header:

```php
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

// Map HTTP methods to actions
$actionMap = [
    'GET'    => 'View',
    'POST'   => 'Add',
    'PUT'    => 'Edit',
    'DELETE' => 'Delete',
];
$action = $actionMap[$method] ?? 'View';

// Check permission before processing request
if (!checkPermission($conn, $userId, 'Employee Masterlist', $action)) {
    denyAccess('Employee Masterlist', $action);
}
```

## Benefits

1. **Proper Authentication**: All API calls now include user identification
2. **Permission Enforcement**: Backend can properly check user permissions
3. **Security**: Prevents unauthorized access to data
4. **Consistent Pattern**: All stores use the same authentication approach
5. **Error Prevention**: Eliminates 403 Forbidden errors

## Testing Checklist

- [ ] Employee dropdown shows employees after login
- [ ] Department dropdown shows departments
- [ ] Schedule operations work (add/edit/delete)
- [ ] Employee operations work (add/edit/delete)
- [ ] Department operations work (add/edit/delete)
- [ ] No 403 Forbidden errors in console
- [ ] Data refreshes automatically after operations
- [ ] Different user roles see appropriate data

## Files Modified

1. `client/src/stores/employees.js` - Added X-User-ID header to all fetch calls
2. `client/src/stores/schedule.js` - Added X-User-ID header to all fetch calls

## Files Already Correct

1. `client/src/utils/api.js` - Already includes automatic header injection
2. `client/src/stores/legend.js` - Uses api utility, no changes needed
3. `client/src/stores/auth.js` - Uses api utility or has custom implementation

## Notes

- The X-User-ID header is read from `sessionStorage.getItem('hris_user')`
- Header is only added if user is logged in (user.id exists)
- Header value is converted to string for consistency
- All stores now follow the same authentication pattern
- The api utility provides a centralized way to handle authentication for future API calls
