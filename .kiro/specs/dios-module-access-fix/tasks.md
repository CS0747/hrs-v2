# Implementation Plan

## Phase 1: Bug Condition Exploration (BEFORE Fix)

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - Permission Enforcement Failure
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: For deterministic bugs, scope the property to the concrete failing case(s) to ensure reproducibility
  - Create test file `server/tests/bug_condition_test.php` that:
    - Sets up test database with denied permissions (Admin role, "Delete" action, "Employee Masterlist" module, granted=0)
    - Simulates DELETE request to `/employees.php?id=1` with Admin user context (X-User-Id header)
    - Asserts response status is 403 Forbidden
    - Asserts response body contains "permission denied" or similar error message
  - Run test on UNFIXED code using `php server/tests/bug_condition_test.php`
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists)
  - Document counterexamples found:
    - Example 1: Admin user can DELETE employee despite denied permission
    - Example 2: Section Admin user can VIEW users list despite denied permission
    - Example 3: Admin user can ADD leave record despite denied permission
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

## Phase 2: Preservation Property Tests (BEFORE Fix)

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - DIOS Unrestricted Access and Granted Permissions
  - **IMPORTANT**: Follow observation-first methodology
  - Create test file `server/tests/preservation_test.php` that:
    - **Test 2.1**: DIOS Unrestricted Access
      - Observe: DIOS user can perform DELETE on employees on unfixed code
      - Observe: DIOS user can perform POST on leave records on unfixed code
      - Write property-based test: for all DIOS user requests, all actions succeed regardless of module_permissions table
      - Verify test passes on UNFIXED code
    - **Test 2.2**: Granted Permission Access
      - Observe: Admin user with granted "View" permission can GET employees list on unfixed code
      - Observe: Super Admin user with granted "Edit" permission can PUT employee record on unfixed code
      - Write property-based test: for all users with granted=1 permissions, actions succeed
      - Verify test passes on UNFIXED code
    - **Test 2.3**: Permission UI Functionality
      - Observe: Module Access tab loads permissions from database on unfixed code
      - Observe: Module Access tab saves permissions to database on unfixed code
      - Write test: GET /module_permissions.php returns correct data structure
      - Write test: POST /module_permissions.php saves permissions correctly
      - Verify tests pass on UNFIXED code
  - Run tests on UNFIXED code using `php server/tests/preservation_test.php`
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

## Phase 3: Core Permission Infrastructure

- [x] 3. Implement core permission checking infrastructure in db.php

  - [x] 3.1 Add getUserRole() helper function
    - Add function `getUserRole($conn, $userId)` to `server/api/db.php`
    - Query `users` table to fetch role for given user ID
    - Return role string (e.g., "DIOS", "Super Admin", "Admin", "Section Admin")
    - Return null if user ID is invalid or not found
    - Handle database errors gracefully
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 3.2 Add checkPermission() core function
    - Add function `checkPermission($conn, $userId, $module, $action)` to `server/api/db.php`
    - Call `getUserRole()` to get user's role
    - Return `true` immediately if role is "DIOS" (unrestricted access)
    - Query `module_permissions` table for specific module/role/action combination
    - Return `true` if `granted = 1`, `false` if `granted = 0`
    - Return `true` by default if no permission record exists (fail-open for backward compatibility)
    - Handle database errors gracefully (fail-open on error)
    - _Bug_Condition: isBugCondition(request) where request.method IN ['GET', 'POST', 'PUT', 'DELETE'] AND request.user.role IN ['Super Admin', 'Admin', 'Section Admin'] AND modulePermissionExists(request.module, request.user.role, request.action) AND NOT permissionCheckPerformed(request)_
    - _Expected_Behavior: For all requests where isBugCondition holds, checkPermission returns false and API returns 403_
    - _Preservation: DIOS role always returns true, granted permissions return true, no permission record returns true_
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 3.1, 3.4_

  - [x] 3.3 Add denyAccess() helper function
    - Add function `denyAccess($module, $action)` to `server/api/db.php`
    - Send 403 Forbidden HTTP response
    - Return JSON error message: `{"error": "Access denied: You do not have permission to perform [action] on [module]"}`
    - Call `exit()` to stop script execution
    - _Requirements: 2.2, 2.3_

## Phase 4: Frontend Auth Context Transmission

- [x] 4. Add user ID header to all API requests

  - [x] 4.1 Create axios interceptor in auth store
    - Modify `client/src/stores/auth.js`
    - Import axios or use fetch API with wrapper
    - Add request interceptor that includes `X-User-Id` header in all API requests
    - Read `currentUser.value.id` from auth store state
    - Include header: `X-User-Id: ${currentUser.value.id}`
    - Handle cases where user is not logged in (skip header)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 4.2 Test header transmission
    - Verify `X-User-Id` header is present in browser DevTools Network tab
    - Test with sample API request (e.g., GET /employees.php)
    - Confirm header value matches logged-in user ID
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

## Phase 5: API Endpoint Permission Enforcement

### 5.1 Employee Masterlist Module

- [x] 5.1.1 Add permission checks to employees.php
  - Modify `server/api/employees.php`
  - Extract `X-User-Id` header: `$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0)`
  - Map HTTP methods to actions:
    - GET (single record or list) → "View"
    - POST → "Add"
    - PUT → "Edit"
    - DELETE → "Delete"
  - Add permission check before each case block:
    ```php
    if (!checkPermission($conn, $userId, 'Employee Masterlist', $action)) {
        denyAccess('Employee Masterlist', $action);
    }
    ```
  - Test with denied permission: verify 403 response
  - Test with granted permission: verify request succeeds
  - Test with DIOS user: verify request succeeds regardless of permissions
  - _Bug_Condition: isBugCondition(request) where request targets Employee Masterlist_
  - _Expected_Behavior: Denied permissions return 403, granted permissions succeed_
  - _Preservation: DIOS access unchanged, granted permissions unchanged_
  - _Requirements: 2.1, 2.2, 2.4, 3.1_

