@echo off
echo ========================================
echo GEAMH HRIS Production Deployment
echo ========================================
echo.

REM Check if client folder exists
if not exist "client" (
    echo ERROR: client folder not found!
    echo Please run this script from the project root directory.
    pause
    exit /b 1
)

REM Check if server folder exists
if not exist "server" (
    echo ERROR: server folder not found!
    echo Please run this script from the project root directory.
    pause
    exit /b 1
)

echo Step 1: Building frontend...
cd client
call npm run build
if errorlevel 1 (
    echo ERROR: Build failed!
    cd ..
    pause
    exit /b 1
)
cd ..

echo.
echo Step 2: Creating production folder...
if not exist "C:\xampp\htdocs\hrsystem" (
    mkdir "C:\xampp\htdocs\hrsystem"
)

echo.
echo Step 3: Copying frontend files...
xcopy /E /I /Y client\dist\* C:\xampp\htdocs\hrsystem\

echo.
echo Step 4: Copying server files...
xcopy /E /I /Y server C:\xampp\htdocs\hrsystem\server\

echo.
echo ========================================
echo Deployment Complete!
echo ========================================
echo.
echo Files deployed to: C:\xampp\htdocs\hrsystem\
echo.
echo Access the application at:
echo   - Local: http://localhost/hrsystem/
echo   - LAN: http://[YOUR-IP]/hrsystem/
echo.
echo Next steps:
echo 1. Update database credentials in server\api\db.php
echo 2. Test the application
echo 3. Configure firewall for LAN access
echo.
pause
