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

## Code Architecture & Structure

### Client (`/client`)
- Single-page Vue 3 application using Vite as the build tool.
- State management: Pinia (store files likely in `/client/src/stores`).
- Routing: Vue Router (routes defined in `/client/src/router/index.js`).
- Components: Located in `/client/src/components` and `/client/src/views`.
- Styles: CSS/scoped styles within Vue components; possibly using CSS modules or plain CSS.
- Assets: Images, icons, etc., in `/client/src/assets` or `/client/public`.
- Configuration: Vite config in `/client/vite.config.js`.

### Server (`/server`)
- RESTful API built with native PHP (no framework).
- Entry points: All API endpoints are individual PHP files in `/server/api/` (e.g., `/server/api/auth.php` handles authentication).
- Database interaction: Centralized database connection in `/server/api/db.php` (likely uses PDO or MySQLi).
- Business logic: Contained within each API endpoint file; some shared functions may exist in `/server/api/` or included via `require_once`.
- SQL scripts: Database schema and seed files are in the `/server` root (e.g., `geamh_hris.sql` for structure, seed files for initial data).
- Tests: PHPUnit tests in `/server/tests/` (e.g., `/server/tests/dios_control_test.php`).
- Uploads: File uploads stored in `/server/uploads/` (ensure web server has write permissions).

### General Notes
- The client and server are decoupled; the client makes HTTP requests to the server's API endpoints.
- Environment configuration: Check for `.env` files or configuration constants in PHP files (look for `define()` or `$_ENV` usage).
- Authentication: Likely token-based (JWT or similar) handled in `/server/api/auth.php` and middleware-style checks in other API files.
- CORS: Server API files may include headers to allow cross-origin requests from the Vue dev server.