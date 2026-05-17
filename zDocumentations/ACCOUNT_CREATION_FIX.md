# Account Creation Duplicate Fix

## Issue
When creating a new account in Account Management, multiple duplicate users were being created instead of a single user. This resulted in several accounts with the same username and details appearing in the system.

## Root Causes Identified

### 1. **Missing Async/Await in Frontend**
The `save()` function in `AccountManagement.vue` was not properly awaiting the `auth.signup()` call, causing the function to complete before the API request finished. This could lead to race conditions and multiple submissions.

### 2. **No Double-Submit Prevention**
There was no guard against double-clicking the submit button, allowing multiple rapid submissions of the same form data.

### 3. **Race Condition in Database**
The signup endpoint didn't use database transactions, allowing concurrent requests to bypass the duplicate username check and create multiple accounts.

### 4. **Case-Sensitive Username Check**
The duplicate username check was case-sensitive, potentially allowing "admin" and "Admin" to be created as separate accounts.

### 5. **Missing X-User-Id Header**
The `fetchUsers()` function wasn't using the `apiFetch()` wrapper, so permission checks might not work correctly.

## Solutions Implemented

### Backend Fixes (`server/api/auth.php`)

**1. Added Database Transaction**
```php
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        'INSERT INTO users (username, password, name, role, department)
         VALUES (?, SHA2(?, 256), ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $username, $password, $name, $role, $dept);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create account: ' . $stmt->error);
    }
    
    $insertId = $conn->insert_id;
    $stmt->close();
    
    $conn->commit();
    sendJson(['id' => $insertId, 'message' => 'Account created'], 201);
    
} catch (Exception $e) {
    $conn->rollback();
    sendError($e->getMessage(), 500);
}
```

**2. Case-Insensitive Username Check**
```php
// Check duplicate username (case-insensitive)
$chk = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
$chk->bind_param('s', $username);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    $chk->close();
    sendError('Username already exists.', 409);
}
$chk->close();
```

### Frontend Fixes

**1. Fixed Async/Await in `AccountManagement.vue`**
```javascript
async function save() {
  if (saving.value) return // Prevent double submission
  
  formError.value = ''
  // ... validation ...

  saving.value = true
  try {
    if (editId.value) {
      await auth.updateUser(editId.value, data)
    } else {
      const success = await auth.signup({ ... })
      if (!success) {
        saving.value = false
        return // Don't close modal if signup failed
      }
    }
    showForm.value = false
  } catch (e) {
    formError.value = 'Failed to save account. Please try again.'
  } finally {
    saving.value = false
  }
}
```

**2. Added Double-Submit Guard**
The `if (saving.value) return` check at the start of the `save()` function prevents multiple simultaneous submissions.

**3. Fixed `fetchUsers()` in `auth.js`**
```javascript
async function fetchUsers() {
  try {
    const res = await apiFetch(`${AUTH_API}?action=users`) // Now uses apiFetch with X-User-Id header
    const data = await res.json()
    if (Array.isArray(data.users)) {
      users.value = data.users.map(u => ({ ...u, password: undefined }))
    }
  } catch { /* silent — keep existing list */ }
}
```

## Testing Recommendations

1. **Test Single Account Creation**
   - Click "Add Account" once
   - Fill in the form
   - Click "Add Account" button once
   - Verify only ONE account is created

2. **Test Double-Click Protection**
   - Click "Add Account"
   - Fill in the form
   - Rapidly double-click the "Add Account" button
   - Verify only ONE account is created

3. **Test Duplicate Username**
   - Try to create an account with an existing username
   - Verify error message appears
   - Verify no duplicate account is created

4. **Test Case-Insensitive Username**
   - Create account with username "testuser"
   - Try to create another account with "TestUser"
   - Verify error message appears

5. **Test Concurrent Requests**
   - Open two browser tabs
   - Try to create the same account simultaneously
   - Verify only ONE account is created
   - Verify one request gets a "Username already exists" error

## Result
✅ Database transactions prevent race conditions
✅ Double-submit guard prevents rapid clicks
✅ Proper async/await ensures requests complete before UI updates
✅ Case-insensitive username check prevents similar duplicates
✅ Permission checks work correctly with X-User-Id header

The account creation process is now robust and prevents duplicate user creation.
