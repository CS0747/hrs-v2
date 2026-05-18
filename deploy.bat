@echo off
echo ========================================
echo  GEAMH HRIS - Production Deployment
echo ========================================
echo.

echo Step 1: Building frontend...
cd client
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)

echo.
echo Step 2: Copying files to production...
cd ..

REM Copy dist contents to production folder
xcopy /E /I /Y client\dist\* C:\xampp\htdocs\hrsystem\

REM Copy server folder
xcopy /E /I /Y server\* C:\xampp\htdocs\hrsystem\server\

echo.
echo ========================================
echo  ✓ Deployment Complete!
echo ========================================
echo.
echo Production URL: http://localhost/hrsystem/
echo.
pause
