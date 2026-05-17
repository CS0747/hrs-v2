# DIOS Password Reset Guide

## How DIOS Users Reset Passwords

### Step-by-Step Process

#### 1. User Submits Request
- User goes to login page
- Clicks "Forgot your password?"
- Enters their username
- Submits the request
- Receives confirmation message

#### 2. DIOS Accesses Password Reset Requests
**Option A: Via Sidebar Menu**
1. Log in as DIOS user
2. Look for **Administration** section in sidebar
3. Click **"Password Reset Requests"** (🔒 icon)

**Option B: Direct URL**
- Navigate to: `http://localhost:5173/password-resets`

#### 3. Review Pending Requests
The Password Reset Requests page shows:
- **User Name** - Full name of requester
- **Username** - Login username
- **Requested At** - Date and time of request
- **Status** - Pending/Approved/Rejected
- **Processed By** - Who handled the request
- **Actions** - Approve (✓) or Reject (✗) buttons

**Features:**
- 🔍 **Search** - Find requests by username or name
- 🎯 **Filter** - Show only Pending/Approved/Rejected
- 🔄 **Refresh** - Reload the list

#### 4. Approve and Reset Password
1. Find the pending request
2. Click the **green checkmark (✓)** button
3. A modal opens showing:
   - User information
   - New password field
   - Confirm password field
4. Enter the new password (minimum 6 characters)
5. Confirm the password
6. Click **"Reset Password"**
7. Success! The user's password is now reset

#### 5. Reject Request (Optional)
1. Find the pending request
2. Click the **red X (✗)** button
3. Confirm rejection
4. Request is marked as "Rejected"

#### 6. Inform the User
After resetting the password:
1. Contact the user (phone, email, or in person)
2. Provide them with the new password
3. Instruct them to log in with the new password
4. Recommend they change it after first login (if needed)

## Password Reset Modal

### Fields:
- **New Password** - Enter new password (min 6 characters)
- **Confirm Password** - Re-enter to confirm
- **👁 Toggle** - Show/hide password

### Validation:
- ✅ Password must be at least 6 characters
- ✅ Passwords must match
- ✅ Cannot be empty

### Actions:
- **Cancel** - Close modal without changes
- **Reset Password** - Save new password and mark request as approved

## Request Status Flow

```
User Submits Request
        ↓
    [PENDING] ← DIOS sees this in the list
        ↓
DIOS Takes Action
        ↓
    ┌───────┴───────┐
    ↓               ↓
[APPROVED]    [REJECTED]
Password       Request
is reset       declined
```

## Security Features

### Access Control
- ✅ Only DIOS users can access this page
- ✅ Other roles are redirected to home
- ✅ Backend validates DIOS role

### Duplicate Prevention
- ✅ Users cannot submit multiple pending requests
- ✅ Must wait for current request to be processed

### Audit Trail
- ✅ All requests are logged
- ✅ Tracks who processed each request
- ✅ Records processing timestamp
- ✅ Maintains complete history

### Password Security
- ✅ Passwords are hashed with SHA-256
- ✅ Minimum 6 characters required
- ✅ Confirmation required before reset

## Troubleshooting

### "Access denied" error
**Problem:** Non-DIOS user trying to access the page
**Solution:** Only DIOS users can reset passwords

### No pending requests showing
**Problem:** All requests have been processed
**Solution:** 
- Check the "All Status" filter
- Click "Refresh" button
- Ask users to submit new requests

### "Failed to reset password" error
**Problem:** Database or connection issue
**Solution:**
- Check database connection
- Verify `password_reset_requests` table exists
- Check browser console for errors

### User still can't log in after reset
**Problem:** Password not updated correctly
**Solution:**
- Try resetting again
- Verify the username is correct
- Check if account is active

## Best Practices

### For DIOS Users:
1. ✅ Check requests regularly (daily or as needed)
2. ✅ Verify user identity before approving
3. ✅ Use strong passwords (mix of letters, numbers)
4. ✅ Inform users promptly after reset
5. ✅ Keep track of processed requests
6. ✅ Reject suspicious or duplicate requests

### For Users:
1. ✅ Only submit request if you genuinely forgot password
2. ✅ Contact DIOS/HR after submitting
3. ✅ Wait for confirmation before trying to log in
4. ✅ Don't submit multiple requests

## Quick Reference

### Menu Location
```
Sidebar → Administration → Password Reset Requests
```

### URL
```
/password-resets
```

### Keyboard Shortcuts
- **Enter** - Submit password in modal
- **Esc** - Close modal (if implemented)

### Status Colors
- 🟠 **Orange** - Pending (needs action)
- 🟢 **Green** - Approved (completed)
- 🔴 **Red** - Rejected (declined)

## Example Workflow

**Scenario:** Employee "Juan Dela Cruz" forgot his password

1. **Juan** goes to login page
2. **Juan** clicks "Forgot your password?"
3. **Juan** enters username: `jdelacruz`
4. **Juan** submits request
5. **Juan** contacts HR/IT: "I submitted a password reset request"
6. **DIOS** logs into system
7. **DIOS** goes to Password Reset Requests
8. **DIOS** sees Juan's pending request
9. **DIOS** clicks ✓ (approve)
10. **DIOS** enters new password: `NewPass2026`
11. **DIOS** confirms password
12. **DIOS** clicks "Reset Password"
13. **DIOS** calls Juan: "Your new password is NewPass2026"
14. **Juan** logs in successfully with new password

## Summary

✅ DIOS users have full control over password resets
✅ Simple 3-click process: Find → Approve → Reset
✅ Secure and audited
✅ Easy to use interface
✅ Complete request history
✅ Search and filter capabilities

The password reset system is now fully functional and ready for production use!
