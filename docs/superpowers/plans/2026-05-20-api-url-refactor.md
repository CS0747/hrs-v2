---
name: api-url-refactor-plan
description: Implementation plan for replacing hard‑coded API URLs with VITE_API_BASE_URL.
metadata:
  type: reference
---

# API URL Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB‑SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task‑by‑task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace every hard‑coded API endpoint string in the Vue frontend with a single environment‑derived base URL (`VITE_API_BASE_URL`).

**Architecture:** The frontend will read `import.meta.env.VITE_API_BASE_URL` (provided via Vite `.env` files) and concatenate the endpoint filename. All stores/composables will reference this variable, removing duplicated literals and enabling proper production configuration.

**Tech Stack:** Vue 3, Pinia stores, Vite, JavaScript/TypeScript, Git.

---

### Task 1: Add/Update environment variable in `.env` files

**Files:**
- Create/modify `.env.development`
- Create/modify `.env.production`

- [ ] **Step 1:** Open `.env.development` and add:
  ```text
  VITE_API_BASE_URL=http://localhost/hrs-v2/server/api
  ```
- [ ] **Step 2:** Open `.env.production` and add (or update) the production URL:
  ```text
  VITE_API_BASE_URL=https://api.example.com/hrs-v2/server/api
  ```
- [ ] **Step 3:** Run the dev server (`npm run dev`) to verify Vite picks up the variable (no build errors).
- [ ] **Step 4:** Commit the env changes.
  ```bash
  git add .env.development .env.production
  git commit -m "chore: add VITE_API_BASE_URL to env files"
  ```

### Task 2: Update `client/src/utils/api.js` to use the new variable

**Files:**
- Modify `client/src/utils/api.js`

- [ ] **Step 1:** Replace the fallback line:
  ```js
  const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost/hrs-v2/server/api'
  ```
  with:
  ```js
  const API_BASE = import.meta.env.VITE_API_BASE_URL
  ```
- [ ] **Step 2:** Save the file and run the app (`npm run dev`) to ensure no runtime errors.
- [ ] **Step 3:** Commit the change.
  ```bash
  git add client/src/utils/api.js
  git commit -m "refactor: use VITE_API_BASE_URL in utils/api.js"
  ```

### Task 3: Refactor `auth.js` store

**Files:**
- Modify `client/src/stores/auth.js`

- [ ] **Step 1:** Replace the hard‑coded constants:
  ```js
  const AUTH_API = 'http://localhost/hrs-v2/server/api/auth.php'
  const AUDIT_API = 'http://localhost/hrs-v2/server/api/audit_logs.php'
  ```
  with:
  ```js
  const AUTH_API = `${import.meta.env.VITE_API_BASE_URL}/auth.php`
  const AUDIT_API = `${import.meta.env.VITE_API_BASE_URL}/audit_logs.php`
  ```
- [ ] **Step 2:** Run the dev server and perform a login to confirm the request hits the correct URL (check network tab).
- [ ] **Step 3:** Commit the change.
  ```bash
  git add client/src/stores/auth.js
  git commit -m "refactor: use env base URL in auth store"
  ```

### Task 4: Refactor `usePermissions.js` composable

**Files:**
- Modify `client/src/composables/usePermissions.js`

- [ ] **Step 1:** Replace
  ```js
  const PERM_API = 'http://localhost/hrs-v2/server/api/module_permissions.php'
  ```
  with
  ```js
  const PERM_API = `${import.meta.env.VITE_API_BASE_URL}/module_permissions.php`
  ```
- [ ] **Step 2:** Verify permission fetching still works (run the app, open a page that checks permissions).
- [ ] **Step 3:** Commit.
  ```bash
  git add client/src/composables/usePermissions.js
  git commit -m "refactor: use env base URL in permissions composable"
  ```

### Task 5: Refactor each store that defines its own `API` constant

The following stores have a similar pattern (`const API = 'http://localhost/hrs‑v2/server/api/<file>.php'`). For each, replace the literal with the env‑derived URL.

