<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTrackingStore } from '@/stores/tracking'
import { usePermissions } from '@/composables/usePermissions'
import { printTrackingRecords } from '@/utils/print'

const store = useTrackingStore()
const { hasPermission, loadPermissions } = usePermissions()

// Fetch tracking records on component mount
onMounted(async () => {
  await loadPermissions()
  store.fetchRecords()
})

const svgIcons = {
  search:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  receive:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z"/></svg>`,
  send:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>`,
  save:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  print:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>`,
}

// ── Tab: Receiving / Outgoing ─────────────────────────────────────────────────
const activeTab = ref('receiving')

const AMELA = 'Gonzales, Realyn P. (HR AMELA)'

const search       = ref('')
const filterType   = ref('')
const filterStatus = ref('')
const showForm     = ref(false)
const editId       = ref(null)
const isOutgoing   = ref(false)

const docTypes = ['DTR Transmittal', 'Leave Form', 'Travel Order', 'Memorandum', 'Other']
const receivingStatuses = ['Pending', 'In Transit', 'Received', 'Returned', 'Lost']
const outgoingStatuses  = ['Sent', 'Delivered', 'Returned']

const blankReceiving = () => ({
  direction: 'incoming',
  docType: 'DTR Transmittal', docNo: '', from: '', to: 'HR Office',
  dateForwarded: new Date().toISOString().split('T')[0], dateReceived: '',
  receivedBy: AMELA, status: 'Pending', remarks: '',
})
const blankOutgoing = () => ({
  direction: 'outgoing',
  docType: 'DTR Transmittal', docNo: '', from: 'HR Office', to: '',
  dateForwarded: new Date().toISOString().split('T')[0],
  receivedBy: AMELA, status: 'Sent', remarks: '',
})

const form = ref(blankReceiving())
const saving = ref(false)

// Separate receiving and outgoing records from store
const receivingRecords = computed(() =>
  store.trackingRecords.filter(r => r.direction === 'incoming')
)
const outgoingRecords = computed(() =>
  store.trackingRecords.filter(r => r.direction === 'outgoing')
)

const currentRecords = computed(() =>
  activeTab.value === 'receiving' ? receivingRecords.value : outgoingRecords.value
)

const filtered = computed(() => currentRecords.value.filter(r => {
  const q = search.value.toLowerCase()
  const ms = !q || (r.docNo||'').toLowerCase().includes(q) || (r.from||'').toLowerCase().includes(q) || (r.to||'').toLowerCase().includes(q)
  const mt = !filterType.value   || r.docType === filterType.value
  const mst= !filterStatus.value || r.status  === filterStatus.value
  return ms && mt && mst
}))

function openAdd() {
  editId.value   = null
  isOutgoing.value = activeTab.value === 'outgoing'
  form.value     = isOutgoing.value ? blankOutgoing() : blankReceiving()
  showForm.value = true
}

function openEdit(r) {
  editId.value   = r.id
  isOutgoing.value = activeTab.value === 'outgoing'
  form.value     = { ...r }
  showForm.value = true
}

async function save() {
  saving.value = true
  try {
    if (editId.value) {
      await store.updateRecord(editId.value, form.value)
    } else {
      await store.addRecord(form.value)
    }
    showForm.value = false
  } catch (err) {
    alert('Error saving tracking record: ' + err.message)
  } finally {
    saving.value = false
  }
}

async function markReceived(r) {
  try {
    const updatedData = {
      ...r,
      status: 'Received',
      receivedBy: AMELA,
      dateReceived: new Date().toISOString().split('T')[0]
    }
    await store.updateRecord(r.id, updatedData)
  } catch (err) {
    alert('Error marking as received: ' + err.message)
  }
}

function statusClass(s) {
  const map = { Pending:'badge-orange','In Transit':'badge-blue',Received:'badge-green',Returned:'badge-purple',Lost:'badge-red',Sent:'badge-blue',Delivered:'badge-green' }
  return map[s] || 'badge-gray'
}
</script>

