<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppModal from '@/components/AppModal.vue'

const auth = useAuthStore()
const AUDIT_API = 'http://localhost/hrs-v2/server/api/audit_logs.php'

// ── State ─────────────────────────────────────────────────────────────────────
const logs    = ref([])
const loading = ref(false)
const error   = ref(null)

const search      = ref('')
const filterModule = ref('')
const filterAction = ref('')
const filterDate   = ref('')

// Modal states
const showSnapshot    = ref(false)
const snapshotData    = ref(null)
const showAllVersions = ref(false)
const allVersionsKey  = ref(null)   // { module, recordId, label }

const MODULES = ['Employee', 'Leave', 'Payroll', 'Schedule', 'Training', 'DTR', 'T.O.', 'Tracking', 'Signatory', 'Department', 'Account']
const ACTIONS = ['CREATE', 'UPDATE', 'DELETE']

// ── Fetch ─────────────────────────────────────────────────────────────────────
async function fetchLogs() {
  loading.value = true
  error.value   = null
  try {
    const params = new URLSearchParams({ limit: '1000' })
    if (filterModule.value) params.set('module', filterModule.value)
    if (filterAction.value) params.set('action_type', filterAction.value)
    if (filterDate.value)   params.set('from', filterDate.value), params.set('to', filterDate.value)
    if (search.value)       params.set('search', search.value)

    const res  = await fetch(`${AUDIT_API}?${params}`)
    if (!res.ok) throw new Error('Failed to fetch logs')
    const rows = await res.json()
    // Only show CREATE / UPDATE / DELETE — exclude LOGIN/LOGOUT/VIEW/etc.
    logs.value = rows.filter(r => ['CREATE','UPDATE','DELETE'].includes(r.action_type))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchLogs)

// ── Filtered (client-side search on top of server filter) ─────────────────────
const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return logs.value.filter(r => {
    if (!q) return true
    return (
      (r.user_name  ?? '').toLowerCase().includes(q) ||
      (r.action     ?? '').toLowerCase().includes(q) ||
      (r.details    ?? '').toLowerCase().includes(q) ||
      (r.module     ?? '').toLowerCase().includes(q)
    )
  })
})

