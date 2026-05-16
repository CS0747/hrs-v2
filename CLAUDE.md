# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Client (Vue 3 + Vite)
- Install dependencies: `npm install` (run from `/client` directory)
- Development server: `npm run dev` (starts Vite dev server at http://localhost:5173)
- Build for production: `npm run build` (outputs to `/client/dist`)
- Preview production build: `npm run preview` (serves the built app locally)

### Server (PHP API)
- No build step required; PHP files are interpreted directly.
- Run tests: PHPUnit tests are located in `/server/tests`. Execute with `php vendor/bin/phpunit` from `/server` directory (assuming PHPUnit is installed via Composer).
- Start a local PHP server for testing: `php -S localhost:8000 -t /server/public` (adjust document root as needed; currently no public folder, so may need to point to `/server` or create a router script).

### Database Setup
- Import database schema: `mysql -u username -p database_name < server/geamh_hris.sql`
- Run migrations (if any): Apply individual `.sql` files in `/server/` directory
- Seed initial data: Execute `seed_*.sql` files after schema import
- Note: Database credentials are currently hardcoded in `/server/api/db.php`

## Code Architecture & Structure

### Client (`/client`)
- Single-page Vue 3 application using Vite as the build tool.
- State management: Pinia (store files likely in `/client/src/stores`).
- Routing: Vue Router (routes defined in `/client/src/router/index.js`).
- Components: Located in `/client/src/components` and `/client/src/views`.
- Styles: CSS/scoped styles within Vue components; possibly using CSS modules or plain CSS.
- Assets: Images, icons, etc., in `/client/src/assets` or `/client/public`.
- Configuration: Vite config in `/client/vite.config.js`.
- AI Integration: Groq SDK for AI chatbot and document scanning (requires `VITE_GROQ_API_KEY`)

### Server (`/server`)
- RESTful API built with native PHP (no framework).
- Entry points: All API endpoints are individual PHP files in `/server/api/` (e.g., `/server/api/auth.php` handles authentication).
- Database interaction: Centralized database connection in `/server/api/db.php` (uses MySQLi with prepared statements).
- Business logic: Contained within each API endpoint file; some shared functions may exist in `/server/api/` or included via `require_once`.
- SQL scripts: Database schema and seed files are in the `/server` root (e.g., `geamh_hris.sql` for structure, seed files for initial data).
- Tests: PHPUnit tests in `/server/tests/` (e.g., `/server/tests/dios_control_test.php`).
- Uploads: File uploads stored in `/server/uploads/` (ensure web server has write permissions).
- DIOS Module: AI/Analytics module with secure SQL execution (requires `X-DIOS-Token` header)

### Key Modules
- **Authentication**: `/server/api/auth.php` - Token-based auth with SHA2-256 password hashing
- **DIOS Control**: `/server/api/dios_control.php` - AI-powered database analytics with SQL whitelisting
- **AI Scanning**: `/server/api/ai_scan.php` - Document scanning and analysis
- **Audit Logs**: `/server/api/audit_logs.php` - Action tracking and audit trail

## Environment Configuration

### Client Environment Variables
- `VITE_GROQ_API_KEY`: API key for Groq AI services (required for AI chatbot)
- Create `.env` file in `/client` with: `VITE_GROQ_API_KEY=your_api_key_here`

### Server Database Configuration
- Database credentials are currently hardcoded in `/server/api/db.php`
- Look for `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` constants
- Consider moving to environment variables for production

## API Communication

### Authentication
- All API requests require `X-User-Id` header for user identification
- Some endpoints require `Authorization` header for token authentication
- Permissions checked via `module_permissions` table

### API Base URLs (Client)
- Hardcoded as `http://localhost/hrs-v2/server/api/` in client
- Main APIs: auth.php, audit_logs.php, module_permissions.php
- AI Services: Direct calls to Groq API (not through PHP backend)

### CORS Configuration
- Server includes CORS headers in `/server/api/cors.php`
- Allows requests from Vue dev server (localhost:5173)

## Development Workflow

### Getting Started
1. Set up database: Import `geamh_hris.sql` and run seed files
2. Configure environment variables for client (Groq API key)
3. Start PHP server: `php -S localhost:8000 -t /server`
4. Start Vue client: `npm run dev` (from `/client`)
5. Test API endpoints at `http://localhost/hrs-v2/server/api/`

### Testing
- Run PHPUnit tests: `php vendor/bin/phpunit` (from `/server`)
- Manual API testing: Use browser or curl with appropriate headers
- AI features require valid Groq API key

### File Uploads
- Upload directory: `/server/uploads/`
- Ensure web server has write permissions
- Files not currently secured (consider adding validation)