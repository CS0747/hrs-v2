# Account Soft Delete Fix

## Issue
When deleting accounts in Account Management, users were being marked as inactive (`active = 0`) in the database but completely disappeared from the UI. This made it appear as if they were permanently deleted, but they still existed in the database and couldn't be managed.

## Problem
The system was using **soft delete** (deactivation) in the backend but the frontend was filtering out inactive accounts, making them invisible and unmanageable.

## Solution Implemented

### 1. Show Inactive Accounts in UI
Updated the frontend to display ALL accounts (both active and inactive) with visual indicators:

**Visual Changes:**
- Inactive accounts have a grayed-out appearance (70% opacity)
- "Inactive" badge displayed next to the user's name
- Avatar and role badge are dimmed
- Row background is light gray

### 2. Added Reactivate Functionality

**Frontend (`AccountManagement.vue`):**
```javascript
async function reactivateUser(u) {
  if (confirm(`Reactivate account for ${u.name}?`)) {
    try {
      const res = await auth.apiFetch(`http://localhost/hrs-v2/server/api/auth.php?action=reactivate_user&id=${u.id}`, {
        method: 'PUT'
      })
      if (res.ok) {
        await auth.fetchUsers()
      } else {
        alert('Failed to reactivate user')
      }
    } catch (e) {
      alert('Error reactivating user: ' + e.message)
    }
  }
}
```

**Backend (`auth.php`):**
```php
// PUT /auth.php?action=reactivate_user&id=X
case 'reactivate_user':
    if ($method !== 'PUT') sendError('PUT required', 405);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) sendError('ID required');

    $stmt = $conn->prepare('UPDATE users SET active = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    sendJson(['message' => 'User reactivated']);
    break;
```

### 3. Updated UI Actions

**Active Accounts:**
- ✏️ Edit button - Modify account details
- 🗑️ Deactivate button - Soft delete (set active = 0)

**Inactive Accounts:**
- 🔄 Reactivate button - Restore account (set active = 1)

### 4. Clarified Terminology

Changed "Delete" to "Deactivate" throughout the UI to make it clear that:
- Accounts are not permanently deleted
- They can be reactivated later
- Data is preserved in the database

**Modal Updates:**
- Title: "Delete Account" → "Deactivate Account"
- Message: "This action cannot be undone" → "You can reactivate this account later if needed"
- Button: "Yes, Delete" → "Yes, Deactivate"

## Database Schema

The `users` table uses the `active` column for soft delete:
- `active = 1` - Account is active (can log in)
- `active = 0` - Account is deactivated (cannot log in, but data preserved)

```sql
SELECT id, username, name, role, department, active, created_at 
FROM users 
ORDER BY id
```

## Benefits

1. **Data Preservation** - No data loss when "deleting" accounts
2. **Audit Trail** - Complete history of all accounts maintained
3. **Reversible** - Accounts can be easily reactivated
4. **Clear Status** - Visual indicators show account state
5. **Better UX** - Users understand what "delete" actually does

## Testing

1. **Deactivate Account**
   - Click deactivate button on an active account
   - Confirm the action
   - Verify account appears grayed out with "Inactive" badge
   - Verify user cannot log in

2. **Reactivate Account**
   - Click reactivate button on an inactive account
   - Confirm the action
   - Verify account returns to normal appearance
   - Verify user can log in again

3. **Filter Behavior**
   - Search should work for both active and inactive accounts
   - Inactive accounts should remain visible in search results

## Files Modified

- `client/src/views/accounts/AccountManagement.vue` - Added inactive display and reactivate function
- `server/api/auth.php` - Added reactivate_user endpoint
- `ACCOUNT_SOFT_DELETE_FIX.md` - This documentation

## Result

✅ Inactive accounts are now visible in the UI
✅ Accounts can be reactivated with one click
✅ Clear visual distinction between active and inactive
✅ Terminology clarified (deactivate vs delete)
✅ Data is preserved in the database
✅ Full account lifecycle management
