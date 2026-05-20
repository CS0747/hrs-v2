---
name: api-url-refactor-design
description: Design spec for refactoring hard‑coded API URLs to use a dedicated environment variable VITE_API_BASE_URL.
metadata:
  type: reference
---

# API URL Refactor Design Spec

**Date:** 2026-05-20

## Goal
Replace all hard‑coded API endpoint strings such as `http://localhost/hrs-v2/server/api/...` in the frontend code with a single environment‑derived base URL. This makes the application configurable for development, staging, and production without code changes.

## Chosen Approach
**Approach B – Introduce `VITE_API_BASE_URL`.**
- Define `VITE_API_BASE_URL` in each `.env` file (development, production, etc.).
- Update every store/composable that currently concatenates a hard‑coded URL to use `import.meta.env.VITE_API_BASE_URL`.
- Remove the fallback hard‑coded string in `client/src/utils/api.js`.
- No additional helper module is required; the base URL is concatenated directly in each file.

## Environment Configuration
Create/modify the following `.env*` files in the project root:

```text
# .env.development
VITE_API_BASE_URL=http://localhost/hrs-v2/server/api

# .env.production
VITE_API_BASE_URL=https://api.example.com/hrs-v2/server/api
```

The Vite build will inject `import.meta.env.VITE_API_BASE_URL` accordingly.

## Files to Modify
| File | Change |
|------|--------|
| `client/src/stores/auth.js` | Replace `const AUTH_API = 'http://localhost/hrs-v2/server/api/auth.php'` with ``const AUTH_API = `${import.meta.env.VITE_API_BASE_URL}/auth.php`;`` |
| `client/src/stores/…` (schedule, employees, dtr, leave, payroll, signatories, tracking, trainings, travel_orders, etc.) | Same pattern: replace hard‑coded base with the env variable and append the endpoint filename. |
| `client/src/composables/usePermissions.js` | Replace `const PERM_API = 'http://localhost/hrs-v2/server/api/module_permissions.php'` with ``const PERM_API = `${import.meta.env.VITE_API_BASE_URL}/module_permissions.php`;`` |
| `client/src/utils/api.js` | Change fallback line:
```js
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost/hrs-v2/server/api'
```
to
```js
const API_BASE = import.meta.env.VITE_API_BASE_URL
```
Remove the `||` fallback because the env variable will always be defined in each environment. |

> **Note:** The `client/src/stores/payroll.js` currently uses `http://localhost/hrs/server/api/payroll.php` (missing `-v2`). Update it to the correct base URL as well.

## Implementation Steps
1. Add the two `.env` files (or update existing ones) with `VITE_API_BASE_URL` values.
2. Run a global search for the literal string `http://localhost/hrs-v2/server/api` to locate all occurrences.
3. Replace each occurrence with the template `${import.meta.env.VITE_API_BASE_URL}` followed by the endpoint filename.
4. Update `client/src/utils/api.js` to use the new env variable and delete the fallback.
5. Run the Vite dev server (`npm run dev`) and verify that API calls succeed in development.
6. Build the production bundle (`npm run build`) and test against the production API URL.
7. Update CI/CD scripts if they previously exported a different variable name.

## Testing & Validation
- **Unit/Integration Tests:** Existing tests that mock API calls should continue to work because they rely on the store constants, which now resolve to the same URLs in the dev environment.
- **Manual Smoke Test:** Run the app locally, perform a login, fetch users, and navigate a few pages to ensure no 404s.
- **Production Verification:** Deploy a staging build with the production `.env.production` file and confirm that network requests target the correct host.

## Rollout Plan
| Phase | Action |
|-------|--------|
| 1️⃣ Development | Apply changes on a feature branch, run local tests.
| 2️⃣ Review | Submit a PR for code review.
| 3️⃣ CI | Ensure CI passes, including lint and unit tests.
| 4️⃣ Staging Deploy | Deploy to a staging environment that uses the production env file.
| 5️⃣ Verification | QA validates API connectivity.
| 6️⃣ Production Merge | Merge to `main` and trigger production release.

## Risks & Mitigations
- **Missing env variable on a new environment** – CI should fail if `VITE_API_BASE_URL` is undefined.
- **Incorrect endpoint concatenation** – Use the exact pattern `${import.meta.env.VITE_API_BASE_URL}/<endpoint>.php` and run a grep to ensure no stray hard‑coded URLs remain.

---

*Design approved when the spec file is reviewed and merged.*
