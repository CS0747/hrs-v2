<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppSelect from '@/components/AppSelect.vue'

const API = 'http://localhost/hrs-v2/server/api/dios_control.php'

const auth   = useAuthStore()
const router = useRouter()
const isDios = computed(() => auth.userRole === 'DIOS')

// ── Active tab ────────────────────────────────────────────────────────────────
const activeTab = ref('dashboard')
const tabs = [
  { id: 'dashboard', label: 'Dashboard',     icon: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>` },
  { id: 'query',     label: 'Query Runner',  icon: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>` },
  { id: 'browser',   label: 'Table Browser', icon: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 2v3H5V5h15zm-9 14H5v-9h6v9zm9 0h-7v-9h7v9z"/></svg>` },
  { id: 'access',    label: 'Module Access', icon: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>` },
]

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 1 — DASHBOARD
// ═══════════════════════════════════════════════════════════════════════════════
const statsLoading = ref(false)
const statsError   = ref('')
const stats        = ref([])
const dbSizeMb     = ref(0)

const statIcons = {
  employees:         `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  departments:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
  leave_records:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/><path d="M9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>`,
  travel_orders:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5h-9.17C6.41 5 2 9.41 2 14.83V15h20V6c0-.55-.45-1-1-1zm-8 6H5.01c1.34-2.38 3.89-4 6.82-4H13zm-8 8h16c.55 0 1-.45 1-1v-2H2c0 1.65 1.35 3 3 3z"/></svg>`,
  dtr_records:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  document_tracking: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
  audit_logs:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 9H7v-2h4v2zm4-4H7v-2h8v2zm0-4H7V8h8v2z"/></svg>`,
  trainings:         `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>`,
  signatories:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
}

async function loadStats() {
  statsLoading.value = true
  statsError.value   = ''
  try {
    const res  = await fetch(`${API}?action=stats`)
    const data = await res.json()
    stats.value    = data.stats    ?? []
    dbSizeMb.value = data.db_size_mb ?? 0
  } catch (e) {
    statsError.value = 'Failed to load stats: ' + e.message
  } finally {
    statsLoading.value = false
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 2 — QUERY RUNNER
// ═══════════════════════════════════════════════════════════════════════════════
const sql          = ref('')
const queryLoading = ref(false)
const queryResult  = ref(null)
const queryError   = ref('')

const quickQueries = [
  { label: 'All Employees',    sql: 'SELECT * FROM employees LIMIT 10' },
  { label: 'Show Tables',      sql: 'SHOW TABLES' },
  { label: 'Leave Records',    sql: 'SELECT * FROM leave_records LIMIT 10' },
  { label: 'Audit Logs',       sql: 'SELECT * FROM audit_logs ORDER BY id DESC LIMIT 20' },
  { label: 'Travel Orders',    sql: 'SELECT * FROM travel_orders LIMIT 10' },
  { label: 'DTR Records',      sql: 'SELECT * FROM dtr_records LIMIT 10' },
  { label: 'Departments',      sql: 'SELECT * FROM departments' },
  { label: 'DB Size',          sql: "SELECT table_name, ROUND((data_length+index_length)/1024/1024,2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY size_mb DESC" },
]

async function runQuery() {
  const q = sql.value.trim()
  if (!q) return
  queryLoading.value = true
  queryResult.value  = null
  queryError.value   = ''
  try {
    const res  = await fetch(`${API}?action=query`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ sql: q }),
    })
    const data = await res.json()
    if (res.status === 403) {
      queryError.value = data.error || 'Blocked query.'
    } else {
      queryResult.value = data
    }
  } catch (e) {
    queryError.value = 'Request failed: ' + e.message
  } finally {
    queryLoading.value = false
  }
}

function useQuick(q) { sql.value = q }

const queryColumns = computed(() => {
  if (!queryResult.value?.rows?.length) return []
  return Object.keys(queryResult.value.rows[0])
})

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 3 — TABLE BROWSER
// ═══════════════════════════════════════════════════════════════════════════════
const tableList      = ref([])
const tableListOpts  = computed(() => tableList.value.map(t => ({ label: t, value: t })))
const selectedTable  = ref('')
const columnsLoading = ref(false)
const tableColumns   = ref([])
const previewLoading = ref(false)
const previewRows    = ref([])
const previewTotal   = ref(0)
const previewOffset  = ref(0)
const previewLimit   = 20

async function loadTables() {
  try {
    const res  = await fetch(`${API}?action=tables`)
    const data = await res.json()
    tableList.value = data.tables ?? []
  } catch { /* silent */ }
}

async function loadTableDetail(table) {
  if (!table) return
  columnsLoading.value = true
  tableColumns.value   = []
  previewRows.value    = []
  previewTotal.value   = 0
  previewOffset.value  = 0
  try {
    const res  = await fetch(`${API}?action=describe&table=${encodeURIComponent(table)}`)
    const data = await res.json()
    tableColumns.value = data.columns ?? []
  } catch { /* silent */ } finally {
    columnsLoading.value = false
  }
  await loadPreview(table, 0)
}

async function loadPreview(table, offset) {
  previewLoading.value = true
  try {
    const res  = await fetch(`${API}?action=preview&table=${encodeURIComponent(table)}&limit=${previewLimit}&offset=${offset}`)
    const data = await res.json()
    previewRows.value   = data.rows   ?? []
    previewTotal.value  = data.total  ?? 0
    previewOffset.value = offset
  } catch { /* silent */ } finally {
    previewLoading.value = false
  }
}

function prevPage() {
  loadPreview(selectedTable.value, Math.max(0, previewOffset.value - previewLimit))
}
function nextPage() {
  const next = previewOffset.value + previewLimit
  if (next < previewTotal.value) loadPreview(selectedTable.value, next)
}

const previewColumns = computed(() => {
  if (!previewRows.value.length) return []
  return Object.keys(previewRows.value[0])
})

const currentPage = computed(() => Math.floor(previewOffset.value / previewLimit) + 1)
const totalPages  = computed(() => Math.ceil(previewTotal.value / previewLimit) || 1)

watch(selectedTable, (val) => { if (val) loadTableDetail(val) })

const tableRouteMap = {
  employees:         '/employees',
  departments:       '/departments',
  leave_records:     '/leave',
  travel_orders:     '/to',
  dtr_records:       '/dtr',
  document_tracking: '/tracking',
  audit_logs:        '/audit-trail',
  trainings:         '/trainings',
  signatories:       '/signatories',
}

function navigateToTable(tableName) {
  const route = tableRouteMap[tableName]
  if (route) router.push(route)
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 4 — MODULE ACCESS CONTROL
// ═══════════════════════════════════════════════════════════════════════════════
const roles = ['DIOS', 'Super Admin', 'Admin', 'Section Admin']

const moduleActions = {
  'Dashboard':           ['View'],
  'Employee Masterlist': ['View', 'Add', 'Edit', 'Delete', 'Export'],
  'Employee Form':       ['View', 'Add', 'Edit', 'Delete'],
  'DTR Transmittal':     ['View', 'Add', 'Edit', 'Delete', 'Verify', 'Export'],
  'Leave Management':    ['View', 'Add', 'Edit', 'Delete', 'Approve'],
  'Travel Orders':       ['View', 'Add', 'Edit', 'Delete', 'Approve'],
  'Schedule Database':   ['View', 'Add', 'Edit', 'Delete', 'Export'],
  'Trainings':           ['View', 'Add', 'Edit', 'Delete'],
  'Tracking / Receiving':['View', 'Add', 'Edit', 'Delete'],
  'Signatories':         ['View', 'Add', 'Edit', 'Delete'],
  'Audit History':       ['View', 'Export'],
  'Account Management':  ['View', 'Add', 'Edit', 'Delete'],
  'DIOS Account':        ['View', 'Add', 'Edit', 'Delete'],
  'DIOS System Control': ['View', 'Query', 'Browse'],
  'Version History':     ['View', 'Export'],
  'User Manual':         ['View'],
  'Birthday Celebrants': ['View', 'Export'],
  'AI Scanning Tools':   ['View', 'Upload', 'Delete'],
  'Departments':         ['View', 'Add', 'Edit', 'Delete'],
  'Audit Transmittal':   ['View', 'Export'],
}

function buildDefaultPerms() {
  const perms = {}
  const roleDefaults = {
    'DIOS':          { View: true,  Add: true,  Edit: true,  Delete: true,  Export: true,  Approve: true,  Verify: true,  Query: true,  Browse: true,  Upload: true  },
    'Super Admin':   { View: true,  Add: true,  Edit: true,  Delete: true,  Export: true,  Approve: true,  Verify: true,  Query: false, Browse: false, Upload: true  },
    'Admin':         { View: true,  Add: true,  Edit: true,  Delete: false, Export: true,  Approve: true,  Verify: true,  Query: false, Browse: false, Upload: true  },
    'Section Admin': { View: true,  Add: false, Edit: true,  Delete: false, Export: false, Approve: false, Verify: false, Query: false, Browse: false, Upload: false },
  }
  for (const mod of Object.keys(moduleActions)) {
    perms[mod] = {}
    for (const role of roles) {
      perms[mod][role] = {}
      for (const action of moduleActions[mod]) {
        perms[mod][role][action] = roleDefaults[role][action] ?? false
      }
    }
  }
  return perms
}

const PERM_API = 'http://localhost/hrs-v2/server/api/module_permissions.php'

async function loadPermsFromDB() {
  try {
    const res  = await fetch(PERM_API)
    const data = await res.json()
    if (data.permissions && Object.keys(data.permissions).length) {
      const defaults = buildDefaultPerms()
      for (const mod of Object.keys(defaults)) {
        if (data.permissions[mod]) {
          for (const role of roles) {
            if (data.permissions[mod][role]) {
              for (const action of Object.keys(defaults[mod][role])) {
                if (data.permissions[mod][role][action] !== undefined) {
                  defaults[mod][role][action] = data.permissions[mod][role][action]
                }
              }
            }
          }
        }
      }
      return defaults
    }
  } catch { /* fall back to defaults */ }
  return buildDefaultPerms()
}

const actionPerms = ref(buildDefaultPerms())
const selectedMod = ref(Object.keys(moduleActions)[0])
const permSaved   = ref(false)
const permChanged = ref(false)
const permLoading = ref(false)
const permError   = ref('')

async function initPerms() {
  permLoading.value = true
  actionPerms.value = await loadPermsFromDB()
  permLoading.value = false
}

const selectedActions = computed(() => moduleActions[selectedMod.value] ?? [])

function togglePerm(role, action) {
  actionPerms.value[selectedMod.value][role][action] = !actionPerms.value[selectedMod.value][role][action]
  permChanged.value = true
}

async function savePerms() {
  permError.value = ''
  try {
    const res = await fetch(PERM_API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        module:      selectedMod.value,
        permissions: actionPerms.value[selectedMod.value],
        updated_by:  'DIOS',
      }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Save failed')
    permSaved.value   = true
    permChanged.value = false
    setTimeout(() => { permSaved.value = false }, 2000)
  } catch (e) {
    permError.value = e.message
  }
}

async function resetPerms() {
  permError.value = ''
  try {
    await fetch(`${PERM_API}?module=${encodeURIComponent(selectedMod.value)}`, { method: 'DELETE' })
    const defaults = buildDefaultPerms()
    actionPerms.value[selectedMod.value] = defaults[selectedMod.value]
    await fetch(PERM_API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        module:      selectedMod.value,
        permissions: defaults[selectedMod.value],
        updated_by:  'DIOS',
      }),
    })
    permChanged.value = false
  } catch (e) {
    permError.value = e.message
  }
}

