# DIOS Module Access Enforcement Bugfix Design

## Overview

The DIOS System Control module provides a "Module Access" tab where DIOS users can configure role-based permissions for different modules and actions. These permissions are successfully saved to the `module_permissions` table, but they are not being enforced when users attempt to access modules and perform actions. This design outlines the implementation of a centralized permission checking system that will enforce these permissions across all API endpoints.

The fix will introduce a reusable permission checking function in `db.php` that all API endpoints can call before processing requests. This ensures that permissions are enforced consistently, immediately (without session restart), and with minimal code duplication.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when a user attempts to perform an action on a module and the permission check is NOT performed, allowing unauthorized access
- **Property (P)**: The desired behavior - when a user attempts an action, the system SHALL check `module_permissions` table and deny access if permission is not granted
- **Preservation**: Existing functionality that must remain unchanged - DIOS role retains unrestricted access, permission UI continues to work, database operations remain functional
- **module_permissions table**: Database table storing permissions with columns: `module`, `role`, `action`, `granted` (TINYINT 0/1)
- **checkPermission()**: New function to be added to `db.php` that queries the permissions table and returns true/false
- **Module**: The feature area being accessed (e.g., "Employee Masterlist", "Leave Management", "Account Management")
- **Action**: The operation being performed (e.g., "View", "Add", "Edit", "Delete", "Export", "Approve")
- **Role**: The user's role from the `users` table (e.g., "DIOS", "Super Admin", "Admin", "Section Admin")

## Bug Details

### Bug Condition

The bug manifests when any user with any role attempts to perform any action on any module. The API endpoints (e.g., `employees.php`, `leave.php`, `auth.php`) process the request without checking the `module_permissions` table, allowing actions to proceed regardless of configured permissions.

**Formal Specification:**
```
FUNCTION isBugCondition(request)
  INPUT: request of type HTTPRequest with user role, target module, and action
  OUTPUT: boolean
  
  RETURN request.method IN ['GET', 'POST', 'PUT', 'DELETE']
         AND request.user.role IN ['Super Admin', 'Admin', 'Section Admin']
         AND modulePermissionExists(request.module, request.user.role, request.action)
         AND NOT permissionCheckPerformed(request)
END FUNCTION
```

### Examples

- **Example 1**: User with role "Admin" sends DELETE request to `/employees.php?id=5`. The Module Access tab shows "Delete" is denied for Admin role on "Employee Masterlist". Expected: 403 Forbidden. Actual: Employee record is deleted.

- **Example 2**: User with role "Section Admin" sends GET request to `/auth.php?action=users`. The Module Access tab shows "View" is denied for Section Admin role on "Account Management". Expected: 403 Forbidden. Actual: User list is returned.

- **Example 3**: User with role "Admin" sends POST request to `/leave.php` to create a leave record. The Module Access tab shows "Add" is denied for Admin role on "Leave Management". Expected: 403 Forbidden. Actual: Leave record is created.

- **Edge Case**: User with role "DIOS" sends DELETE request to `/employees.php?id=5`. Expected: Request succeeds regardless of permissions (DIOS has unrestricted access). Actual: Request succeeds (this behavior is correct and must be preserved).

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- DIOS role must continue to have unrestricted access to all modules and actions
- The Module Access UI tab must continue to display and save permissions correctly
- Database operations (INSERT, UPDATE, DELETE, SELECT) must continue to work as before for authorized requests
- Existing audit logging functionality must remain unchanged
- API response formats must remain unchanged for successful requests

**Scope:**
All inputs that do NOT involve permission-restricted roles (i.e., DIOS role) should be completely unaffected by this fix. This includes:
- DIOS user actions (all modules, all actions)
- Database schema and table structure
- Frontend permission configuration UI
- Audit log entries
- Response JSON structure for successful operations

## Hypothesized Root Cause

Based on the bug description and code analysis, the root cause is clear:

1. **Missing Permission Check Logic**: None of the API endpoints (`employees.php`, `leave.php`, `auth.php`, `dtr.php`, etc.) contain any code that queries the `module_permissions` table before processing requests.

2. **No Centralized Permission Function**: There is no reusable function in `db.php` or elsewhere that API endpoints can call to check permissions.

3. **No User Context in Requests**: The API endpoints do not receive or validate user role information from the frontend, making it impossible to perform permission checks even if the logic existed.

4. **Frontend Does Not Send Auth Context**: The Vue.js frontend stores user information in `auth.js` store but does not include user role or user ID in API requests.

## Correctness Properties