// ── All versions for a specific record ────────────────────────────────────────
const recordVersions = computed(() => {
  if (!allVersionsKey.value) return []
  const { module, recordId } = allVersionsKey.value
  return logs.value.filter(r =>
    r.module === module &&
    (recordId ? String(r.record_id) === String(recordId) : true)
  )
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function actionBadgeClass(type) {
  return { CREATE: 'type-added', UPDATE: 'type-edited', DELETE: 'type-deleted' }[type] || 'type-other'
}

function actionLabel(type) {
  return { CREATE: '➕ Added', UPDATE: '✏️ Updated', DELETE: '🗑 Deleted' }[type] || type
}

function moduleBadgeClass(mod) {
  const map = {
    Employee: 'mod-employee', Leave: 'mod-leave', Payroll: 'mod-payroll',
    Schedule: 'mod-schedule', Training: 'mod-training', DTR: 'mod-dtr',
    'T.O.': 'mod-to', Tracking: 'mod-tracking', Signatory: 'mod-signatory',
    Auth: 'mod-auth',
  }
  return map[mod] || 'mod-other'
}

function formatDate(ts) {
  if (!ts) return '—'
  const d = new Date(ts)
  if (isNaN(d)) return ts
  const mm   = String(d.getMonth() + 1).padStart(2, '0')
  const dd   = String(d.getDate()).padStart(2, '0')
  const yyyy = d.getFullYear()
  const hh   = String(d.getHours() % 12 || 12).padStart(2, '0')
  const min  = String(d.getMinutes()).padStart(2, '0')
  const sec  = String(d.getSeconds()).padStart(2, '0')
  const ampm = d.getHours() < 12 ? 'AM' : 'PM'
  return `${mm}/${dd}/${yyyy}, ${hh}:${min}:${sec} ${ampm}`
}

function viewSnapshot(entry) {
  snapshotData.value = entry
  showSnapshot.value = true
}

function openAllVersions(entry) {
  allVersionsKey.value = {
    module:   entry.module,
    recordId: entry.record_id,
    label:    entry.details ?? entry.action,
  }
  showAllVersions.value = true
}

const showClearModal = ref(false)

async function clearHistory() {
  showClearModal.value = true
}
async function confirmClear() {
  showClearModal.value = false
  // Archive all shown logs
  await Promise.all(
    filtered.value.map(r =>
      fetch(`${AUDIT_API}?id=${r.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ archived: 1 }),
      })
    )
  )
  await fetchLogs()
}

function renderJson(val) {
  if (!val) return '—'
  if (typeof val === 'string') {
    try { val = JSON.parse(val) } catch { return val }
  }
  return Object.entries(val)
    .filter(([, v]) => v !== null && v !== '' && v !== undefined)
    .map(([k, v]) => `${k}: ${v}`)
    .join('\n')
}

const svgClose   = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`
const svgHistory = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>`
const svgRefresh = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35A7.958 7.958 0 0 0 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0 1 12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>`
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h2>Version History</h2>
        <p>Track all add, update, and delete actions across all modules.</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-refresh" @click="fetchLogs" :disabled="loading">
          <span v-html="svgRefresh" class="icon-svg"></span>
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
        <button class="btn btn-danger" @click="clearHistory" :disabled="loading">🗑 Clear History</button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-left">
        <input v-model="search" class="search-input" placeholder="Search action, user, details..." @keyup.enter="fetchLogs" />

        <!-- Module dropdown -->
        <select v-model="filterModule" class="filter-select" @change="fetchLogs">
          <option value="" selected>All Modules</option>
          <option v-for="m in MODULES" :key="m" :value="m">{{ m }}</option>
        </select>

        <!-- Action type dropdown -->
        <select v-model="filterAction" class="filter-select" @change="fetchLogs">
          <option value="" selected>All Actions</option>
          <option value="CREATE">➕ Added</option>
          <option value="UPDATE">✏️ Updated</option>
          <option value="DELETE">🗑 Deleted</option>
        </select>

        <!-- Date filter -->
        <input v-model="filterDate" type="date" class="filter-select" title="Filter by date" @change="fetchLogs" />
        <button v-if="filterDate" class="btn-clear-date" @click="filterDate = ''; fetchLogs()" title="Clear date">✕</button>
      </div>
      <span class="record-count">{{ filtered.length }} record(s)</span>
    </div>

    <!-- Error -->
    <div v-if="error" class="error-banner">⚠ {{ error }} — showing cached data if available.</div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Action</th>
            <th>Module</th>
            <th>Record / Details</th>
            <th>By</th>
            <th>Date &amp; Time</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="empty-row">Loading...</td>
          </tr>
          <tr v-else-if="filtered.length === 0">
            <td colspan="6" class="empty-row">
              No version history yet. Actions across all modules will appear here.
            </td>
          </tr>
          <tr v-for="entry in filtered" :key="entry.id">
            <td>
              <span class="type-badge" :class="actionBadgeClass(entry.action_type)">
                {{ actionLabel(entry.action_type) }}
              </span>
            </td>
            <td>
              <span class="module-badge" :class="moduleBadgeClass(entry.module)">
                {{ entry.module }}
              </span>
            </td>
            <td class="details-col">{{ entry.details }}</td>
            <td>{{ entry.user_name }}</td>
            <td class="ts-col">{{ formatDate(entry.created_at) }}</td>
            <td>
              <div class="action-btns">
                <button class="btn-view" @click="viewSnapshot(entry)">👁 View</button>
                <button class="btn-versions" @click="openAllVersions(entry)" title="All versions for this record">
                  <span v-html="svgHistory" class="icon-svg"></span> History
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Snapshot Modal ──────────────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showSnapshot" class="modal-overlay" @click.self="showSnapshot = false">
        <div class="modal">
          <div class="modal-header">
            <div>
              <div class="modal-badges">
                <span class="type-badge" :class="actionBadgeClass(snapshotData?.action_type)">
                  {{ actionLabel(snapshotData?.action_type) }}
                </span>
                <span class="module-badge" :class="moduleBadgeClass(snapshotData?.module)">
                  {{ snapshotData?.module }}
                </span>
              </div>
              <h3>{{ snapshotData?.details }}</h3>
              <p class="snap-meta">
                🕐 {{ formatDate(snapshotData?.created_at) }} &nbsp;·&nbsp; 👤 {{ snapshotData?.user_name }}
              </p>
            </div>
            <button class="btn-icon" @click="showSnapshot = false" v-html="svgClose"></button>
          </div>

          <!-- Before / After comparison -->
          <div class="snap-compare" v-if="snapshotData?.old_values || snapshotData?.new_values">
            <div class="snap-col" v-if="snapshotData?.old_values">
              <div class="snap-col-label before">Before</div>
              <pre class="snap-pre">{{ renderJson(snapshotData.old_values) }}</pre>
            </div>
            <div class="snap-col" v-if="snapshotData?.new_values">
              <div class="snap-col-label after">After</div>
              <pre class="snap-pre">{{ renderJson(snapshotData.new_values) }}</pre>
            </div>
          </div>
          <div v-else class="snap-no-data">No snapshot data available for this entry.</div>

          <button class="btn btn-primary close-btn" @click="showSnapshot = false">Close</button>
        </div>
      </div>
    </Transition>

    <!-- ── All Versions Modal ──────────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showAllVersions" class="modal-overlay" @click.self="showAllVersions = false">
        <div class="modal modal-wide">
          <div class="modal-header">
            <div>
              <h3>Record History</h3>
              <p class="snap-meta">{{ allVersionsKey?.label }} — {{ recordVersions.length }} version(s)</p>
            </div>
            <button class="btn-icon" @click="showAllVersions = false" v-html="svgClose"></button>
          </div>

          <div v-if="recordVersions.length === 0" class="empty-row" style="padding:24px;text-align:center;color:#aaa;">
            No versions found for this record.
          </div>

          <div v-else class="versions-timeline">
            <div
              v-for="(ver, idx) in recordVersions"
              :key="ver.id"
              class="version-card"
              :class="{ 'version-latest': idx === 0 }"
            >
              <div class="version-card-header">
                <div class="version-left">
                  <span class="version-num">v{{ recordVersions.length - idx }}</span>
                  <span class="type-badge" :class="actionBadgeClass(ver.action_type)">{{ actionLabel(ver.action_type) }}</span>
                  <span v-if="idx === 0" class="latest-chip">Latest</span>
                </div>
                <div class="version-right">
                  <span class="version-meta">🕐 {{ formatDate(ver.created_at) }}</span>
                  <span class="version-meta">👤 {{ ver.user_name }}</span>
                  <button class="btn-view" @click="viewSnapshot(ver)">👁 View Details</button>
                </div>
              </div>
              <div class="version-details">{{ ver.details }}</div>
            </div>
          </div>

          <button class="btn btn-primary close-btn" @click="showAllVersions = false">Close</button>
        </div>
      </div>
    </Transition>

    <!-- Clear History Confirmation -->
    <AppModal
      v-if="showClearModal"
      type="warning"
      title="Clear Version History"
      message="This will archive all currently visible history entries. This cannot be undone."
      confirmLabel="Yes, Clear"
      @confirm="confirmClear"
      @cancel="showClearModal = false"
    />
  </div>
</template>

<style scoped>
.page { padding: 24px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; }
.page-header h2 { margin:0 0 4px; color:#1a3a5c; font-size:20px; }
.page-header p  { margin:0; color:#888; font-size:13px; }
.header-actions { display:flex; gap:10px; align-items:center; }

/* Toolbar */
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-input { padding:8px 14px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:220px; outline:none; }
.filter-select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; outline:none; background:#fff; }
.btn-clear-date { background:none; border:none; cursor:pointer; font-size:13px; color:#888; padding:4px 6px; border-radius:4px; }
.btn-clear-date:hover { background:#fdecea; color:#e74c3c; }
.record-count { font-size:13px; color:#888; white-space:nowrap; }

/* Buttons */
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn:disabled { opacity:0.6; cursor:not-allowed; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-refresh { background:#ebf5fb; color:#2980b9; border:1px solid #a9cce3; }
.btn-refresh:hover:not(:disabled) { background:#2980b9; color:#fff; }
.btn-danger  { background:#fdecea; color:#e74c3c; border:1px solid #f5b7b1; }
.btn-danger:hover:not(:disabled) { background:#e74c3c; color:#fff; }

/* Error */
.error-banner { background:#fef3e2; border:1px solid #f5cba7; border-radius:8px; padding:10px 14px; font-size:13px; color:#e67e22; margin-bottom:12px; }

/* Table */
.table-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow-x:auto; max-height:calc(100vh - 280px); overflow-y:auto; }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 14px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:10px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.ts-col { font-size:11px; color:#555; white-space:nowrap; }
.details-col { max-width:260px; font-size:12px; color:#444; }
.empty-row { text-align:center; color:#aaa; padding:40px; font-size:13px; }

/* Action type badges */
.type-badge { padding:3px 10px; border-radius:8px; font-size:11px; font-weight:600; white-space:nowrap; }
.type-added    { background:#eafaf1; color:#1a6b3c; }
.type-edited   { background:#ebf5fb; color:#2980b9; }
.type-deleted  { background:#fdecea; color:#e74c3c; }
.type-other    { background:#f4f4f4; color:#666; }

/* Module badges */
.module-badge { padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; white-space:nowrap; }
.mod-employee  { background:#e8f0fe; color:#1a3a5c; }
.mod-leave     { background:#fef3e2; color:#e67e22; }
.mod-payroll   { background:#eafaf1; color:#1a6b3c; }
.mod-schedule  { background:#f5eef8; color:#8e44ad; }
.mod-training  { background:#fdecea; color:#c0392b; }
.mod-dtr       { background:#ebf5fb; color:#2980b9; }
.mod-to        { background:#fdfefe; color:#555; border:1px solid #ddd; }
.mod-tracking  { background:#f0f4f8; color:#1a3a5c; }
.mod-signatory { background:#fef9e7; color:#b7950b; }
.mod-auth      { background:#f4f4f4; color:#666; }
.mod-other     { background:#f4f4f4; color:#666; }

/* Action buttons in table */
.action-btns { display:flex; gap:6px; align-items:center; }
.btn-view { background:none; border:1px solid #ddd; border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; color:#1a3a5c; white-space:nowrap; }
.btn-view:hover { background:#e8f0fe; }
.btn-versions { background:none; border:1px solid #1a6b3c; border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; color:#1a6b3c; display:inline-flex; align-items:center; gap:4px; white-space:nowrap; }
.btn-versions:hover { background:#eafaf1; }
.icon-svg { display:inline-flex; align-items:center; width:14px; height:14px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }

/* Modal base */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(2px); }
.modal { background:#fff; border-radius:16px; padding:24px; width:100%; max-width:600px; box-shadow:0 20px 60px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; }
.modal-wide { max-width:720px; }
.modal-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px; }
.modal-header h3 { margin:4px 0 2px; font-size:16px; color:#1a1a2e; }
.modal-badges { display:flex; gap:6px; margin-bottom:6px; flex-wrap:wrap; }
.snap-meta { font-size:11px; color:#555; margin:4px 0 0; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; border-radius:4px; display:inline-flex; align-items:center; }
.btn-icon :deep(svg) { width:18px; height:18px; fill:#555; }

/* Snapshot compare */
.snap-compare { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
.snap-col { display:flex; flex-direction:column; gap:6px; }
.snap-col-label { font-size:11px; font-weight:700; padding:3px 10px; border-radius:6px; text-align:center; }
.snap-col-label.before { background:#fdecea; color:#c0392b; }
.snap-col-label.after  { background:#eafaf1; color:#1a6b3c; }
.snap-pre { background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; padding:10px; font-size:11px; white-space:pre-wrap; word-break:break-word; margin:0; max-height:300px; overflow-y:auto; color:#333; font-family:monospace; }
.snap-no-data { text-align:center; color:#aaa; padding:24px; font-size:13px; }
.close-btn { margin-top:12px; float:right; }

/* All Versions timeline */
.versions-timeline { display:flex; flex-direction:column; gap:10px; margin-bottom:16px; max-height:55vh; overflow-y:auto; padding-right:4px; }
.version-card { border:1px solid #e9ecef; border-radius:10px; padding:12px 14px; background:#fafafa; }
.version-card.version-latest { border-color:#1a3a5c; background:#f0f4f8; }
.version-card-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:6px; }
.version-left { display:flex; align-items:center; gap:8px; }
.version-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.version-num { font-size:11px; font-weight:700; color:#1a3a5c; background:#e8f0fe; padding:2px 8px; border-radius:6px; }
.latest-chip { font-size:10px; font-weight:700; background:#1a3a5c; color:#fff; padding:2px 8px; border-radius:6px; }
.version-meta { font-size:11px; color:#555; white-space:nowrap; }
.version-details { font-size:12px; color:#666; padding-top:4px; border-top:1px solid #e9ecef; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }
</style>
