@echo off
echo ========================================
echo  Restoring Important Files After Pull
echo ========================================
echo.

REM Find the backup branch
for /f "tokens=*" %%i in ('git branch ^| findstr "backup-before-pull"') do set BACKUP_BRANCH=%%i
set BACKUP_BRANCH=%BACKUP_BRANCH:~2%

if "%BACKUP_BRANCH%"=="" (
    echo ERROR: No backup branch found!
    echo Please run backup_before_pull.bat first
    pause
    exit /b 1
)

echo Using backup branch: %BACKUP_BRANCH%
echo.

echo Restoring password reset system...
git checkout %BACKUP_BRANCH% -- client/src/views/admin/PasswordResetRequests.vue 2>nul
git checkout %BACKUP_BRANCH% -- server/migrate_password_resets.sql 2>nul

echo Restoring notifications system...
git checkout %BACKUP_BRANCH% -- server/api/notifications.php 2>nul
git checkout %BACKUP_BRANCH% -- server/api/notification_helpers.php 2>nul
git checkout %BACKUP_BRANCH% -- client/src/composables/useLiveNotifications.js 2>nul
git checkout %BACKUP_BRANCH% -- server/migrate_notifications.sql 2>nul

echo Restoring API configuration...
git checkout %BACKUP_BRANCH% -- client/src/config/api.js 2>nul

echo Restoring environment files...
git checkout %BACKUP_BRANCH% -- client/.env.production 2>nul
git checkout %BACKUP_BRANCH% -- client/.env.development 2>nul
git checkout %BACKUP_BRANCH% -- client/public/.htaccess 2>nul

echo Restoring deployment scripts...
git checkout %BACKUP_BRANCH% -- deploy.bat 2>nul

echo Restoring documentation...
git checkout %BACKUP_BRANCH% -- zDocumentations/ 2>nul

echo Restoring database files...
git checkout %BACKUP_BRANCH% -- geamh_hris.sql 2>nul

echo.
echo ========================================
echo  ✓ Restoration Complete!
echo ========================================
echo.
echo Files restored from: %BACKUP_BRANCH%
echo.
echo Next steps:
echo 1. Test the application
echo 2. Rebuild frontend: cd client ^&^& npm run build
echo 3. Commit the restored files: git add . ^&^& git commit -m "Restore important files"
echo.
pause