Property 1: Bug Condition - Permission Enforcement

_For any_ HTTP request where a user with a non-DIOS role attempts to perform an action on a module, and that action is denied in the `module_permissions` table (granted = 0), the API endpoint SHALL reject the request with a 403 Forbidden response and an appropriate error message, preventing the action from being executed.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

Property 2: Preservation - DIOS Unrestricted Access

_For any_ HTTP request where a user with role "DIOS" attempts to perform any action on any module, the API endpoint SHALL allow the request to proceed without checking the `module_permissions` table, preserving unrestricted access for DIOS users.

**Validates: Requirements 3.1, 3.4**

Property 3: Preservation - Permission UI Functionality

_For any_ interaction with the Module Access tab in the DIOS System Control interface, the system SHALL continue to display, edit, and save permissions to the `module_permissions` table exactly as before, with no changes to UI behavior or database storage.

**Validates: Requirements 3.2, 3.3, 3.5**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File 1**: `server/api/db.php`

**Function**: Add new `checkPermission()` function

**Specific Changes**:
1. **Add Permission Checking Function**: Create a new function `checkPermission($conn, $userId, $module, $action)` that:
   - Accepts database connection, user ID, module name, and action name
   - Queries the `users` table to get the user's role
   - Returns `true` immediately if role is "DIOS" (unrestricted access)
   - Queries the `module_permissions` table for the specific module/role/action combination
   - Returns `true` if `granted = 1`, `false` if `granted = 0`
   - Returns `true` by default if no permission record exists (fail-open for backward compatibility)

2. **Add Helper Function for User Role**: Create `getUserRole($conn, $userId)` to fetch role from `users` table

3. **Add Helper Function for Permission Denial Response**: Create `denyAccess($module, $action)` to send consistent 403 responses

**File 2**: `server/api/employees.php`

**Function**: All HTTP method handlers (GET, POST, PUT, DELETE)

**Specific Changes**:
1. **Extract User ID from Request**: Add logic to read `X-User-Id` header from request
2. **Map HTTP Methods to Actions**: 
   - GET → "View"
   - POST → "Add"
   - PUT → "Edit"
   - DELETE → "Delete"
3. **Call Permission Check**: Before processing each request, call `checkPermission($conn, $userId, 'Employee Masterlist', $action)`
4. **Deny if Unauthorized**: If `checkPermission()` returns `false`, call `denyAccess()` and exit

**File 3**: `server/api/leave.php`

**Function**: All HTTP method handlers (GET, POST, PUT, DELETE)

**Specific Changes**: Same pattern as `employees.php`, using module name "Leave Management"

**File 4**: `server/api/auth.php`

**Function**: `users` action handler (GET)

**Specific Changes**: Add permission check for "Account Management" module, "View" action before returning user list

**File 5**: `client/src/stores/auth.js`

**Function**: Store state and API request interceptor

**Specific Changes**:
1. **Add Request Interceptor**: Configure axios or fetch to include `X-User-Id` header in all API requests
2. **Use Existing User ID**: Read `userId` from auth store state and include in header

**File 6**: All other API endpoints (`dtr.php`, `travel_orders.php`, `tracking.php`, `trainings.php`, `signatories.php`, `payroll.php`, `schedule.php`, `departments.php`, `birthday_celebrants.php`, `ai_scan.php`, `audit_logs.php`)

**Specific Changes**: Apply the same permission checking pattern as `employees.php`, using appropriate module names and action mappings

### Module Name Mapping

The following mapping will be used to translate API endpoints to module names in the `module_permissions` table:

| API Endpoint | Module Name |
|--------------|-------------|
| `employees.php` | Employee Masterlist |
| `leave.php` | Leave Management |
| `travel_orders.php` | Travel Orders |
| `dtr.php` | DTR Transmittal |
| `tracking.php` | Tracking / Receiving |
| `trainings.php` | Trainings |
| `signatories.php` | Signatories |
| `auth.php?action=users` | Account Management |
| `audit_logs.php` | Audit History |
| `departments.php` | Departments |
| `birthday_celebrants.php` | Birthday Celebrants |
| `ai_scan.php` | AI Scanning Tools |
| `schedule.php` | Schedule Database |
| `payroll.php` | (Not in module list, skip for now) |

### Action Mapping

| HTTP Method | Action Name |
|-------------|-------------|
| GET (single record) | View |
| GET (list) | View |
| POST | Add |
| PUT | Edit |
| DELETE | Delete |
| GET with `?export=1` | Export |
| PUT with `status=approved` | Approve |
| PUT with `verified=1` | Verify |

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code, then verify the fix works correctly and preserves existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm or refute the root cause analysis. If we refute, we will need to re-hypothesize.

