# Forgot Password Feature Implementation

## Overview
Added a "Forgot Password" feature to the login page that allows users to request password resets. Since this is an internal HRIS system without email functionality, the system uses an admin-approval workflow.

## How It Works

### User Flow
1. User clicks "Forgot your password?" link on login page
2. Modal opens asking for username
3. User enters username and submits request
4. System validates username and creates a password reset request
5. User receives confirmation message to contact administrator
6. Administrator reviews and approves/rejects the request
7. Administrator manually resets the password

### Admin Flow
1. Admin receives notification of password reset request (via system or direct contact)
2. Admin verifies user identity
3. Admin goes to Account Management
4. Admin edits the user account and sets a new password
5. Admin informs user of the new password

## Frontend Changes

### `client/src/views/Login.vue`

**Added Components:**
- "Forgot your password?" link below login button
- Modal dialog for password reset request
- Success confirmation screen
- Error handling

**New Functions:**
```javascript
function openForgotPassword() {
  showForgotPassword.value = true
  forgotUsername.value = ''
  forgotSuccess.value = false
  forgotError.value = ''
}

async function handleForgotPassword() {
  // Validates username
  // Sends POST request to backend
  // Shows success or error message
}
```

**UI Features:**
- Clean modal design matching login page style
- Loading state during submission
- Success confirmation with checkmark icon
- Error messages for invalid usernames
- Keyboard support (Enter key to submit)

## Backend Changes

### `server/api/auth.php`

**New Endpoint:**
```php
// POST /auth.php?action=request_password_reset
case 'request_password_reset':
    // Validates username
    // Checks if user exists and is active
    // Prevents duplicate pending requests
    // Creates password reset request record
```

**Validation:**
- Username must exist in database
- Account must be active (`active = 1`)
- User cannot have multiple pending requests
- Returns appropriate error messages

### Database Schema

**New Table: `password_reset_requests`**
```sql
CREATE TABLE password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    processed_by VARCHAR(100) NULL,
    notes TEXT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
);
```

**Fields:**
- `id` - Auto-increment primary key
- `user_id` - Foreign key to users table
- `username` - Username of requester
- `user_name` - Full name of requester
- `status` - pending/approved/rejected
- `requested_at` - Timestamp of request
- `processed_at` - When admin processed it
- `processed_by` - Admin who processed it
- `notes` - Optional admin notes

## Security Features

### 1. Username Validation
- Checks if username exists before creating request
- Prevents requests for inactive accounts
- Returns generic error to prevent username enumeration

### 2. Duplicate Prevention
```php
// Check if there's already a pending request
$chk = $conn->prepare('SELECT id FROM password_reset_requests WHERE user_id = ? AND status = ?');
$status = 'pending';
$chk->bind_param('is', $user['id'], $status);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    sendError('You already have a pending password reset request. Please contact your administrator.', 409);
}
```

### 3. No Automatic Password Reset
- System does NOT automatically reset passwords
- Requires admin verification and manual reset
- Prevents unauthorized password changes

### 4. Audit Trail
- All requests are logged in database
- Tracks who processed each request
- Maintains history for security audits

## User Messages

### Success Message
```
Request Submitted

Your password reset request has been submitted. 
Please contact your administrator (HR or IT) to 
complete the password reset process.
```

### Error Messages
- "Please enter your username." - Empty username
- "Username not found or account is inactive" - Invalid username
- "You already have a pending password reset request. Please contact your administrator." - Duplicate request
- "Connection error. Please try again." - Network error

## Installation

### 1. Run Database Migration
```bash
mysql -u root -p geamh_hris < server/migrate_password_resets.sql
```

### 2. Verify Table Creation
```sql
SHOW TABLES LIKE 'password_reset_requests';
DESC password_reset_requests;
```

### 3. Test the Feature
1. Go to login page
2. Click "Forgot your password?"
3. Enter a valid username
4. Submit request
5. Verify success message
6. Check database for new record

## Future Enhancements

### Recommended Additions:
1. **Admin Dashboard for Password Resets**
   - View all pending requests
   - Approve/reject with one click
   - Add notes to requests
   - Automatically generate temporary passwords

2. **Email Notifications** (if email server available)
   - Notify admins of new requests
   - Send temporary password to user
   - Confirmation emails

3. **Self-Service Password Reset** (if security requirements allow)
   - Security questions
   - SMS verification
   - Temporary password generation

4. **Request Expiration**
   - Auto-reject requests older than X days
   - Cleanup old processed requests

5. **Rate Limiting**
   - Limit requests per user per day
   - Prevent abuse

## Testing Checklist

- [ ] Click "Forgot your password?" link
- [ ] Modal opens correctly
- [ ] Enter valid username and submit
- [ ] Success message displays
- [ ] Check database for new record
- [ ] Try submitting duplicate request
- [ ] Verify duplicate prevention error
- [ ] Enter invalid username
- [ ] Verify error message
- [ ] Test with inactive account
- [ ] Verify rejection
- [ ] Test keyboard navigation (Tab, Enter)
- [ ] Test modal close (X button, outside click)
- [ ] Test on mobile viewport

## Files Modified/Created

**Created:**
- `server/migrate_password_resets.sql` - Database migration
- `FORGOT_PASSWORD_FEATURE.md` - This documentation

**Modified:**
- `client/src/views/Login.vue` - Added forgot password UI
- `server/api/auth.php` - Added password reset request endpoint

## Result

✅ Users can request password resets from login page
✅ Requests are stored in database for admin review
✅ Duplicate requests are prevented
✅ Clear user feedback and instructions
✅ Secure implementation without automatic resets
✅ Audit trail for all requests
✅ Professional UI matching login page design
