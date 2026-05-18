# Quick Test Guide - GEAMH HRIS

## 🚀 Quick Start

### 1. Verify System Health
```bash
cd server
php verify_system.php
```

**Expected Output**: ✅ System is HEALTHY and ready for use

---

## 🧪 Feature Testing

### Test 1: Login System
1. Navigate to `http://localhost/hrs-v2/`
2. Enter username and password
3. Click "Log in"
4. ✅ Should redirect to Dashboard

### Test 2: Password Reset (User Side)
1. On login page, click "Forgot your password?"
2. Enter username (e.g., `testuser`)
3. Click "Submit Request"
4. ✅ Should show success message

### Test 3: Password Reset (DIOS Side)
1. Login as DIOS user
2. Check notification bell (should have badge)
3. Click bell → see "New Password Reset Request"
4. Click notification OR navigate to Administration → Password Resets
5. Click "Approve & Reset" on pending request
6. Enter new password (min 6 characters)
7. Click "Reset Password"
8. ✅ Should show success alert

### Test 4: Notifications
1. Login as any user
2. Check notification bell icon
3. Click bell to open panel
4. Click a notification
5. ✅ Should navigate to linked page
6. ✅ Notification should mark as read

### Test 5: Employee Management
1. Navigate to Employee Masterlist
2. Click "Add Employee"
3. Fill in required fields
4. Click "Save"
5. ✅ Employee should appear in list

### Test 6: Schedule Database
1. Navigate to Schedule Database
2. Click on a date
3. Add schedule details
4. Click "Save"
5. ✅ Schedule should appear on calendar

### Test 7: Leave Management
1. Navigate to Leave Management
2. Click "Add Leave"
3. Fill in leave details
4. Click "Submit"
5. ✅ Leave record should be created

### Test 8: Permissions (Section Admin)
1. Login as Section Admin
2. ✅ Should only see: Dashboard, Schedule Database, User Manual
3. Try to access other pages
4. ✅ Should redirect to Dashboard

### Test 9: Permissions (DIOS)
1. Login as DIOS
2. ✅ Should see all menu items
3. Navigate to System Control
4. ✅ Should have full access

---

## 🔍 Database Verification

### Check Tables
```sql
USE geamh_hris;
SHOW TABLES;
```

**Expected**: 12+ tables including:
- users
- employees
- notifications
- password_reset_requests
- module_permissions
- audit_logs
- schedules
- leave_records
- travel_orders
- trainings
- departments
- signatories

### Check Password Reset Requests
```sql
SELECT * FROM password_reset_requests ORDER BY requested_at DESC;
```

### Check Notifications
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

### Check Active Users
```sql
SELECT id, username, name, role, active FROM users WHERE active = 1;
```

---

## 🐛 Troubleshooting

### Issue: Login not working
**Solution**:
1. Check database connection in `server/api/db.php`
2. Verify user exists: `SELECT * FROM users WHERE username = 'youruser';`
3. Check browser console for errors

### Issue: Notifications not showing
**Solution**:
1. Check notification table: `SELECT COUNT(*) FROM notifications;`
2. Verify user ID is being sent in headers
3. Check browser console for API errors

### Issue: Password reset not working
**Solution**:
1. Verify table exists: `DESCRIBE password_reset_requests;`
2. Check DIOS user exists: `SELECT * FROM users WHERE role = 'DIOS';`
3. Check notification_helpers.php is included

### Issue: Build errors
**Solution**:
```bash
cd client
rm -rf node_modules
npm install
npm run build
```

### Issue: CORS errors
**Solution**:
1. Check `server/api/cors.php` exists
2. Verify it's included in API files
3. Check browser console for specific error

---

## 📊 Performance Check

### Database Query Speed
```bash
cd server
php verify_system.php
```
Look for: "Query executed in X.XXms"  
**Good**: <1ms  
**Acceptable**: <5ms  
**Slow**: >10ms

### Frontend Build Speed
```bash
cd client
npm run build
```
**Good**: <500ms  
**Acceptable**: <1000ms  
**Slow**: >2000ms

### API Response Time
Use browser DevTools Network tab:
**Good**: <100ms  
**Acceptable**: <300ms  
**Slow**: >500ms

---

## ✅ Success Criteria

### System is Working if:
- [x] Login successful
- [x] Dashboard loads
- [x] Notifications appear
- [x] Password reset workflow completes
- [x] CRUD operations work
- [x] Permissions enforced
- [x] No console errors
- [x] Build completes without errors
- [x] Database queries fast (<5ms)

---

## 🎯 Quick Commands

### Backend
```bash
# Verify system
cd server
php verify_system.php

# Run password reset migration
php run_password_reset_migration.php

# Run notification migration
php run_notification_migration.php
```

### Frontend
```bash
# Development
cd client
npm run dev

# Build
npm run build

# Preview build
npm run preview
```

### Database
```bash
# Access MySQL (if mysql command available)
mysql -u root geamh_hris

# Or use phpMyAdmin
# Navigate to http://localhost/phpmyadmin
```

---

## 📞 Support

### Check Logs
- Browser Console (F12)
- Network Tab (F12 → Network)
- PHP Error Log (check XAMPP logs)

### Verification Script
```bash
cd server
php verify_system.php
```

### Documentation
- `PASSWORD_RESET_VERIFICATION.md` - Password reset testing
- `SYSTEM_OPTIMIZATION_COMPLETE.md` - Full system status
- `README.md` - Project overview

---

**Last Updated**: May 18, 2026  
**System Status**: ✅ FULLY OPERATIONAL  
**Quick Test**: Run `php server/verify_system.php`
