# Notification Migration Files - Explanation

## What Are These Files?

### `server/run_notification_migration.php`
A PHP script that creates the `notifications` table in your database.

### `server/migrate_notifications.sql`
The SQL commands to create the notifications table structure.

---

## Why Do They Exist?

These files were created during the **Live Notifications Implementation** to add a new `notifications` table to your existing database **without affecting existing data**.

### The Problem They Solve:
When you add a new feature (like notifications) to an existing system, you need to:
1. ✅ Add new database tables
2. ✅ Not break existing data
3. ✅ Make it easy to apply to production

### Migration Approach:
Instead of manually running SQL commands, you run:
```bash
php server/run_notification_migration.php
```

This automatically:
- Connects to your database
- Creates the `notifications` table
- Uses `CREATE TABLE IF NOT EXISTS` (safe to run multiple times)
- Reports success or errors

---

## Current Status

### ❌ Problem: Notifications Table Not in Main Schema

The `notifications` table is **NOT included** in `geamh_hris.sql`, which means:

1. **Existing installations** (like yours):
   - ✅ Have the table (if you ran the migration)
   - ✅ Notifications work fine

2. **New installations**:
   - ❌ Won't have the notifications table
   - ❌ Will get errors when trying to use notifications
   - ❌ Need to manually run the migration

3. **Database imports**:
   - ❌ If someone imports `geamh_hris.sql`, they'll be missing the table

---

## What You Should Do

### Option 1: Add to Main SQL File (RECOMMENDED)

**Pros:**
- ✅ Complete database schema in one file
- ✅ New installations work immediately
- ✅ No extra migration steps needed
- ✅ Easier to maintain

**Cons:**
- ⚠️ Need to update `geamh_hris.sql`

**How to do it:**
1. Add the notifications table to `geamh_hris.sql`
2. Delete migration files (no longer needed)
3. Export fresh SQL from your database

---

### Option 2: Keep Migration Files

**Pros:**
- ✅ Existing installations can upgrade easily
- ✅ Separates schema changes from base schema

**Cons:**
- ⚠️ New installations need extra step
- ⚠️ More files to maintain
- ⚠️ Can forget to run migrations

**When to use:**
- If you have many production installations
- If you need to track schema changes over time
- If you're using a migration system

---

## Recommended Action

### For Your Project: **Add to Main SQL File**

Since this is a single installation (not a distributed product), it's better to have one complete SQL file.

### Steps:

1. **Check if notifications table exists in your database:**
   ```sql
   SHOW TABLES LIKE 'notifications';
   ```

2. **If it exists, export your current database:**
   ```bash
   # From phpMyAdmin or command line
   mysqldump -u root geamh_hris > geamh_hris_updated.sql
   ```

3. **Replace the old `geamh_hris.sql` with the new export**

4. **Delete migration files:**
   - `server/run_notification_migration.php`
   - `server/migrate_notifications.sql`

---

## What the Notifications Table Does

```sql
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                    -- Who receives the notification
  type VARCHAR(50) NOT NULL,               -- Type: 'password_reset', 'leave_request', etc.
  title VARCHAR(255) NOT NULL,             -- Notification title
  message TEXT NOT NULL,                   -- Notification message
  reference_id INT NULL,                   -- ID of related record
  reference_type VARCHAR(50) NULL,         -- Type of related record
  link VARCHAR(255) NULL,                  -- Direct link to the item
  is_read TINYINT(1) DEFAULT 0,           -- Read status
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,                  -- When it was read
  
  -- Indexes for performance
  INDEX idx_user_id (user_id),
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at),
  INDEX idx_user_read (user_id, is_read)
);
```

### Used By:
- Real-time notification system
- Password reset requests
- Leave approvals
- Travel order notifications
- Employee additions
- Training enrollments
- Audit log alerts

---

## Migration Best Practices

### For Future Features:

If you add more tables later, you have two options:

#### Option A: Direct SQL Update (Simple Projects)
1. Add table to database
2. Export full database
3. Replace `geamh_hris.sql`

#### Option B: Migration System (Complex Projects)
1. Create migration file: `migrate_YYYYMMDD_feature_name.sql`
2. Create runner script: `run_migration.php`
3. Keep migration history
4. Track which migrations have been applied

---

## Summary

### Current Situation:
- ✅ Migration files exist
- ❌ Notifications table not in main SQL file
- ⚠️ New installations will be missing the table

### Recommended Fix:
1. Export your current database (includes notifications table)
2. Replace `geamh_hris.sql` with the new export
3. Delete migration files
4. Done! One complete SQL file

### Alternative:
Keep migration files if you plan to:
- Distribute the system to multiple locations
- Track schema changes over time
- Use a formal migration system

---

## Quick Decision Guide

**Choose "Add to Main SQL"** if:
- ✅ Single installation
- ✅ Want simplicity
- ✅ Don't need migration history

**Choose "Keep Migrations"** if:
- ✅ Multiple installations
- ✅ Need upgrade path
- ✅ Want change tracking

For your project: **Add to Main SQL** ✅
