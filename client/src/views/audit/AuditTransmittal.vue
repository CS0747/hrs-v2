<script setup>
import { ref, computed } from 'vue'
import { useDTRStore } from '@/stores/dtr'
import { useLeaveStore } from '@/stores/leave'
import { useAuthStore } from '@/stores/auth'

const dtrStore = useDTRStore()
const leaveStore = useLeaveStore()
const auth = useAuthStore()

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  document: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  check: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  leave: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  verify: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>`,
  money: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>`,
}

const activeTab = ref('summary')
const filterModule = ref('all')
const search = ref('')
const sortBy = ref('timestamp')
const sortDir = ref('desc')
const archivedIds = ref(new Set())

function toggleSort(col) {
  if (sortBy.value === col) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortBy.value = col; sortDir.value = 'desc' }
}
function sortIcon(col) {
  if (sortBy.value !== col) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}

function archiveLog(id) { archivedIds.value = new Set([...archivedIds.value, id]) }
function restoreLog(id) { const s = new Set(archivedIds.value); s.delete(id); archivedIds.value = s }

const showArchived = ref(false)

const modules = ['all', 'DTR', 'Leave', 'T.O.']

// Merge built-in seed log + live auth log — DTR, Leave, T.O. only
const ALLOWED_MODULES = new Set(['DTR', 'Leave', 'T.O.'])

const allLogs = computed(() => {
  const live = auth.activityLog.filter(l => ALLOWED_MODULES.has(l.module))
  const seed = [
    { id: 's1', timestamp: '4/16/2026, 09:15:00 AM', user: 'HR Admin', action: 'DTR Received', module: 'DTR', details: 'DTR of Dela Cruz, Juan S. for April 1-15, 2026 received.', status: 'OK' },
    { id: 's2', timestamp: '4/16/2026, 09:30:00 AM', user: 'Thea Villanueva', action: 'DTR Submitted', module: 'DTR', details: 'DTR of Reyes, Maria G. submitted for transmittal.', status: 'OK' },
    { id: 's3', timestamp: '4/15/2026, 02:00:00 PM', user: 'HR Admin', action: 'Leave Approved', module: 'Leave', details: 'Leave of Dela Cruz, Juan S. (VL, Apr 20-22) approved.', status: 'OK' },
  ]
  const existingIds = new Set(live.map(l => l.id))
  return [...live, ...seed.filter(s => !existingIds.has(s.id))]
})

const filtered = computed(() => {
  let list = allLogs.value.filter(l => {
    const isArchived = archivedIds.value.has(l.id)
    if (showArchived.value ? !isArchived : isArchived) return false
    const q = search.value.toLowerCase()
    const matchSearch = !q || l.details.toLowerCase().includes(q) || l.action.toLowerCase().includes(q) || l.user.toLowerCase().includes(q)
    const matchModule = filterModule.value === 'all' || l.module === filterModule.value
    return matchSearch && matchModule
  })
  list = [...list].sort((a, b) => {
    let va = a[sortBy.value] ?? ''
    let vb = b[sortBy.value] ?? ''
    if (typeof va === 'string') va = va.toLowerCase()
    if (typeof vb === 'string') vb = vb.toLowerCase()
    if (va < vb) return sortDir.value === 'asc' ? -1 : 1
    if (va > vb) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })
  return list
})

// Transmittal summary
const dtrSummary = computed(() => ({
  total: dtrStore.dtrRecords.length,
  pending: dtrStore.dtrRecords.filter(r => r.status === 'Pending').length,
  received: dtrStore.dtrRecords.filter(r => r.status === 'Received').length,
  verified: dtrStore.dtrRecords.filter(r => r.status === 'Verified').length,
}))

const leaveSummary = computed(() => ({
  total: leaveStore.leaveRecords.length,
  pending: leaveStore.leaveRecords.filter(r => r.status === 'Pending').length,
  approved: leaveStore.leaveRecords.filter(r => r.status === 'Approved').length,
}))

