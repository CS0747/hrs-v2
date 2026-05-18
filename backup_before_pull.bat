@echo off
echo ========================================
echo  Backing Up Your Work Before Git Pull
echo ========================================
echo.

REM Create backup branch
echo Creating backup branch...
git branch backup-before-pull-%date:~-4,4%%date:~-10,2%%date:~-7,2%
if %errorlevel% neq 0 (
    echo ERROR: Failed to create backup branch
    pause
    exit /b 1
)

echo.
echo ✓ Backup branch created successfully!
echo.
echo Branch name: backup-before-pull-%date:~-4,4%%date:~-10,2%%date:~-7,2%
echo.
echo You can now safely run: git pull origin main
echo.
echo If something goes wrong, restore with:
echo   git checkout backup-before-pull-%date:~-4,4%%date:~-10,2%%date:~-7,2%
echo.
pause