| Store File | Replacement |
|------------|------------|
| `client/src/stores/dtr.js` | `const API = `${import.meta.env.VITE_API_BASE_URL}/dtr.php`` |
| `client/src/stores/employees.js` | `const API = `${import.meta.env.VITE_API_BASE_URL}/employees.php`` |
| `client/src/stores/payroll.js` | `const API_URL = `${import.meta.env.VITE_API_BASE_URL}/payroll.php`` |
| `client/src/stores/schedule.js` | `const API = `${import.meta.env.VITE_API_BASE_URL}/schedule.php`` |
| `client/src/stores/signatories.js` | `const API_URL = `${import.meta.env.VITE_API_BASE_URL}/signatories.php`` |
| `client/src/stores/tracking.js` | `const API_URL = `${import.meta.env.VITE_API_BASE_URL}/tracking.php`` |
| `client/src/stores/trainings.js` | `const API = `${import.meta.env.VITE_API_BASE_URL}/trainings.php`` |
| `client/src/stores/leave.js` | `const API_URL = `${import.meta.env.VITE_API_BASE_URL}/leave.php`` |
| `client/src/stores/travel_orders.js` | `const API_URL = `${import.meta.env.VITE_API_BASE_URL}/travel_orders.php`` |

For each file:
- [ ] Open the file.
- [ ] Replace the hard‑coded URL with the template string shown above.
- [ ] Run the dev server and navigate to a view that uses the store to ensure data loads correctly.
- [ ] Stage and commit the file, e.g.:
  ```bash
  git add client/src/stores/dtr.js
  git commit -m "refactor: use VITE_API_BASE_URL in dtr store"
  ```

### Task 6: Verify project builds and runs in production mode

- [ ] **Step 1:** Run a production build: `npm run build`.
- [ ] **Step 2:** Serve the built files locally (e.g., `npx serve dist`).
- [ ] **Step 3:** Open the app, perform a few actions that trigger API calls (login, view employees, submit a leave request) and confirm the network requests target the correct host.
- [ ] **Step 4:** Commit any build‑related adjustments (e.g., CI script changes if they referenced the old env variable).
  ```bash
  git add .github/workflows/*.yml   # if needed
  git commit -m "ci: update build scripts to use VITE_API_BASE_URL"
  ```

### Task 7: Clean‑up and finalization

- [ ] **Step 1:** Search the repo for any remaining occurrences of `http://localhost/hrs-v2/server/api` to guarantee all were replaced.
  ```bash
  grep -R "http://localhost/hrs-v2/server/api" client/src || true
  ```
- [ ] **Step 2:** If any stray literals appear (e.g., in comments or tests), update or remove them.
- [ ] **Step 3:** Run the full test suite (`npm test` or `npm run test:unit`) to ensure nothing broke.
- [ ] **Step 4:** Push the feature branch and open a PR.
  ```bash
  git push -u origin feature/api-url-refactor
  gh pr create --title "refactor: replace hard‑coded API URLs with VITE_API_BASE_URL" \
    --body "## Summary\n- Centralize API base URL in environment variable\n- Update all stores/composables\n- Remove fallback hard‑coded string\n- Add env files for dev and prod\n\n## Test plan\n- Run dev server, perform login and data fetches\n- Build production bundle and verify URLs\n- Full unit test run passes"
  ```

---

**Self‑Review Checklist**
1. Spec coverage: Every file listed in the design spec has a corresponding task.
2. No placeholders: All steps contain concrete code snippets, commands, and expected outcomes.
3. Type/Name consistency: The variable `VITE_API_BASE_URL` is used uniformly.

If any item is missing, add a new task before proceeding.

---

**Plan execution options**
- **Subagent‑Driven (recommended)** – dispatch a fresh subagent for each task, review results, and iterate quickly.
- **Inline Execution** – execute tasks directly in this session.

Which approach would you like to use?