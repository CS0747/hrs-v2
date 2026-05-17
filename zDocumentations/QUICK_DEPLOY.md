# Quick Deployment Reference

## 🚀 Fast Deployment (3 Steps)

### 1. Build
```bash
cd client
npm run build
```

### 2. Copy Files
```
client/dist/*  →  C:\xampp\htdocs\hrsystem\
server/        →  C:\xampp\htdocs\hrsystem\server\
```

### 3. Configure Database
Edit `C:\xampp\htdocs\hrsystem\server\api\db.php`:
```php
$host = 'localhost';
$dbname = 'geamh_hris';
$username = 'root';
$password = 'your_password';
```

## ✅ Access
- **Local**: http://localhost/hrsystem/
- **LAN**: http://192.168.1.XXX/hrsystem/

## 🔧 Automated Deployment
Run `deploy.bat` from project root - it does everything automatically!

## 📝 Important Files

| File | Purpose |
|------|---------|
| `client/.env.production` | Production API URLs |
| `client/vite.config.js` | Build configuration |
| `server/api/db.php` | Database connection |
| `server/api/cors.php` | CORS settings |

## 🐛 Common Issues

### White Screen
- Check if `index.html` exists in `/hrsystem/`
- Check browser console (F12) for errors

### API Errors
- Verify database credentials in `db.php`
- Check if Apache and MySQL are running
- Verify CORS settings in `cors.php`

### Can't Access from LAN
- Check Windows Firewall (allow port 80)
- Verify Apache is listening on all interfaces
- Use correct server IP address

## 📦 Production Folder Structure
```
C:\xampp\htdocs\hrsystem\
├── index.html
├── assets\
│   ├── index-[hash].js
│   └── index-[hash].css
├── GEAMH LOGO.png
├── favicon.ico
└── server\
    └── api\
        ├── auth.php
        ├── employees.php
        └── ...
```

## 🔐 Security Checklist
- [ ] Change database password
- [ ] Remove test accounts
- [ ] Enable HTTPS (if available)
- [ ] Configure firewall
- [ ] Regular database backups
- [ ] Update PHP/MySQL regularly

## 📞 Get Help
1. Check `PRODUCTION_DEPLOYMENT_GUIDE.md` for detailed instructions
2. Review Apache error logs: `C:\xampp\apache\logs\error.log`
3. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
4. Open browser console (F12) for frontend errors

---
**Quick Tip**: Always test in development before deploying to production!
