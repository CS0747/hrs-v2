# Production Deployment Guide - LAN Setup

## Overview
This guide explains how to deploy the GEAMH HRIS system to a production environment in a LAN (Local Area Network) setup.

## Production Structure
```
C:\xampp\htdocs\hrsystem\
├── index.html              (from client/dist/)
├── assets\                 (from client/dist/assets/)
│   ├── index-[hash].js
│   ├── index-[hash].css
│   └── ...
├── GEAMH LOGO.png         (from client/public/)
├── favicon.ico            (from client/public/)
└── server\                (copy entire server folder)
    └── api\
        ├── auth.php
        ├── employees.php
        └── ...
```

## Step-by-Step Deployment

### Step 1: Update API URLs (IMPORTANT!)

Before building, you need to update all hardcoded API URLs to use the centralized configuration.

**Option A: Use the provided API config (Recommended)**

Import and use the API configuration in your files:

```javascript
// Instead of:
const API = 'http://localhost/hrs-v2/server/api/employees.php'

// Use:
import { API_ENDPOINTS } from '@/config/api'
const API = API_ENDPOINTS.EMPLOYEES
```

**Option B: Quick Fix for Current Setup**

Update `.env.production` file with your production server details:

```env
# If deploying to same machine
VITE_API_BASE_URL=/hrsystem/server/api/

# If deploying to different machine on LAN
VITE_API_BASE_URL=http://192.168.1.100/hrsystem/server/api/
```

### Step 2: Build the Frontend

1. Open terminal in the `client` folder:
```bash
cd client
```

2. Install dependencies (if not already installed):
```bash
npm install
```

3. Build for production:
```bash
npm run build
```

This will create a `dist` folder with optimized production files.

### Step 3: Prepare Production Folder

1. Create the production folder:
```
C:\xampp\htdocs\hrsystem\
```

2. Copy files from `client/dist/` to `C:\xampp\htdocs\hrsystem\`:
   - Copy ALL files and folders from `client/dist/`
   - This includes: `index.html`, `assets/` folder, and any other generated files

3. Copy the `server` folder:
   - Copy the entire `server` folder to `C:\xampp\htdocs\hrsystem\server\`

### Step 4: Configure Database Connection

Update `server/api/db.php` with production database credentials:

```php
<?php
// Production Database Configuration
$host = 'localhost';           // or your MySQL server IP
$dbname = 'geamh_hris';       // your database name
$username = 'root';            // your database username
$password = '';                // your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}
?>
```

### Step 5: Test the Deployment

1. **Access the application**:
   - From the same machine: `http://localhost/hrsystem/`
   - From other machines on LAN: `http://[SERVER-IP]/hrsystem/`
   - Example: `http://192.168.1.100/hrsystem/`

2. **Test login**:
   - Try logging in with a test account
   - Check browser console (F12) for any errors

3. **Test API endpoints**:
   - Navigate through different modules
   - Try creating/editing records
   - Check if notifications work

### Step 6: Troubleshooting

#### Issue: White screen or "Cannot GET /hrsystem/"
**Solution**: Make sure `index.html` is in the root of `/hrsystem/` folder

#### Issue: 404 errors for assets
**Solution**: Check that the `assets/` folder is copied correctly

#### Issue: API calls failing
**Solutions**:
1. Check `server/api/db.php` database credentials
2. Verify CORS headers in `server/api/cors.php`
3. Check that PHP and MySQL are running in XAMPP
4. Verify the API base URL in `.env.production` matches your setup

#### Issue: Images not loading (logo, favicon)
**Solution**: Copy `client/public/` contents to `hrsystem/` root

#### Issue: "Access denied" or CORS errors
**Solution**: Update `server/api/cors.php` to allow your LAN IP range:

```php
<?php
// Allow requests from LAN
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

## LAN Access Configuration

### For Windows Server:

1. **Find your server IP**:
```cmd
ipconfig
```
Look for "IPv4 Address" (e.g., 192.168.1.100)

2. **Configure Windows Firewall**:
   - Allow inbound connections on port 80 (HTTP)
   - Allow inbound connections on port 443 (HTTPS) if using SSL

3. **Configure XAMPP**:
   - Edit `C:\xampp\apache\conf\httpd.conf`
   - Find `Listen 80` and ensure it's not restricted to localhost
   - Restart Apache

### For Client Machines:

Access the application using:
```
http://[SERVER-IP]/hrsystem/
```

Example:
```
http://192.168.1.100/hrsystem/
```

## Production Checklist

- [ ] Built frontend with `npm run build`
- [ ] Copied `dist/` contents to `/hrsystem/`
- [ ] Copied `server/` folder to `/hrsystem/server/`
- [ ] Updated database credentials in `server/api/db.php`
- [ ] Tested login functionality
- [ ] Tested all major modules
- [ ] Verified API calls are working
- [ ] Checked browser console for errors
- [ ] Tested from another machine on LAN
- [ ] Configured firewall rules
- [ ] Documented server IP for users

## Quick Deployment Script

Create a file `deploy.bat` in the project root:

```batch
@echo off
echo ========================================
echo GEAMH HRIS Production Deployment
echo ========================================
echo.

echo Step 1: Building frontend...
cd client
call npm run build
cd ..

echo.
echo Step 2: Copying files to production folder...
xcopy /E /I /Y client\dist\* C:\xampp\htdocs\hrsystem\
xcopy /E /I /Y server C:\xampp\htdocs\hrsystem\server\

echo.
echo ========================================
echo Deployment Complete!
echo ========================================
echo.
echo Access the application at:
echo http://localhost/hrsystem/
echo.
echo Or from other machines:
echo http://[YOUR-IP]/hrsystem/
echo.
pause
```

Run this script to automatically build and deploy.

## Environment Variables Reference

### `.env.production`
```env
# Base URL for the application
VITE_BASE_URL=/hrsystem/

# API Base URL
# Same machine:
VITE_API_BASE_URL=/hrsystem/server/api/

# Different machine on LAN:
# VITE_API_BASE_URL=http://192.168.1.100/hrsystem/server/api/
```

### `.env.development`
```env
# Base URL for development
VITE_BASE_URL=/

# API Base URL for development
VITE_API_BASE_URL=http://localhost/hrs-v2/server/api/
```

## Security Recommendations

1. **Change default database password**
2. **Disable directory listing** in Apache
3. **Remove test/debug files** from production
4. **Enable HTTPS** if possible
5. **Regular backups** of database
6. **Keep PHP and MySQL updated**
7. **Restrict database access** to localhost only
8. **Use strong passwords** for all accounts

## Maintenance

### Updating the Application

1. Make changes in development environment
2. Test thoroughly
3. Build new production version: `npm run build`
4. Backup current production folder
5. Copy new `dist/` contents to production
6. Test the update

### Database Backups

Regular backup schedule:
```bash
# Daily backup
mysqldump -u root -p geamh_hris > backup_$(date +%Y%m%d).sql
```

## Support

For issues or questions:
1. Check browser console (F12) for errors
2. Check Apache error logs: `C:\xampp\apache\logs\error.log`
3. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
4. Review this deployment guide

---

**Last Updated**: May 16, 2026  
**Version**: 1.0  
**System**: GEAMH HRIS - Human Resource Information System