<template>
  <div class="page">
    <!-- Loading State -->
    <div v-if="store.loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading tracking records...</p>
    </div>

    <!-- Error State -->
    <div v-if="store.error" class="error-banner">
      <strong>⚠️ Error:</strong> {{ store.error }}
      <button class="btn-retry" @click="store.fetchRecords()">Retry</button>
    </div>

    <!-- AMELA banner -->
    <div class="amela-banner">
      <span class="icon-svg" v-html="svgIcons.receive"></span>
      Document Tracking — Managed by: <strong>{{ AMELA }}</strong>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn" :class="{ active: activeTab === 'receiving' }" @click="activeTab = 'receiving'">
        <span class="icon-svg" v-html="svgIcons.receive"></span> Receiving
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'outgoing' }" @click="activeTab = 'outgoing'">
        <span class="icon-svg" v-html="svgIcons.send"></span> Outgoing
      </button>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search doc no, from, to..." />
        </div>
        <AppSelect v-model="filterType"   :options="[{ label: 'All Doc Types', value: '' }, ...docTypes.map(t => ({ label: t, value: t }))]" placeholder="All Doc Types" />
        <AppSelect v-model="filterStatus" :options="[{ label: 'All Status', value: '' }, ...(activeTab === 'receiving' ? receivingStatuses : outgoingStatuses).map(s => ({ label: s, value: s }))]" placeholder="All Status" />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} record(s)</span>
        <button class="btn btn-print" @click="printTrackingRecords(filtered, { Type: filterType, Status: filterStatus }, activeTab === 'receiving' ? 'Receiving' : 'Outgoing')">
          <span class="icon-svg" v-html="svgIcons.print"></span> Print
        </button>
        <button v-if="hasPermission('Tracking / Receiving', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span>
          {{ activeTab === 'receiving' ? 'Add Receiving' : 'Add Outgoing' }}
        </button>
      </div>
    </div>

    <!-- Receiving Table -->
    <div v-if="activeTab === 'receiving'" class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Doc Type</th><th>Doc No.</th><th>From</th><th>To</th><th>Forwarded</th><th>Received</th><th>Received By</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-if="filtered.length === 0"><td colspan="10" class="empty-row">No receiving records found.</td></tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><span class="doc-type">{{ r.docType }}</span></td>
            <td><span class="doc-no">{{ r.docNo }}</span></td>
            <td>{{ r.from }}</td><td>{{ r.to }}</td>
            <td>{{ r.dateForwarded }}</td>
            <td>{{ r.dateReceived || '—' }}</td>
            <td class="amela-cell">{{ r.receivedBy || '—' }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td class="remarks-cell">{{ r.remarks || '—' }}</td>
            <td>
              <div class="action-btns">
                <button v-if="r.status !== 'Received' && hasPermission('Tracking / Receiving', 'Edit')" class="btn btn-receive-sm" @click="markReceived(r)">
                  <span class="icon-svg" v-html="svgIcons.receive"></span> Receive
                </button>
                <button v-if="hasPermission('Tracking / Receiving', 'Edit')" class="btn-icon" @click="openEdit(r)"><span class="icon-svg" v-html="svgIcons.edit"></span></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Outgoing Table -->
    <div v-else class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Doc Type</th><th>Doc No.</th><th>From</th><th>To</th><th>Date Sent</th><th>Sent By</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-if="filtered.length === 0"><td colspan="9" class="empty-row">No outgoing records found.</td></tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><span class="doc-type">{{ r.docType }}</span></td>
            <td><span class="doc-no">{{ r.docNo }}</span></td>
            <td>{{ r.from }}</td><td>{{ r.to }}</td>
            <td>{{ r.dateForwarded }}</td>
            <td class="amela-cell">{{ r.receivedBy }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td class="remarks-cell">{{ r.remarks || '—' }}</td>
            <td><button class="btn-icon" @click="openEdit(r)"><span class="icon-svg" v-html="svgIcons.edit"></span></button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Form Modal -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit' : 'Add' }} {{ isOutgoing ? 'Outgoing' : 'Receiving' }} Record</h3>
          <button class="close-btn" @click="showForm = false"><span class="icon-svg" v-html="svgIcons.close"></span></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group"><label>Document Type</label><AppSelect v-model="form.docType" :options="docTypes" /></div>
            <div class="form-group"><label>Document No.</label><input v-model="form.docNo" /></div>
            <div class="form-group"><label>From</label><input v-model="form.from" /></div>
            <div class="form-group"><label>To</label><input v-model="form.to" /></div>
            <template v-if="!isOutgoing">
              <div class="form-group"><label>Date Forwarded</label><input v-model="form.dateForwarded" type="date" /></div>
              <div class="form-group"><label>Date Received</label><input v-model="form.dateReceived" type="date" /></div>
              <div class="form-group"><label>Received By</label><input v-model="form.receivedBy" /></div>
              <div class="form-group"><label>Status</label><AppSelect v-model="form.status" :options="receivingStatuses" /></div>
            </template>
            <template v-else>
              <div class="form-group"><label>Date Sent</label><input v-model="form.dateForwarded" type="date" /></div>
              <div class="form-group"><label>Sent By</label><input v-model="form.receivedBy" /></div>
              <div class="form-group"><label>Status</label><AppSelect v-model="form.status" :options="outgoingStatuses" /></div>
            </template>
            <div class="form-group full"><label>Remarks</label><textarea v-model="form.remarks" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-primary" @click="save" :disabled="saving">
            <span v-if="saving" class="spinner-small"></span>
            <span v-else class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; display:flex; flex-direction:column; gap:14px; }
.amela-banner { display:flex; align-items:center; gap:8px; background:#e8f5ee; border:1px solid #c3e6cb; border-radius:8px; padding:8px 16px; font-size:13px; color:#1a6b3c; }
.amela-banner .icon-svg { color:#1a6b3c; }
.amela-banner .icon-svg :deep(svg) { fill:#1a6b3c; }
.tabs { display:flex; gap:4px; }
.tab-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:8px 8px 0 0; border:1px solid #ddd; border-bottom:none; background:#f0f4f8; color:#555; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.15s; }
.tab-btn.active { background:#1a3a5c; color:#fff; border-color:#1a3a5c; }
.tab-btn .icon-svg :deep(svg) { fill:currentColor; }
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-icon { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:240px; outline:none; }
.record-count { font-size:13px; color:#888; }
.btn { padding:6px 14px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn-primary { background:#1a3a5c; color:#fff; padding:8px 16px; font-size:13px; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; padding:8px 16px; font-size:13px; }
.btn-receive-sm { background:#27ae60; color:#fff; font-size:11px; padding:4px 8px; }
.btn-print { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.table-wrapper { overflow-x:auto; overflow-y:auto; max-height:55vh; background:#fff; border-radius:0 12px 12px 12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:12px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 12px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.doc-type { font-size:12px; color:#2980b9; font-weight:600; }
.doc-no { font-family:monospace; font-size:11px; color:#555; }
.amela-cell { font-size:11px; color:#1a6b3c; font-weight:600; }
.remarks-cell { max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-orange { background:#fef3e2; color:#e67e22; }
.badge-blue   { background:#ebf5fb; color:#2980b9; }
.badge-green  { background:#eafaf1; color:#27ae60; }
.badge-purple { background:#f5eef8; color:#8e44ad; }
.badge-red    { background:#fdecea; color:#c0392b; }
.badge-gray   { background:#f4f4f4; color:#666; }
.action-btns { display:flex; gap:6px; align-items:center; }
.btn-icon { background:none; border:none; cursor:pointer; padding:3px; border-radius:4px; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#f0f4f8; }
.empty-row { text-align:center; color:#aaa; padding:40px; }
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal { background:#fff; border-radius:12px; width:700px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f0f4f8; }
.modal-header h3 { margin:0; color:#1a3a5c; }
.close-btn { background:none; border:none; cursor:pointer; color:#888; display:inline-flex; align-items:center; padding:4px; border-radius:4px; }
.close-btn:hover { background:#f0f4f8; }
.modal-body { padding:20px; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #f0f4f8; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group.full { grid-column:1/-1; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input, .form-group select, .form-group textarea { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.loading-container {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px; gap: 16px;
}
.spinner {
  width: 40px; height: 40px; border: 4px solid #f0f4f8;
  border-top-color: #1a3a5c; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.spinner-small {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
.error-banner {
  background: #fdecea; border: 1px solid #f5b7b1;
  color: #c0392b; padding: 14px 18px; border-radius: 8px;
  margin-bottom: 16px; display: flex; align-items: center;
  justify-content: space-between; gap: 12px;
}
.btn-retry {
  padding: 6px 14px; background: #c0392b; color: #fff;
  border: none; border-radius: 6px; cursor: pointer;
  font-size: 12px; font-weight: 600;
}
.btn-retry:hover { background: #a93226; }
.btn-primary:disabled, .btn-secondary:disabled {
  opacity: 0.5; cursor: not-allowed;
}
</style>