function moduleColor(m) {
  const map = { DTR: '#2980b9', Leave: '#e67e22', Employee: '#8e44ad', 'T.O.': '#c0392b', Auth: '#1a6b3c' }
  return map[m] || '#888'
}
function moduleBg(m) {
  const map = { DTR: '#ebf5fb', Leave: '#fef3e2', Employee: '#f5eef8', 'T.O.': '#fdecea', Auth: '#e8f5ee' }
  return map[m] || '#f4f4f4'
}

function formatTimestamp(raw) {
  if (!raw) return '—'
  const d = new Date(raw)
  if (isNaN(d.getTime())) return raw
  const mm   = String(d.getMonth() + 1).padStart(2, '0')
  const dd   = String(d.getDate()).padStart(2, '0')
  const yyyy = d.getFullYear()
  const hh   = String(d.getHours() % 12 || 12).padStart(2, '0')
  const min  = String(d.getMinutes()).padStart(2, '0')
  const sec  = String(d.getSeconds()).padStart(2, '0')
  const ampm = d.getHours() < 12 ? 'AM' : 'PM'
  return `${mm}/${dd}/${yyyy}, ${hh}:${min}:${sec} ${ampm}`
}
</script>

<template>
  <div class="page">
    <!-- Summary only — no tabs -->
    <div>
      <div class="summary-section">
        <div class="summary-grid">
          <div class="sum-card blue">
            <div class="sum-icon"><span class="icon-svg sum-svg" v-html="svgIcons.document"></span></div>
            <div class="sum-info">
              <div class="sum-label">DTR Total</div>
              <div class="sum-value">{{ dtrSummary.total }}</div>
            </div>
          </div>
          <div class="sum-card orange">
            <div class="sum-icon">⏳</div>
            <div class="sum-info">
              <div class="sum-label">DTR Pending</div>
              <div class="sum-value">{{ dtrSummary.pending }}</div>
            </div>
          </div>
          <div class="sum-card green">
            <div class="sum-icon"><span class="icon-svg sum-svg" v-html="svgIcons.check"></span></div>
            <div class="sum-info">
              <div class="sum-label">DTR Received</div>
              <div class="sum-value">{{ dtrSummary.received }}</div>
            </div>
          </div>
          <div class="sum-card purple">
            <div class="sum-icon"><span class="icon-svg sum-svg" v-html="svgIcons.verify"></span></div>
            <div class="sum-info">
              <div class="sum-label">DTR Verified</div>
              <div class="sum-value">{{ dtrSummary.verified }}</div>
            </div>
          </div>
          <div class="sum-card teal">
            <div class="sum-icon"><span class="icon-svg sum-svg" v-html="svgIcons.leave"></span></div>
            <div class="sum-info">
              <div class="sum-label">Leave Pending</div>
              <div class="sum-value">{{ leaveSummary.pending }}</div>
            </div>
          </div>
          <div class="sum-card green2">
            <div class="sum-icon"><span class="icon-svg sum-svg" v-html="svgIcons.check"></span></div>
            <div class="sum-info">
              <div class="sum-label">Leave Approved</div>
              <div class="sum-value">{{ leaveSummary.approved }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity in summary -->
      <div class="audit-section">
        <h3 class="section-title">🕐 Recent Activity (Last 5)</h3>
        <div class="recent-list">
          <div v-for="l in allLogs.slice(0,5)" :key="l.id" class="recent-item">
            <div class="recent-module-dot" :style="{ background: moduleColor(l.module) }"></div>
            <div class="recent-info">
              <strong>{{ l.action }}</strong> — {{ l.details }}
              <div class="recent-meta">{{ l.user }} · {{ formatTimestamp(l.timestamp) }}</div>
            </div>
            <span class="module-badge" :style="{ background: moduleBg(l.module), color: moduleColor(l.module) }">{{ l.module }}</span>
          </div>
        </div>
        <button class="view-all-btn" style="display:none"></button>
      </div>
    </div>

  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.sum-svg { width: 24px; height: 24px; }
.page { padding: 24px; }
.tab-bar {
  display: flex; gap: 4px; margin-bottom: 20px;
  border-bottom: 2px solid #e0e0e0; padding-bottom: 0;
}
.tab-btn {
  padding: 10px 20px; border: none; background: none;
  cursor: pointer; font-size: 13px; font-weight: 600;
  color: #888; border-bottom: 3px solid transparent;
  margin-bottom: -2px; transition: all 0.2s; display: flex; align-items: center; gap: 6px;
}
.tab-btn:hover { color: #1a6b3c; }
.tab-btn.active { color: #1a6b3c; border-bottom-color: #1a6b3c; }
.history-count {
  background: #1a6b3c; color: #fff; border-radius: 10px;
  padding: 1px 7px; font-size: 11px;
}
.section-title { font-size: 16px; font-weight: 700; color: #1a6b3c; margin: 0 0 16px; }
.summary-section { margin-bottom: 24px; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
.sum-card { border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 12px; }
.sum-card.blue { background: #ebf5fb; border: 1px solid #a9cce3; }
.sum-card.orange { background: #fef3e2; border: 1px solid #f9ca8b; }
.sum-card.green { background: #eafaf1; border: 1px solid #a9dfbf; }
.sum-card.green2 { background: #e8f8f5; border: 1px solid #a2d9ce; }
.sum-card.purple { background: #f5eef8; border: 1px solid #d2b4de; }
.sum-card.teal { background: #e8f6f3; border: 1px solid #a2d9ce; }
.sum-icon { font-size: 24px; display: flex; align-items: center; }
.sum-label { font-size: 11px; color: #666; }
.sum-value { font-size: 24px; font-weight: 800; color: #1a6b3c; }
.audit-section { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.recent-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.recent-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; background: #f9fafb; border-radius: 8px;
}
.recent-module-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.recent-info { flex: 1; font-size: 13px; }
.recent-info strong { color: #1a6b3c; }
.recent-meta { font-size: 11px; color: #888; margin-top: 2px; }
.view-all-btn {
  background: none; border: none; color: #1a6b3c;
  font-size: 13px; font-weight: 600; cursor: pointer; padding: 0;
}
.view-all-btn:hover { text-decoration: underline; }
.toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; }
.search-wrap { position: relative; display: inline-flex; align-items: center; }
.search-icon { position: absolute; left: 10px; color: #aaa; pointer-events: none; }
.search-input { padding: 8px 14px 8px 34px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; width: 280px; outline: none; }
.filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }
.record-count { font-size: 13px; color: #888; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 60vh; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.data-table thead tr { background: #1a6b3c; color: #fff; }
.data-table thead tr th { position: sticky; top: 0; z-index: 2; background: #1a6b3c; }
.data-table th { padding: 11px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.timestamp { font-family: monospace; font-size: 11px; color: #888; white-space: nowrap; }
.module-badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.details-cell { max-width: 320px; }
.badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-green { background: #eafaf1; color: #27ae60; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }
.data-table th.sortable { cursor: pointer; user-select: none; }
.data-table th.sortable:hover { background: #155a30; }
.archived-row { opacity: 0.45; background: #f9f9f9 !important; }
.archive-toggle-btn {
  padding: 7px 14px; border: 1px solid #ddd; border-radius: 8px;
  background: #fff; cursor: pointer; font-size: 12px; font-weight: 600; color: #555;
}
.archive-toggle-btn.active { background: #fef3e2; border-color: #e67e22; color: #e67e22; }
.archived-count { font-size: 12px; color: #e67e22; font-weight: 600; }
.btn-archive {
  background: none; border: 1px solid #ddd; border-radius: 6px;
  padding: 3px 8px; font-size: 11px; cursor: pointer; color: #888; white-space: nowrap;
}
.btn-archive:hover { background: #fef3e2; border-color: #e67e22; color: #e67e22; }
.btn-restore {
  background: none; border: 1px solid #ddd; border-radius: 6px;
  padding: 3px 8px; font-size: 11px; cursor: pointer; color: #27ae60; white-space: nowrap;
}
.btn-restore:hover { background: #eafaf1; border-color: #27ae60; }
</style>
