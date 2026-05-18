<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { printAuditLogs } from '@/utils/print'

const auth = useAuthStore()

const search      = ref('')
const filterModule = ref('')
const filterAction = ref('')

const modules = ['Auth','Employee','DTR','Leave','T.O.','Schedule','Training','Tracking','Signatory','Other']
const actionTypes = ['LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','EXPORT','OTHER']

// Load from DB + local session log
const dbLogs  = ref([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res  = await fetch('http://localhost/hrs-v2/server/api/audit_logs.php?limit=500')
    const data = await res.json()
    if (Array.isArray(data)) dbLogs.value = data
  } catch { /* use session log only */ }
  finally { loading.value = false }
})

// Merge DB logs + session logs, deduplicate by id
const allLogs = computed(() => {
  const sessionLogs = auth.activityLog.map(l => ({
    id:          l.id,
    user_name:   l.user,
    action:      l.action,
    action_type: l.actionType || 'OTHER',
    module:      l.module,
    details:     l.details,
    status:      l.status || 'OK',
    created_at:  l.timestamp,
  }))
  const dbIds = new Set(dbLogs.value.map(l => String(l.id)))
  const unique = sessionLogs.filter(l => !dbIds.has(String(l.id)))
  return [...dbLogs.value, ...unique].sort((a, b) =>
    new Date(b.created_at) - new Date(a.created_at)
  )
})

const filtered = computed(() => allLogs.value.filter(l => {
  const q  = search.value.toLowerCase()
  const ms = !q || (l.user_name||'').toLowerCase().includes(q) || (l.action||'').toLowerCase().includes(q) || (l.details||'').toLowerCase().includes(q)
  const mm = !filterModule.value || l.module === filterModule.value
  const ma = !filterAction.value || l.action_type === filterAction.value
  return ms && mm && ma
}))

function statusClass(s) {
  return s === 'OK' ? 'badge-green' : 'badge-red'
}
function actionTypeColor(t) {
  return { LOGIN:'#27ae60', LOGOUT:'#888', CREATE:'#2980b9', UPDATE:'#e67e22', DELETE:'#e74c3c', VIEW:'#8e44ad', EXPORT:'#1a6b3c' }[t] || '#666'
}
function actionTypeBg(t) {
  return { LOGIN:'#eafaf1', LOGOUT:'#f4f4f4', CREATE:'#ebf5fb', UPDATE:'#fef3e2', DELETE:'#fdecea', VIEW:'#f5eef8', EXPORT:'#e8f5ee' }[t] || '#f4f4f4'
}

// Normalize any timestamp string to MM/DD/YYYY, hh:mm:ss AM/PM
function formatTimestamp(raw) {
  if (!raw) return '—'
  const d = new Date(raw)
  if (isNaN(d.getTime())) return raw // fallback if unparseable
  const mm   = String(d.getMonth() + 1).padStart(2, '0')
  const dd   = String(d.getDate()).padStart(2, '0')
  const yyyy = d.getFullYear()
  const hh   = String(d.getHours() % 12 || 12).padStart(2, '0')
  const min  = String(d.getMinutes()).padStart(2, '0')
  const sec  = String(d.getSeconds()).padStart(2, '0')
  const ampm = d.getHours() < 12 ? 'AM' : 'PM'
  return `${mm}/${dd}/${yyyy}, ${hh}:${min}:${sec} ${ampm}`
}

function exportCSV() {
  const q = v => `"${String(v ?? '').replace(/"/g, '""')}"` // wrap in quotes, escape inner quotes
  const headers = ['Timestamp', 'User', 'Action', 'Type', 'Module', 'Details', 'Status']
  const rows = filtered.value.map(l => [
    formatTimestamp(l.created_at),
    l.user_name   || '',
    l.action      || '',
    l.action_type || '',
    l.module      || '',
    l.details     || '',
    l.status      || '',
  ])
  const csv = [headers.map(q), ...rows.map(r => r.map(q))].join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }) // BOM for Excel UTF-8
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `audit_history_${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h2>Audit History</h2>
        <p>Complete log of all user actions in the system.</p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button class="btn btn-secondary" @click="printAuditLogs(filtered, { Module: filterModule, Action: filterAction })">
          🖨 Print
        </button>
        <button class="btn btn-export" @click="exportCSV">⬇ Export CSV</button>
      </div>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <input v-model="search" class="search-input" placeholder="Search user, action, details..." />
        </div>
        <select v-model="filterModule" class="filter-select">
          <option value="">All Modules</option>
          <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
        </select>
        <select v-model="filterAction" class="filter-select">
          <option value="">All Actions</option>
          <option v-for="a in actionTypes" :key="a" :value="a">{{ a }}</option>
        </select>
      </div>
      <span class="record-count">{{ filtered.length }} log(s)</span>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Action</th>
            <th>Type</th>
            <th>Module</th>
            <th>Details</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="7" class="empty-row">Loading...</td></tr>
          <tr v-else-if="filtered.length === 0"><td colspan="7" class="empty-row">No logs found.</td></tr>
          <tr v-for="log in filtered" :key="log.id">
            <td class="ts-col">{{ formatTimestamp(log.created_at) }}</td>
            <td><strong>{{ log.user_name }}</strong></td>
            <td>{{ log.action }}</td>
            <td>
              <span class="action-badge"
                :style="{ background: actionTypeBg(log.action_type), color: actionTypeColor(log.action_type) }">
                {{ log.action_type || '—' }}
              </span>
            </td>
            <td><span class="module-tag">{{ log.module }}</span></td>
            <td class="details-col">{{ log.details }}</td>
            <td><span class="badge" :class="statusClass(log.status)">{{ log.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.page { padding: 24px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; }
.page-header h2 { margin:0 0 4px; color:#1a3a5c; font-size:20px; }
.page-header p  { margin:0; color:#888; font-size:13px; }
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { display:inline-flex; }
.search-input { padding:8px 14px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:280px; outline:none; }
.filter-select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; outline:none; background:#fff; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn-export { background:#1a6b3c; color:#fff; }
.btn-export:hover { background:#27ae60; }
.table-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow-x:auto; max-height:calc(100vh - 220px); overflow-y:auto; }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:12px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 12px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.ts-col { font-size:11px; color:#888; white-space:nowrap; }
.details-col { font-size:11px; color:#555; max-width:280px; }
.badge { padding:2px 8px; border-radius:8px; font-size:10px; font-weight:600; }
.badge-green { background:#eafaf1; color:#27ae60; }
.badge-red   { background:#fdecea; color:#e74c3c; }
.action-badge { padding:2px 8px; border-radius:8px; font-size:10px; font-weight:700; }
.module-tag { background:#f0f4f8; color:#1a3a5c; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; }
.empty-row { text-align:center; color:#aaa; padding:40px; }
</style>