### 5.2 Leave Management Module

- [x] 5.2.1 Add permission checks to leave.php
  - Modify `server/api/leave.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions (GET→View, POST→Add, PUT→Edit, DELETE→Delete)
  - Add permission check: `checkPermission($conn, $userId, 'Leave Management', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Leave Management_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4, 3.1_

### 5.3 Account Management Module

- [x] 5.3.1 Add permission checks to auth.php (users action)
  - Modify `server/api/auth.php`
  - Add permission check for `action=users` case
  - Extract `X-User-Id` header
  - Check permission: `checkPermission($conn, $userId, 'Account Management', 'View')`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Account Management_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.3, 2.4, 3.1_

### 5.4 Travel Orders Module

- [x] 5.4.1 Add permission checks to travel_orders.php
  - Modify `server/api/travel_orders.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Travel Orders', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Travel Orders_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.5 DTR Transmittal Module

- [x] 5.5.1 Add permission checks to dtr.php
  - Modify `server/api/dtr.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'DTR Transmittal', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets DTR Transmittal_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.6 Tracking / Receiving Module

- [x] 5.6.1 Add permission checks to tracking.php
  - Modify `server/api/tracking.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Tracking / Receiving', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Tracking / Receiving_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.7 Trainings Module

- [x] 5.7.1 Add permission checks to trainings.php
  - Modify `server/api/trainings.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Trainings', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Trainings_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.8 Signatories Module

- [x] 5.8.1 Add permission checks to signatories.php
  - Modify `server/api/signatories.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Signatories', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Signatories_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.9 Audit History Module

- [x] 5.9.1 Add permission checks to audit_logs.php
  - Modify `server/api/audit_logs.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Audit History', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Audit History_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.10 Departments Module

- [x] 5.10.1 Add permission checks to departments.php
  - Modify `server/api/departments.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Departments', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Departments_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.11 Birthday Celebrants Module

- [ ] 5.11.1 Add permission checks to birthday_celebrants.php
  - Modify `server/api/birthday_celebrants.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Birthday Celebrants', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Birthday Celebrants_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.12 AI Scanning Tools Module

- [x] 5.12.1 Add permission checks to ai_scan.php
  - Modify `server/api/ai_scan.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'AI Scanning Tools', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets AI Scanning Tools_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

### 5.13 Schedule Database Module

- [x] 5.13.1 Add permission checks to schedule.php
  - Modify `server/api/schedule.php`
  - Extract `X-User-Id` header
  - Map HTTP methods to actions
  - Add permission check: `checkPermission($conn, $userId, 'Schedule Database', $action)`
  - Test with denied/granted permissions and DIOS user
  - _Bug_Condition: isBugCondition(request) where request targets Schedule Database_
  - _Expected_Behavior: Denied permissions return 403_
  - _Preservation: DIOS access unchanged_
  - _Requirements: 2.1, 2.2, 2.4_

## Phase 6: Fix Validation

- [x] 6. Verify bug condition exploration test now passes

  - [x] 6.1 Re-run bug condition exploration test
    - **Property 1: Expected Behavior** - Permission Enforcement Success
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test: `php server/tests/bug_condition_test.php`
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - Verify all counterexamples from task 1 are now resolved:
      - Admin user DELETE request returns 403 when permission denied
      - Section Admin user GET request returns 403 when permission denied
      - Admin user POST request returns 403 when permission denied
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 6.2 Verify preservation tests still pass
    - **Property 2: Preservation** - DIOS Unrestricted Access and Granted Permissions
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests: `php server/tests/preservation_test.php`
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Verify all preservation requirements:
      - DIOS users can perform all actions regardless of permissions
      - Users with granted permissions can perform actions
      - Permission UI continues to work correctly
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

## Phase 7: Integration Testing

- [x] 7. End-to-end integration tests

  - [x] 7.1 Test immediate permission application
    - Configure permission as granted for Admin role on Employee Masterlist (Edit action)
    - Verify Admin user can PUT employee record
    - Change permission to denied (granted=0)
    - Verify Admin user immediately receives 403 on next PUT request (no session restart required)
    - _Requirements: 2.4_

  - [x] 7.2 Test cross-module permissions
    - Configure Admin role with View access to Employee Masterlist but not Leave Management
    - Verify Admin user can GET employees list
    - Verify Admin user receives 403 on GET leave records list
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 7.3 Test role-based access patterns
    - Configure different permissions for Super Admin, Admin, and Section Admin roles on same module
    - Verify each role's permissions are enforced correctly
    - Verify DIOS role bypasses all permission checks
    - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.4_

  - [x] 7.4 Test error messages
    - Trigger permission denial for various modules and actions
    - Verify error messages are clear and informative
    - Verify error messages include module name and action name
    - _Requirements: 2.2, 2.3_

## Phase 8: Final Checkpoint

- [x] 8. Ensure all tests pass and system is stable
  - Run all bug condition exploration tests - verify all pass
  - Run all preservation tests - verify all pass
  - Run all integration tests - verify all pass
  - Verify no regressions in existing functionality
  - Verify DIOS System Control Module Access tab still works correctly
  - Ask user if any questions or issues arise
  - _Requirements: All requirements (1.1-3.5)_