function grantedCount(mod) {
  let total = 0, granted = 0
  for (const role of roles) {
    for (const action of (moduleActions[mod] ?? [])) {
      total++
      if (actionPerms.value[mod]?.[role]?.[action]) granted++
    }
  }
  return { granted, total }
}

// ── Init ──────────────────────────────────────────────────────────────────────
const defaultIcon = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>`
const dbIcon      = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C7.58 3 4 4.79 4 7v10c0 2.21 3.58 4 8 4s8-1.79 8-4V7c0-2.21-3.58-4-8-4zm0 2c3.87 0 6 1.36 6 2s-2.13 2-6 2-6-1.36-6-2 2.13-2 6-2zm6 12c0 .64-2.13 2-6 2s-6-1.36-6-2v-2.23c1.61.78 3.72 1.23 6 1.23s4.39-.45 6-1.23V17zm0-5c0 .64-2.13 2-6 2s-6-1.36-6-2v-2.23C7.61 10.55 9.72 11 12 11s4.39-.45 6-1.23V12z"/></svg>`

onMounted(() => {
  if (!isDios.value) return
  loadStats()
  loadTables()
  initPerms()
})
</script>

<template>
  <!-- ACCESS DENIED -->
  <div v-if="!isDios" class="access-denied">
    <div class="denied-card">
      <div class="denied-icon">🔒</div>
      <h2>Access Denied</h2>
      <p>This panel is restricted to <strong>DIOS</strong> role only.</p>
      <p class="denied-sub">Your current role: <span class="role-badge">{{ auth.userRole || 'Unknown' }}</span></p>
    </div>
  </div>

  <!-- MAIN PANEL -->
  <div v-else class="dsc-page">

    <!-- Sticky tab bar -->
    <div class="dsc-tabs">
      <div class="dsc-tabs-inner">
        <div class="dsc-tabs-title">
          <h2>DIOS System Control</h2>
          <p>Database management, query execution, and system monitoring.</p>
        </div>
        <nav class="dsc-tab-nav">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="dsc-tab-btn"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <span class="dsc-tab-icon" v-html="tab.icon"></span>
            {{ tab.label }}
          </button>
        </nav>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         TAB: DASHBOARD
    ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'dashboard'" class="dsc-body dsc-body--pad">
      <div class="section-header">
        <span>System Statistics</span>
        <button class="btn btn-sm btn-secondary" @click="loadStats" :disabled="statsLoading">
          {{ statsLoading ? '⏳ Loading...' : '🔄 Refresh' }}
        </button>
      </div>

      <div v-if="statsError" class="alert alert-error">{{ statsError }}</div>

      <div v-if="statsLoading && !stats.length" class="loading-placeholder">
        <div v-for="i in 9" :key="i" class="skeleton-card"></div>
      </div>

      <div v-else class="stats-grid">
        <div
          v-for="s in stats"
          :key="s.table"
          class="stat-card stat-card--clickable"
          @click="navigateToTable(s.table)"
        >
          <div class="stat-icon" v-html="statIcons[s.table] ?? defaultIcon"></div>
          <div class="stat-info">
            <div class="stat-count">{{ s.count.toLocaleString() }}</div>
            <div class="stat-label">{{ s.label }}</div>
            <div class="stat-table">{{ s.table }}</div>
          </div>
        </div>
        <div class="stat-card stat-card--accent">
          <div class="stat-icon" v-html="dbIcon"></div>
          <div class="stat-info">
            <div class="stat-count">{{ dbSizeMb }} MB</div>
            <div class="stat-label">Database Size</div>
            <div class="stat-table">geamh_hris</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         TAB: QUERY RUNNER
    ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'query'" class="dsc-body dsc-body--pad">
      <div class="query-layout">
        <div class="query-main">
          <div class="card">
            <div class="card-title">SQL Query</div>
            <textarea
              v-model="sql"
              class="sql-textarea"
              placeholder="Enter SQL query here… e.g. SELECT * FROM employees LIMIT 10"
              spellcheck="false"
              @keydown.ctrl.enter.prevent="runQuery"
            ></textarea>
            <div class="query-actions">
              <button class="btn btn-primary" @click="runQuery" :disabled="queryLoading || !sql.trim()">
                {{ queryLoading ? '⏳ Running...' : '▶ Run Query' }}
              </button>
              <button class="btn btn-secondary" @click="sql = ''; queryResult = null; queryError = ''">
                🗑 Clear
              </button>
              <span class="query-hint">Ctrl+Enter to run</span>
            </div>
          </div>

          <div v-if="queryError" class="alert alert-error">
            <strong>⛔ Blocked or Error:</strong> {{ queryError }}
          </div>

          <div v-if="queryResult" class="card results-card">
            <div class="results-meta">
              <span v-if="queryResult.success && queryResult.rows !== undefined" class="meta-badge meta-green">
                ✅ {{ queryResult.count }} row(s) returned
              </span>
              <span v-else-if="queryResult.success" class="meta-badge meta-green">
                ✅ {{ queryResult.affected_rows }} row(s) affected
                <span v-if="queryResult.insert_id"> · Insert ID: {{ queryResult.insert_id }}</span>
              </span>
              <span v-else class="meta-badge meta-red">❌ {{ queryResult.error }}</span>
              <span class="meta-elapsed">⏱ {{ queryResult.elapsed }} ms</span>
            </div>
            <div v-if="queryResult.rows && queryResult.rows.length" class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr><th v-for="col in queryColumns" :key="col">{{ col }}</th></tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in queryResult.rows" :key="i">
                    <td v-for="col in queryColumns" :key="col">
                      <span v-if="row[col] === null" class="null-val">NULL</span>
                      <span v-else>{{ row[col] }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else-if="queryResult.success && queryResult.rows !== undefined" class="empty-result">
              No rows returned.
            </div>
          </div>
        </div>

        <div class="query-sidebar">
          <div class="card">
            <div class="card-title">Quick Queries</div>
            <div class="quick-list">
              <button
                v-for="q in quickQueries"
                :key="q.label"
                class="quick-btn"
                @click="useQuick(q.sql)"
              >{{ q.label }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         TAB: TABLE BROWSER
    ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'browser'" class="dsc-body dsc-body--pad">
      <div class="browser-toolbar">
        <AppSelect
          v-model="selectedTable"
          :options="tableListOpts"
          placeholder="Select a table..."
          style="min-width: 240px;"
        />
        <span v-if="selectedTable" class="table-badge">{{ previewTotal.toLocaleString() }} total rows</span>
      </div>

      <div v-if="!selectedTable" class="empty-state">
        <div class="empty-icon">🗄</div>
        <p>Select a table to browse its structure and data.</p>
      </div>

      <template v-else>
        <div class="card browser-card">
          <div class="card-title">Column Structure — <code>{{ selectedTable }}</code></div>
          <div v-if="columnsLoading" class="loading-text">Loading columns...</div>
          <div v-else-if="tableColumns.length" class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Field</th><th>Type</th><th>Null</th>
                  <th>Key</th><th>Default</th><th>Extra</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="col in tableColumns" :key="col.Field">
                  <td><strong>{{ col.Field }}</strong></td>
                  <td><span class="type-badge">{{ col.Type }}</span></td>
                  <td><span :class="col.Null === 'YES' ? 'badge-yes' : 'badge-no'">{{ col.Null }}</span></td>
                  <td>
                    <span v-if="col.Key" class="key-badge" :class="'key-' + col.Key.toLowerCase()">{{ col.Key }}</span>
                    <span v-else class="null-val">—</span>
                  </td>
                  <td>
                    <span v-if="col.Default !== null">{{ col.Default }}</span>
                    <span v-else class="null-val">NULL</span>
                  </td>
                  <td>
                    <span v-if="col.Extra" class="extra-badge">{{ col.Extra }}</span>
                    <span v-else class="null-val">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card browser-card">
          <div class="card-title-row">
            <div class="card-title" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">Row Preview</div>
            <div class="pagination">
              <button class="btn btn-sm btn-secondary" @click="prevPage" :disabled="previewOffset === 0 || previewLoading">← Prev</button>
              <span class="page-info">Page {{ currentPage }} / {{ totalPages }}</span>
              <button class="btn btn-sm btn-secondary" @click="nextPage" :disabled="currentPage >= totalPages || previewLoading">Next →</button>
            </div>
          </div>
          <div v-if="previewLoading" class="loading-text" style="margin-top:12px;">Loading rows...</div>
          <div v-else-if="!previewRows.length" class="empty-result">No rows in this table.</div>
          <div v-else class="table-wrapper" style="margin-top:12px;">
            <table class="data-table">
              <thead>
                <tr><th v-for="col in previewColumns" :key="col">{{ col }}</th></tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in previewRows" :key="i">
                  <td v-for="col in previewColumns" :key="col">
                    <span v-if="row[col] === null" class="null-val">NULL</span>
                    <span v-else class="cell-val">{{ row[col] }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         TAB: MODULE ACCESS
    ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'access'" class="dsc-body dsc-body--access">

      <!-- Left: fixed module list -->
      <aside class="dsc-modules">
        <div class="dsc-modules-header">Modules</div>
        <nav class="dsc-modules-nav">
          <button
            v-for="mod in Object.keys(moduleActions)"
            :key="mod"
            class="dsc-module-item"
            :class="{ active: selectedMod === mod }"
            @click="selectedMod = mod"
          >
            <span class="dsc-module-name">{{ mod }}</span>
            <span class="dsc-module-badge">
              {{ grantedCount(mod).granted }}/{{ grantedCount(mod).total }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- Right: permission grid -->
      <main class="dsc-content">

        <!-- Sticky header: module name + breadcrumb + actions -->
        <div class="dsc-perm-header">
          <div class="dsc-perm-breadcrumb">Module Access &rsaquo; {{ selectedMod }}</div>
          <div class="dsc-perm-title-row">
            <div>
              <div class="dsc-perm-title">{{ selectedMod }}</div>
              <div class="dsc-perm-sub">Configure allowed actions per role</div>
            </div>
            <div class="dsc-perm-actions">
              <span v-if="permChanged" class="unsaved-badge">● Unsaved</span>
              <button class="btn btn-sm btn-secondary" @click="resetPerms" :disabled="permLoading">↺ Reset</button>
              <button class="btn btn-sm btn-primary" @click="savePerms" :disabled="!permChanged || permLoading">
                {{ permSaved ? '✓ Saved' : '💾 Save' }}
              </button>
            </div>
          </div>
          <div v-if="permError" class="alert alert-error" style="margin-top:10px;">{{ permError }}</div>
        </div>

        <!-- Sticky column headers -->
        <div class="dsc-perm-roles">
          <div class="dsc-col-action">Action</div>
          <div v-for="role in roles" :key="role" class="dsc-col-role">{{ role }}</div>
        </div>

        <!-- Scrollable action rows -->
        <div class="dsc-perm-rows">
          <div v-if="permLoading" class="loading-text" style="padding:24px 20px;">Loading permissions...</div>
          <template v-else>
            <div
              v-for="action in selectedActions"
              :key="action"
              class="dsc-perm-row"
            >
              <div class="dsc-col-action">
                <span class="action-pill" :class="'action-' + action.toLowerCase()">{{ action }}</span>
              </div>
              <div v-for="role in roles" :key="role" class="dsc-col-role">
                <button
                  class="perm-toggle"
                  :class="actionPerms[selectedMod]?.[role]?.[action] ? 'perm-on' : 'perm-off'"
                  @click="togglePerm(role, action)"
                  :title="(actionPerms[selectedMod]?.[role]?.[action] ? 'Revoke' : 'Grant') + ' ' + action + ' for ' + role"
                >
                  <svg v-if="actionPerms[selectedMod]?.[role]?.[action]" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                  </svg>
                </button>
              </div>
            </div>
          </template>
        </div>

      </main>
    </div>

  </div>
</template>

<style scoped>
/* ─────────────────────────────────────────────────────────────────────────────
   PAGE SHELL
───────────────────────────────────────────────────────────────────────────── */
.dsc-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f4f6fb;
  overflow: hidden;
}

/* ─────────────────────────────────────────────────────────────────────────────
   STICKY TAB BAR  (sits just below the AppHeader ~60px)
───────────────────────────────────────────────────────────────────────────── */
.dsc-tabs {
  position: sticky;
  top: 0;
  z-index: 100;
  flex-shrink: 0;
  background: linear-gradient(135deg, #0f1c2e 0%, #1a2f4a 60%, #1e3a5f 100%);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
}
.dsc-tabs-inner {
  padding: 20px 32px 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.dsc-tabs-title h2 {
  margin: 0 0 3px;
  color: #fff;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.3px;
}
.dsc-tabs-title p {
  margin: 0 0 16px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
}
.dsc-tab-nav {
  display: flex;
  gap: 0;
}
.dsc-tab-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 0;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
  background: transparent;
  transition: color 0.15s, background 0.15s, border-color 0.15s;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-bottom: 3px solid transparent;
}
.dsc-tab-btn:hover { color: rgba(255, 255, 255, 0.9); background: rgba(255, 255, 255, 0.08); }
.dsc-tab-btn.active { color: #fff; border-bottom-color: #ffd700; background: rgba(255, 255, 255, 0.1); }
.dsc-tab-icon { width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dsc-tab-icon :deep(svg) { width: 16px; height: 16px; fill: currentColor; }

/* ─────────────────────────────────────────────────────────────────────────────
   BODY VARIANTS
───────────────────────────────────────────────────────────────────────────── */
/* Padded body for dashboard / query / browser tabs */
.dsc-body--pad {
  flex: 1;
  overflow-y: auto;
  padding: 24px 32px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Two-column body for the access tab */
.dsc-body--access {
  flex: 1;
  display: flex;
  overflow: hidden;
}

/* ─────────────────────────────────────────────────────────────────────────────
   MODULE ACCESS — LEFT PANEL (fixed-width, never scrolls with page)
───────────────────────────────────────────────────────────────────────────── */
.dsc-modules {
  width: 230px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
  border-right: 1px solid #e8ecf4;
  overflow: hidden;
}
.dsc-modules-header {
  padding: 12px 16px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #fff;
  background: #1a3a5c;
  flex-shrink: 0;
}
.dsc-modules-nav {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.dsc-modules-nav::-webkit-scrollbar { width: 4px; }
.dsc-modules-nav::-webkit-scrollbar-thumb { background: #c8d0e0; border-radius: 2px; }

.dsc-module-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 12.5px;
  color: #444;
  font-weight: 500;
  text-align: left;
  border-left: 3px solid transparent;
  border-bottom: 1px solid #f8f9fc;
  transition: background 0.12s, color 0.12s, border-color 0.12s;
  flex-shrink: 0;
}
.dsc-module-item:hover { background: #f4f6fb; color: #1a3a5c; }
.dsc-module-item.active {
  background: #e8f0fe;
  color: #1a3a5c;
  font-weight: 700;
  border-left-color: #1a3a5c;
}
.dsc-module-name { flex: 1; }
.dsc-module-badge {
  font-size: 10px;
  background: #f0f2f7;
  color: #888;
  padding: 2px 7px;
  border-radius: 10px;
  font-weight: 600;
  flex-shrink: 0;
}
.dsc-module-item.active .dsc-module-badge { background: #1a3a5c; color: #fff; }

/* ─────────────────────────────────────────────────────────────────────────────
   MODULE ACCESS — RIGHT PANEL (scrollable)
───────────────────────────────────────────────────────────────────────────── */
.dsc-content {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  scroll-behavior: smooth;
}
.dsc-content::-webkit-scrollbar { width: 6px; }
.dsc-content::-webkit-scrollbar-thumb { background: #c8d0e0; border-radius: 3px; }

/* Sticky permission header (module name + save/reset) */
.dsc-perm-header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #f8f9fc;
  border-bottom: 1px solid #e8ecf4;
  padding: 14px 24px;
  flex-shrink: 0;
}
.dsc-perm-breadcrumb {
  font-size: 11px;
  color: #b0b8cc;
  margin-bottom: 6px;
  letter-spacing: 0.3px;
}
.dsc-perm-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}
.dsc-perm-title { font-size: 15px; font-weight: 700; color: #1a3a5c; }
.dsc-perm-sub   { font-size: 12px; color: #888; margin-top: 2px; }
.dsc-perm-actions { display: flex; align-items: center; gap: 8px; }
.unsaved-badge { font-size: 12px; color: #e67e22; font-weight: 600; }

/* Sticky role column headers */
.dsc-perm-roles {
  position: sticky;
  top: 0;
  z-index: 9;
  display: grid;
  grid-template-columns: 140px repeat(4, 1fr);
  gap: 8px;
  padding: 10px 24px;
  background: #f0f4f8;
  border-bottom: 2px solid #dde3ef;
  flex-shrink: 0;
}

/* Scrollable action rows container */
.dsc-perm-rows {
  flex: 1;
  padding: 0 24px 24px;
}

/* Shared grid columns for roles and rows */
.dsc-col-action {
  font-size: 12px;
  font-weight: 700;
  color: #1a3a5c;
  display: flex;
  align-items: center;
}
.dsc-col-role {
  text-align: center;
  font-size: 11px;
  font-weight: 800;
  color: #1a3a5c;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Individual action row */
.dsc-perm-row {
  display: grid;
  grid-template-columns: 140px repeat(4, 1fr);
  gap: 8px;
  padding: 10px 0;
  border-bottom: 1px solid #f4f6fb;
  align-items: center;
}
.dsc-perm-row:last-child { border-bottom: none; }

/* ─────────────────────────────────────────────────────────────────────────────
   SHARED COMPONENTS
───────────────────────────────────────────────────────────────────────────── */
.card {
  background: #fff;
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
}
.card-title {
  font-size: 13px;
  font-weight: 700;
  color: #1a3a5c;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f7;
}
.card-title code {
  font-size: 12px;
  color: #1a6b3c;
  background: #f0f9f4;
  padding: 1px 6px;
  border-radius: 4px;
  text-transform: none;
  letter-spacing: 0;
}
.card-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f7;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 14px;
  font-weight: 700;
  color: #1a3a5c;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background 0.15s, transform 0.1s;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary  { background: #1a6b3c; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #27ae60; }
.btn-secondary { background: #f0f2f7; color: #1a3a5c; border: 1px solid #e2e6ef; }
.btn-secondary:hover:not(:disabled) { background: #e4e9f4; }
.btn-sm { padding: 5px 12px; font-size: 12px; }

.alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; }
.alert-error { background: #fdecea; color: #c0392b; border: 1px solid #f5b7b1; }

/* ─────────────────────────────────────────────────────────────────────────────
   DASHBOARD — STATS
───────────────────────────────────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}
.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid #f0f2f7;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1); border-color: #d8dff0; }
.stat-card--clickable { cursor: pointer; }
.stat-card--clickable:hover { border-color: #1a3a5c; }
.stat-card--clickable:hover .stat-label::after { content: ' →'; color: #1a3a5c; font-weight: 700; }
.stat-icon {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}
.stat-icon :deep(svg) { width: 24px; height: 24px; }
.stat-card:nth-child(1) .stat-icon { background: #e8f0fe; }
.stat-card:nth-child(1) .stat-icon :deep(svg) { fill: #1a73e8; }
.stat-card:nth-child(2) .stat-icon { background: #fce8e6; }
.stat-card:nth-child(2) .stat-icon :deep(svg) { fill: #d93025; }
.stat-card:nth-child(3) .stat-icon { background: #e6f4ea; }
.stat-card:nth-child(3) .stat-icon :deep(svg) { fill: #1e8e3e; }
.stat-card:nth-child(4) .stat-icon { background: #fef7e0; }
.stat-card:nth-child(4) .stat-icon :deep(svg) { fill: #f9ab00; }
.stat-card:nth-child(5) .stat-icon { background: #f3e8fd; }
.stat-card:nth-child(5) .stat-icon :deep(svg) { fill: #8430ce; }
.stat-card:nth-child(6) .stat-icon { background: #e8f5f9; }
.stat-card:nth-child(6) .stat-icon :deep(svg) { fill: #0097a7; }
.stat-card:nth-child(7) .stat-icon { background: #fff3e0; }
.stat-card:nth-child(7) .stat-icon :deep(svg) { fill: #e65100; }
.stat-card:nth-child(8) .stat-icon { background: #e8f0fe; }
.stat-card:nth-child(8) .stat-icon :deep(svg) { fill: #3949ab; }
.stat-card:nth-child(9) .stat-icon { background: #fce8e6; }
.stat-card:nth-child(9) .stat-icon :deep(svg) { fill: #c62828; }
.stat-card--accent .stat-icon { background: #e6f4ea; }
.stat-card--accent .stat-icon :deep(svg) { fill: #1e8e3e; }
.stat-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.stat-count { font-size: 26px; font-weight: 800; color: #1a3a5c; line-height: 1; }
.stat-label { font-size: 13px; font-weight: 600; color: #444; margin-top: 3px; }
.stat-table { font-size: 11px; color: #b0b8cc; font-family: monospace; margin-top: 1px; }

.loading-placeholder { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.skeleton-card {
  height: 96px;
  border-radius: 14px;
  background: linear-gradient(90deg, #f0f2f7 25%, #e8ecf4 50%, #f0f2f7 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ─────────────────────────────────────────────────────────────────────────────
   QUERY RUNNER
───────────────────────────────────────────────────────────────────────────── */
.query-layout {
  display: grid;
  grid-template-columns: 1fr 220px;
  gap: 16px;
  align-items: start;
}
@media (max-width: 768px) { .query-layout { grid-template-columns: 1fr; } }

.query-main { display: flex; flex-direction: column; gap: 16px; }

.sql-textarea {
  width: 100%;
  min-height: 150px;
  padding: 14px;
  border: 1px solid #e2e6ef;
  border-radius: 10px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  color: #1a1a2e;
  resize: vertical;
  outline: none;
  background: #f8f9fc;
  box-sizing: border-box;
  line-height: 1.7;
  transition: border-color 0.2s, background 0.2s;
}
.sql-textarea:focus { border-color: #1a6b3c; background: #fff; box-shadow: 0 0 0 3px rgba(26, 107, 60, 0.08); }

.query-actions { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.query-hint { font-size: 11px; color: #b0b8cc; margin-left: auto; }

.results-card { overflow: hidden; }
.results-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f7;
}
.meta-badge { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.meta-green { background: #e6f4ea; color: #1e8e3e; }
.meta-red   { background: #fdecea; color: #c0392b; }
.meta-elapsed { font-size: 12px; color: #b0b8cc; margin-left: auto; }

.quick-list { display: flex; flex-direction: column; gap: 6px; }
.quick-btn {
  width: 100%;
  text-align: left;
  padding: 9px 12px;
  border: 1px solid #e8ecf4;
  border-radius: 8px;
  background: #f8f9fc;
  font-size: 12px;
  color: #1a3a5c;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.quick-btn:hover { background: #f0f9f4; border-color: #1a6b3c; color: #1a6b3c; }

/* ─────────────────────────────────────────────────────────────────────────────
   TABLE BROWSER
───────────────────────────────────────────────────────────────────────────── */
.browser-toolbar { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.table-badge { background: #e8f0fe; color: #1a73e8; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.browser-card { overflow: hidden; }

.empty-state { text-align: center; padding: 60px 20px; color: #b0b8cc; }
.empty-icon { font-size: 48px; margin-bottom: 12px; }
.empty-state p { font-size: 14px; }

.pagination { display: flex; align-items: center; gap: 10px; }
.page-info { font-size: 12px; color: #888; white-space: nowrap; }

/* ─────────────────────────────────────────────────────────────────────────────
   SHARED TABLE STYLES
───────────────────────────────────────────────────────────────────────────── */
.table-wrapper {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 380px;
  border-radius: 10px;
  border: 1px solid #f0f2f7;
}
.table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
.table-wrapper::-webkit-scrollbar-track { background: #f4f6fb; }
.table-wrapper::-webkit-scrollbar-thumb { background: #c8d0e0; border-radius: 3px; }
.table-wrapper::-webkit-scrollbar-thumb:hover { background: #a0aec0; }

.data-table { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #1a3a5c;
  padding: 10px 14px;
  text-align: left;
  font-weight: 600;
  white-space: nowrap;
}
.data-table td {
  padding: 8px 14px;
  border-bottom: 1px solid #f4f6fb;
  vertical-align: middle;
  white-space: nowrap;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover { background: #f8f9fc; }

.null-val  { color: #c8d0e0; font-style: italic; font-size: 11px; }
.cell-val  { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.type-badge  { background: #f0f2f7; color: #1a3a5c; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-family: monospace; }
.badge-yes   { color: #b0b8cc; font-size: 11px; }
.badge-no    { color: #c0392b; font-size: 11px; font-weight: 700; }
.key-badge   { padding: 2px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; }
.key-pri     { background: #fff3cd; color: #856404; }
.key-uni     { background: #d1ecf1; color: #0c5460; }
.key-mul     { background: #e2e3e5; color: #383d41; }
.extra-badge { background: #e6f4ea; color: #1e8e3e; padding: 2px 8px; border-radius: 5px; font-size: 10px; font-weight: 600; }

/* ─────────────────────────────────────────────────────────────────────────────
   ACTION PILLS
───────────────────────────────────────────────────────────────────────────── */
.action-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}
.action-view   { background: #e8f0fe; color: #1a73e8; }
.action-add    { background: #e6f4ea; color: #1e8e3e; }
.action-edit   { background: #fef7e0; color: #f9ab00; }
.action-delete { background: #fdecea; color: #c0392b; }
.action-export { background: #f3e8fd; color: #8430ce; }
.action-approve{ background: #e8f5f9; color: #0097a7; }
.action-verify { background: #fff3e0; color: #e65100; }
.action-query  { background: #e8f0fe; color: #3949ab; }
.action-browse { background: #f0f2f7; color: #555; }
.action-upload { background: #fce8e6; color: #d93025; }

/* ─────────────────────────────────────────────────────────────────────────────
   PERMISSION TOGGLE BUTTONS
───────────────────────────────────────────────────────────────────────────── */
.perm-toggle {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 2px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, border-color 0.15s, transform 0.1s;
  margin: 0 auto;
}
.perm-toggle svg { display: block; }
.perm-on  { background: #e6f4ea; color: #1e8e3e; border-color: #a8d5b5; }
.perm-on:hover  { background: #c8ecd2; border-color: #1e8e3e; transform: scale(1.1); }
.perm-off { background: #fdecea; color: #c0392b; border-color: #f5b7b1; }
.perm-off:hover { background: #fad4d1; border-color: #c0392b; transform: scale(1.1); }

/* ─────────────────────────────────────────────────────────────────────────────
   ACCESS DENIED
───────────────────────────────────────────────────────────────────────────── */
.access-denied {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  padding: 24px;
}
.denied-card {
  background: #fff;
  border-radius: 16px;
  padding: 48px 40px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  text-align: center;
  max-width: 400px;
}
.denied-icon { font-size: 56px; margin-bottom: 16px; }
.denied-card h2 { margin: 0 0 8px; color: #1a3a5c; font-size: 22px; }
.denied-card p  { margin: 0 0 8px; color: #555; font-size: 14px; }
.denied-sub { color: #888; font-size: 13px; }
.role-badge { background: #fdecea; color: #c0392b; padding: 2px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; }

/* ─────────────────────────────────────────────────────────────────────────────
   MISC
───────────────────────────────────────────────────────────────────────────── */
.empty-result { text-align: center; color: #b0b8cc; padding: 28px; font-size: 13px; }
.loading-text { color: #b0b8cc; font-size: 13px; padding: 12px 0; }

/* Tablet responsive */
@media (max-width: 900px) {
  .dsc-body--access { flex-direction: column; overflow-y: auto; }
  .dsc-modules { width: 100%; flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #e8ecf4; }
  .dsc-modules-nav { flex-direction: row; overflow-y: visible; overflow-x: auto; }
  .dsc-module-item { flex-shrink: 0; border-left: none; border-bottom: 3px solid transparent; }
  .dsc-module-item.active { border-left-color: transparent; border-bottom-color: #1a3a5c; }
  .dsc-content { overflow-y: visible; }
  .dsc-perm-header,
  .dsc-perm-roles { position: static; }
}
</style>
