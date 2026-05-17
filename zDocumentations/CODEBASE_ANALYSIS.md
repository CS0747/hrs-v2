# HRS-V2 Codebase Analysis

## Overview
This is an HRIS (Human Resource Information System) application with a decoupled architecture:
- **Frontend**: Vue 3 + Vite SPA (Single Page Application)
- **Backend**: Native PHP REST API
- **Database**: MySQL (schema in `geamh_hris.sql`)

## Technology Stack

### Frontend (`/client`)
- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router 4
- **HTTP Client**: Likely using native `fetch` or axios (check components)
- **UI Libraries**: Not immediately apparent from package.json - may be custom CSS
- **Dependencies**: 
  - `groq-sdk`: For AI integration (likely for DIOS features)
  - `pinia`: State management
  - `vue-router`: Routing
  - `xlsx`: Excel export/import
  - `vite`: Build tool
  - `@vitejs/plugin-vue`: Vue support for Vite
  - `vite-plugin-vue-devtools`: Development tools

### Backend (`/server`)
- **Language**: PHP 7.4+ (uses mysqli, prepared statements)
- **Architecture**: Modular REST API (one file per endpoint)
- **Database**: MySQLi extension with prepared statements
- **Authentication**: Token-based (SHA2 password hashing, custom token system)
- **Security**: 
  - Prepared statements prevent SQL injection
  - Input validation and sanitization
  - Role-based access control (RBAC)
  - CORS headers for frontend communication
  - SQL injection prevention in DIOS module (whitelisting, statement blocking)

## Directory Structure

```
/client
  /public          # Static assets
  /src
    /assets        # Images, icons, etc.
    /components    # Reusable Vue components
    /router        # Vue Router configuration
    /stores        # Pinia stores
    /views         # Page components
    App.vue        # Root component
    main.js        # Entry point
  package.json
  vite.config.js

/server
  /api             # PHP API endpoints (REST-like)
  /tests           # PHPUnit tests
  /uploads         # File upload storage
  *.sql            # Database schema and seed files
```

## Key Features Identified

### Authentication System (`/server/api/auth.php`)
- Login with username/password (password hashed with SHA2-256)
- User registration with role validation
- Profile management
- Password change
- Soft delete (deactivation) of users
- Permission checking via `module_permissions` table
- Audit logging for login attempts
- Last Super Admin protection (cannot delete last admin)

### Database Layer (`/server/api/db.php`)
- Centralized database connection function
- JSON response helpers (`sendJson`, `sendError`)
- Permission checking system:
  - `getUserRole()`: Fetches role from users table
  - `checkPermission()`: Checks `module_permissions` table
  - Fail-open behavior for backward compatibility (when no user ID)
  - Special handling for DIOS role (unrestricted access)
  - Role-based access control with module/action granularity

### DIOS Control System (`/server/api/dios_control.php`)
- **DIOS** appears to be an AI/Analytics module (based on Groq SDK in frontend)
- Secure API requiring `X-DIOS-Token` header
- Whitelisted table access (prevents arbitrary table querying)
- SQL execution with safety measures:
  - Blocks dangerous statements (DROP, TRUNCATE, GRANT, etc.)
  - Limits result sets to 500 rows
  - Sets execution timeout (10 seconds)
  - Input sanitization for table names
- Features:
  - List tables
  - Describe table structure
  - Database statistics (record counts, size)
  - Arbitrary SQL query execution (with safeguards)
  - Data preview with pagination

### Other API Endpoints (sampled)
- Employees, departments, leave, travel orders, schedules, payroll, trainings, signatories, audit logs
- Each follows similar pattern: require db.php, switch on action, handle CRUD operations

## Security Assessment

### Strengths
1. **SQL Injection Prevention**: Consistent use of prepared statements with parameter binding
2. **Input Validation**: Required field checks, length validation, role validation
3. **Password Security**: SHA2-256 hashing (could be improved to bcrypt/argon2)
4. **Principle of Least Privilege**: RBAC via module_permissions table
5. **DIOS Module Protections**:
   - Table whitelisting
   - SQL statement blocking
   - Result set limiting
   - Execution timeouts
   - Special token authentication
6. **CORS Configuration**: Proper headers for frontend-backend communication
7. **Error Handling**: No stack traces exposed to users
8. **Audit Logging**: Login attempts logged

### Areas for Improvement
1. **Password Hashing**: Consider upgrading to bcrypt or Argon2id
2. **Rate Limiting**: No visible rate limiting on auth endpoints
3. **HTTP Headers**: Missing security headers (CSP, HSTS, X-Frame-Options)
4. **Session Management**: Appears to be token-based but tokens not examined in detail
5. **File Upload Security**: Not reviewed but important for any upload functionality
6. **Dependency Updates**: Check for outdated npm packages

## Database Schema (inferred)
Based on SQL files and queries:
- `users`: id, username, password (hashed), name, role, department, active, created_at
- `employees`, `departments`, `leave_records`, `travel_orders`, etc.
- `module_permissions`: module, role, action, granted (0/1)
- `audit_logs`: user_id, user_name, action, action_type, module, details, status
- `dtr_records`: Daily Time Records
- `payroll_records`

## Integration Points
1. **Frontend-Backend**: REST API calls from Vue components to `/server/api/*.php?action=...`
2. **Auth Flow**: Login returns user data; frontend stores user ID/role for permission checks
3. **DIOS Integration**: Frontend uses groq-sdk to call DIOS backend for AI/analytics features
4. **Permission System**: Backend checks permissions via X-USER-ID header and module_permissions table
5. **Audit Trail**: Auth and other modules log actions to audit_logs

## Development Workflow
1. Install dependencies: `npm install` in `/client`
2. Start development server: `npm run dev` in `/client`
3. Backend requires PHP server with MySQL database
4. Import schema: `geamh_hris.sql` + seed files
5. API accessible at `http://localhost:[port]/server/api/[endpoint].php?action=...`

## Potential Issues / Notes
1. **Environment Configuration**: Database credentials hardcoded in db.php (should use .env)
2. **Error Reporting**: Display errors disabled (good for prod, but may hinder debugging)
3. **Backend Framework**: Native PHP - no routing framework, each endpoint handles own routing
4. **Frontend Build**: Vite setup appears standard
5. **Testing**: PHPUnit tests exist in `/server/tests` but need to examine coverage
6. **Documentation**: Limited inline comments beyond function headers

## Recommendations for Future Development
1. **Environment Variables**: Move DB credentials to .env file
2. **API Documentation**: Consider adding OpenAPI/Swagger docs
3. **Testing**: Expand test coverage, especially for security edge cases
4. **Logging**: Implement structured logging for debugging
5. **Dependency Management**: Regularly update npm and Composer dependencies
6. **Code Consistency**: Ensure all API endpoints follow same patterns seen in auth/db/dios_control
7. **Frontend Architecture**: Consider adopting composition API patterns consistently
8. **Performance**: Consider adding caching layer for frequent queries