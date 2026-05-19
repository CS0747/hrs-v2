# How to Restore Files After Git Pull

## ⚠️ WARNING

If you run `git pull` now, you will **LOSE** these important files:

### Critical Files That Will Be Deleted:
- ❌ `client/src/views/admin/PasswordResetRequests.vue`
- ❌ `server/api/notifications.php`
- ❌ `server/api/notification_helpers.php`
- ❌ `client/src/composables/useLiveNotifications.js`
- ❌ `client/src/config/api.js`
- ❌ `server/migrate_password_resets.sql`
- ❌ `server/migrate_notifications.sql`
- ❌ All documentation in `zDocumentations/`

### Files That Will Be Modified:
- ⚠️ Many store files (auth, employees, schedule, etc.)
- ⚠️ Login.vue
- ⚠️ Router configuration
- ⚠️ And many more...

---

## Solution: Save Your Work First

### Option 1: Create a Backup Branch (RECOMMENDED)

```bash
# Create a backup of your current work
git branch backup-before-pull

# Now you can safely pull
git pull origin main

# If something breaks, you can restore:
git checkout backup-before-pull
```

### Option 2: Stash Your Changes

```bash
# Save all your local changes
git stash save "backup before pull"

# Pull the changes
git pull origin main

# Restore your changes
git stash pop
```

### Option 3: Cherry-Pick Important Files

```bash
# Pull first
git pull origin main

# Then restore specific files from your backup
git checkout backup-before-pull -- client/src/views/admin/PasswordResetRequests.vue
git checkout backup-before-pull -- server/api/notifications.php
git checkout backup-before-pull -- client/src/config/api.js
# ... etc
```

---

## Recommended Approach

### Step 1: Create Backup Branch
```bash
git branch backup-my-work
```

### Step 2: Check What You Have
```bash
git status
git log --oneline -5
```

### Step 3: Pull Changes
```bash
git pull origin main
```

### Step 4: Restore Important Files

After pulling, restore the files you need:

```bash
# Restore password reset feature
git checkout backup-my-work -- client/src/views/admin/PasswordResetRequests.vue
git checkout backup-my-work -- server/migrate_password_resets.sql

# Restore notifications system
git checkout backup-my-work -- server/api/notifications.php
git checkout backup-my-work -- server/api/notification_helpers.php
git checkout backup-my-work -- client/src/composables/useLiveNotifications.js
git checkout backup-my-work -- server/migrate_notifications.sql

# Restore API configuration
git checkout backup-my-work -- client/src/config/api.js

# Restore environment files
git checkout backup-my-work -- client/.env.production
git checkout backup-my-work -- client/.env.development

# Restore documentation
git checkout backup-my-work -- zDocumentations/
```

### Step 5: Test Everything
```bash
# Rebuild the frontend
cd client
npm run build

# Test the application
# Check if password resets work
# Check if notifications work
```

---

## Files You MUST Restore

### 1. Password Reset System
```bash
git checkout backup-my-work -- client/src/views/admin/PasswordResetRequests.vue
git checkout backup-my-work -- server/migrate_password_resets.sql
```

### 2. Notifications System
```bash
git checkout backup-my-work -- server/api/notifications.php
git checkout backup-my-work -- server/api/notification_helpers.php
git checkout backup-my-work -- client/src/composables/useLiveNotifications.js
git checkout backup-my-work -- server/migrate_notifications.sql
```

### 3. API Configuration (CRITICAL)
```bash
git checkout backup-my-work -- client/src/config/api.js
```

### 4. Environment Files
```bash
git checkout backup-my-work -- client/.env.production
git checkout backup-my-work -- client/.env.development
git checkout backup-my-work -- client/public/.htaccess
```

### 5. Deployment Scripts
```bash
git checkout backup-my-work -- deploy.bat
```

---

## Alternative: Manual Backup

If you don't want to use git, manually copy these folders:

```bash
# Create backup folder
mkdir C:\backup-hrs-v2

# Copy important files
xcopy /E /I client\src\views\admin C:\backup-hrs-v2\admin
xcopy /E /I server\api C:\backup-hrs-v2\api
xcopy /E /I client\src\config C:\backup-hrs-v2\config
xcopy /E /I client\src\composables C:\backup-hrs-v2\composables
xcopy /E /I zDocumentations C:\backup-hrs-v2\zDocumentations
```

Then after pulling, copy them back.

---

## What Happened?

Someone on your team:
1. Pulled an older version of the code
2. Deleted many files (probably by accident)
3. Pushed those deletions to GitHub

This is why the remote wants to delete your files.

---

## Prevention for Future

### 1. Always Create Feature Branches
```bash
git checkout -b feature/my-feature
# Make changes
git commit -m "Add feature"
git push origin feature/my-feature
```

### 2. Never Force Push to Main
```bash
# DON'T DO THIS:
git push -f origin main  # ❌ Dangerous!

# DO THIS:
git push origin main     # ✅ Safe
```

### 3. Review Changes Before Pulling
```bash
git fetch origin
git diff HEAD..origin/main --name-status
# Review what will change, then:
git pull origin main
```

---

## Quick Commands

### Save everything now:
```bash
git branch backup-$(date +%Y%m%d)
```

### Pull safely:
```bash
git pull origin main
```

### Restore if needed:
```bash
git checkout backup-20260518 -- [filename]
```

---

## Need Help?

If you're unsure, **DON'T PULL YET**. 

Contact your team first to understand why these files were deleted from the remote repository.