**Test Plan**: Write tests that configure permissions to deny specific actions for specific roles, then send API requests as those roles. Run these tests on the UNFIXED code to observe that requests succeed when they should fail.

**Test Cases**:
1. **Admin Delete Denial Test**: Set "Delete" to denied for Admin role on "Employee Masterlist". Send DELETE request to `/employees.php?id=1` with Admin user ID. (will succeed on unfixed code, should fail after fix)
2. **Section Admin View Denial Test**: Set "View" to denied for Section Admin role on "Account Management". Send GET request to `/auth.php?action=users` with Section Admin user ID. (will succeed on unfixed code, should fail after fix)
3. **Admin Add Denial Test**: Set "Add" to denied for Admin role on "Leave Management". Send POST request to `/leave.php` with Admin user ID. (will succeed on unfixed code, should fail after fix)
4. **DIOS Unrestricted Test**: Set "Delete" to denied for DIOS role on "Employee Masterlist". Send DELETE request to `/employees.php?id=1` with DIOS user ID. (should succeed on both unfixed and fixed code)

**Expected Counterexamples**:
- Requests succeed even when permissions are denied in the database
- Possible causes: no permission check logic, no user context in requests, no centralized permission function

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed function produces the expected behavior.

**Pseudocode:**
```
FOR ALL request WHERE isBugCondition(request) DO
  response := processRequest_fixed(request)
  ASSERT response.status = 403
  ASSERT response.body.error CONTAINS "permission denied"
END FOR
```

**Test Plan**: After implementing the fix, run the same test cases from exploratory checking and verify that denied actions now return 403 responses.

**Test Cases**:
1. **Admin Delete Denial**: Verify DELETE request returns 403 when permission is denied
2. **Section Admin View Denial**: Verify GET request returns 403 when permission is denied
3. **Admin Add Denial**: Verify POST request returns 403 when permission is denied
4. **Super Admin Granted Access**: Verify requests succeed when permissions are granted
5. **Immediate Application**: Change permission from granted to denied, verify next request is denied without session restart

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed function produces the same result as the original function.

**Pseudocode:**
```
FOR ALL request WHERE NOT isBugCondition(request) DO
  ASSERT processRequest_original(request) = processRequest_fixed(request)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs

**Test Plan**: Observe behavior on UNFIXED code first for DIOS users and granted permissions, then write property-based tests capturing that behavior.

**Test Cases**:
1. **DIOS Unrestricted Access**: Observe that DIOS users can perform all actions on unfixed code, then verify this continues after fix (all HTTP methods, all modules)
2. **Granted Permission Access**: Observe that users with granted permissions can perform actions on unfixed code, then verify this continues after fix
3. **Permission UI Functionality**: Observe that Module Access tab loads, displays, and saves permissions on unfixed code, then verify this continues after fix
4. **Database Operations**: Observe that successful API requests create/update/delete records on unfixed code, then verify this continues after fix
5. **Audit Logging**: Observe that audit logs are created for actions on unfixed code, then verify this continues after fix

### Unit Tests

- Test `checkPermission()` function with various combinations of user roles, modules, and actions
- Test permission check returns `true` for DIOS role regardless of database settings
- Test permission check returns `true` when `granted = 1` in database
- Test permission check returns `false` when `granted = 0` in database
- Test permission check returns `true` when no permission record exists (fail-open)
- Test `getUserRole()` function returns correct role for valid user ID
- Test `getUserRole()` function handles invalid user ID gracefully
- Test each API endpoint denies access when permission is denied
- Test each API endpoint allows access when permission is granted

### Property-Based Tests

- Generate random user roles (excluding DIOS) and verify that denied permissions always result in 403 responses
- Generate random module/action combinations and verify that granted permissions always allow access
- Generate random DIOS user requests and verify they always succeed regardless of permission settings
- Generate random permission configuration changes and verify they apply immediately without session restart

### Integration Tests

- Test full workflow: DIOS user configures permissions → Non-DIOS user attempts action → Access denied
- Test full workflow: DIOS user configures permissions → Non-DIOS user with granted permission attempts action → Access allowed
- Test permission changes apply immediately: User performs action successfully → DIOS revokes permission → User attempts same action → Access denied
- Test cross-module permissions: User has access to Module A but not Module B → Verify access patterns are correct
- Test role-based access: Same action on same module with different roles → Verify each role's permissions are enforced correctly
