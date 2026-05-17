# Account Permanent Delete Implementation

## Change Summary
Changed the account deletion functionality from **soft delete** (deactivation) to **permanent delete** (hard delete from database).

## Previous Behavior
- Clicking "Delete" would set `active = 0` in the database
- Account remained in the database but was hidden from UI
- User could not log in but data was preserved
- Accounts could be reactivated

## New Behavior
- Clicking "Delete" permanently removes the account from the database
- Account is completely deleted using SQL `DELETE` statement
- User data is permanently removed
- Action cannot be undone
- Inactive accounts can still be reactivated (if they exist)

## Backend Changes

### `server/api/auth.php`

**Before:**
```php
$stmt = $conn->prepare('UPDATE users SET active = 0 WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
sendJson(['message' => 'User deactivated']);
```

**After:**
```php
// Permanently delete the user from database
$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
sendJson(['message' => 'User permanently deleted']);
```

## Frontend Changes

### `client/src/views/accounts/AccountManagement.vue`

**Modal Updates:**
- Title: "Deactivate Account" → "Delete Account"
- Message: "Are you sure you want to deactivate this account?" → "Are you sure you want to permanently delete this account?"
- Warning: "You can reactivate this account later if needed" → "⚠️ This action cannot be undone. The account will be permanently removed from the database."
- Button: "Yes, Deactivate" → "Yes, Delete Permanently"
- Tooltip: "Deactivate" → "Delete Permanently"

## Safety Features

### 1. Prevent Deleting Last Super Admin
```php
// Prevent deleting the last Super Admin
$check = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='Super Admin' AND active=1")->fetch_assoc();
$stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
if ($target && $target['role'] === 'Super Admin' && (int)$check['cnt'] <= 1) {
    sendError('Cannot delete the last Super Admin account', 403);
}
```

### 2. Prevent Self-Deletion
```javascript
:disabled="u.id === auth.currentUser?.id"
```

### 3. Confirmation Modal
Users must confirm deletion through a modal dialog with clear warning message.

## Database Impact

**SQL Operation:**
```sql
DELETE FROM users WHERE id = ?
```

**Result:**
- Row is permanently removed from `users` table
- All user data is lost
- User ID may be reused by auto-increment (depending on MySQL settings)
- Foreign key constraints may prevent deletion if user has related records

## Important Considerations

### ⚠️ Data Loss Warning
- **Permanent deletion** means complete data loss
- No recovery possible without database backup
- Consider implementing audit logs before deletion
- May want to backup user data before deletion

### Foreign Key Constraints
If the `users` table has foreign key relationships (e.g., audit logs, created records), deletion may fail with:
```
Cannot delete or update a parent row: a foreign key constraint fails
```

**Solutions:**
1. Use `ON DELETE CASCADE` in foreign keys (deletes related records)
2. Use `ON DELETE SET NULL` in foreign keys (nullifies references)
3. Manually delete related records first
4. Keep soft delete for data integrity

### Recommended: Hybrid Approach
Consider implementing both options:
- **Deactivate** - Soft delete for regular use (preserves data)
- **Delete Permanently** - Hard delete for DIOS/Super Admin only (removes data)

## Testing

1. **Delete Active Account**
   - Click delete button on an active account
   - Confirm the deletion
   - Verify account is completely removed from database
   - Verify user cannot log in
   - Check that account does not appear in UI

2. **Prevent Last Super Admin Deletion**
   - Try to delete the only Super Admin account
   - Verify error message appears
   - Verify account is not deleted

3. **Prevent Self-Deletion**
   - Try to delete your own account
   - Verify delete button is disabled
   - Verify account cannot be deleted

4. **Database Verification**
   ```sql
   SELECT * FROM users WHERE id = [deleted_id];
   -- Should return 0 rows
   ```

## Reactivate Functionality
The reactivate function still exists for accounts that were previously soft-deleted (before this change). It will:
- Set `active = 1` for inactive accounts
- Allow previously deactivated accounts to log in again
- Only works if the account still exists in database

## Files Modified
- `server/api/auth.php` - Changed DELETE to permanent removal
- `client/src/views/accounts/AccountManagement.vue` - Updated UI text and warnings
- `ACCOUNT_DELETE_PERMANENT_FIX.md` - This documentation

## Result
✅ Accounts are now permanently deleted from database
✅ Clear warning message about permanent deletion
✅ Safety checks prevent accidental deletion
✅ Confirmation modal requires explicit user action
✅ Cannot delete last Super Admin
✅ Cannot delete own account
