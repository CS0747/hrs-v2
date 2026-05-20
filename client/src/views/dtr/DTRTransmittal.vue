<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDTRStore } from '@/stores/dtr'
import { useAuthStore } from '@/stores/auth'
import { useEmployeeStore } from '@/stores/employees'
import { usePermissions } from '@/composables/usePermissions'
import AppModal from '@/components/AppModal.vue'
import { printDTRRecords } from '@/utils/print'
import { API_ENDPOINTS } from '@/config/api'

const store    = useDTRStore()
const auth     = useAuthStore()
const empStore = useEmployeeStore()
const { hasPermission, loadPermissions } = usePermissions()


const DTR_API = API_ENDPOINTS.DTR

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  document: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  save: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  print: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>`,
  download: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>`,
}

const activeTab = ref('records') // 'records' | 'history'
const search = ref('')
const filterType = ref('')
const filterStatus = ref('')
const showForm = ref(false)
const editId = ref(null)

// DTR history — fetched from backend
const dtrHistory    = ref([])
const historyLoading = ref(false)

async function fetchHistory() {
  historyLoading.value = true
  try {
    const res  = await fetch(`${DTR_API}?history=1`)
    if (!res.ok) throw new Error('Failed to fetch history')
    dtrHistory.value = await res.json()
  } catch (e) {
    console.warn('DTR history fetch failed:', e.message)
  } finally {
    historyLoading.value = false
  }
}

onMounted(async () => {
  await loadPermissions()
  fetchHistory()
})

const historySearch = ref('')
const historyFilterStatus = ref('')

const filteredHistory = computed(() => dtrHistory.value.filter(h => {
  const q = historySearch.value.toLowerCase()
  const matchSearch = !q || (h.employee_name ?? '').toLowerCase().includes(q) || (h.employee_no ?? '').toLowerCase().includes(q)
  const matchStatus = !historyFilterStatus.value || h.status === historyFilterStatus.value
  return matchSearch && matchStatus
}))

const blankForm = () => ({
  employeeNo: '', employeeName: '', department: '',
  period: 'April 1-15, 2026', transmittalType: 'Main',
  submittedBy: '', dateSubmitted: new Date().toISOString().split('T')[0],
  dateReceived: '', verifiedBy: '', verificationDate: '',
  status: 'Pending', remarks: '', signatories: [], signedBy: [],
})

const form = ref(blankForm())
const formErrors = ref({ employeeNo: '', employeeName: '' })

// ── Employee search combobox ──────────────────────────────────────────────────
const empSearch   = ref('')
const empDropOpen = ref(false)

const filteredEmps = computed(() => {
  const q = empSearch.value.toLowerCase().trim()
  if (!q) return empStore.employees.slice(0, 50)
  return empStore.employees.filter(e =>
    e.lastName.toLowerCase().includes(q) ||
    e.firstName.toLowerCase().includes(q) ||
    e.employeeNo.toLowerCase().includes(q) ||
    (e.department ?? '').toLowerCase().includes(q)
  ).slice(0, 50)
})

function selectEmployee(emp) {
  form.value.employeeNo   = emp.employeeNo
  form.value.employeeName = `${emp.lastName}, ${emp.firstName}${emp.middleName ? ' ' + emp.middleName[0] + '.' : ''}`
  form.value.department   = emp.department ?? ''
  empSearch.value   = `${emp.lastName}, ${emp.firstName} (${emp.employeeNo})`
  empDropOpen.value = false
  formErrors.value.employeeNo   = ''
  formErrors.value.employeeName = ''
}

function onEmpBlur() {
  setTimeout(() => { empDropOpen.value = false }, 180)
}

function clearEmployee() {
  form.value.employeeNo   = ''
  form.value.employeeName = ''
  form.value.department   = ''
  empSearch.value = ''
}

function openAdd() {
  editId.value = null
  form.value = blankForm()
  formErrors.value = { employeeNo: '', employeeName: '' }
  empSearch.value = ''
  showForm.value = true
}

function openEdit(rec) {
  editId.value = rec.id
  form.value = { ...rec }
  formErrors.value = { employeeNo: '', employeeName: '' }
  empSearch.value = rec.employeeName ? `${rec.employeeName} (${rec.employeeNo})` : ''
  showForm.value = true
}

function save() {
  formErrors.value = { employeeNo: '', employeeName: '' }
  let valid = true
  if (!form.value.employeeNo.trim()) { formErrors.value.employeeNo = 'Employee No. is required.'; valid = false }
  if (!form.value.employeeName.trim()) { formErrors.value.employeeName = 'Employee Name is required.'; valid = false }
  if (!valid) return
  showSaveModal.value = true
}
async function confirmSave() {
  const payload = { ...form.value, processedBy: auth.currentUser?.name || 'System' }
  if (editId.value) {
    await store.updateRecord(editId.value, payload)
    auth.addLog('DTR Updated', 'DTR', `DTR of ${form.value.employeeName} (${form.value.period}) updated.`)
  } else {
    await store.addRecord(payload)
    auth.addLog('DTR Added', 'DTR', `DTR of ${form.value.employeeName} (${form.value.period}) added.`)
  }
  await fetchHistory()
  showSaveModal.value = false
  showForm.value = false
}

const showDeleteModal = ref(false)
const showSaveModal   = ref(false)
const deleteTarget    = ref(null)

function deleteRec(id) {
  deleteTarget.value = store.dtrRecords.find(r => r.id === id)
  showDeleteModal.value = true
}
async function confirmDelete() {
  if (deleteTarget.value) {
    await store.deleteRecord(deleteTarget.value.id, auth.currentUser?.name || 'System')
    auth.addLog('DTR Deleted', 'DTR', `DTR of ${deleteTarget.value.employeeName} deleted.`)
    await fetchHistory()
  }
  showDeleteModal.value = false
  deleteTarget.value = null
}

const filtered = computed(() => store.dtrRecords.filter(r => {
  const q = search.value.toLowerCase()
  const matchSearch = !q || r.employeeName.toLowerCase().includes(q) || r.employeeNo.toLowerCase().includes(q)
  const matchType = !filterType.value || r.transmittalType === filterType.value
  const matchStatus = !filterStatus.value || r.status === filterStatus.value
  return matchSearch && matchType && matchStatus
}))

function statusClass(s) {
  const map = { Pending: 'badge-orange', Submitted: 'badge-blue', Received: 'badge-green', Verified: 'badge-purple', Returned: 'badge-red' }
  return map[s] || 'badge-gray'
}

// ── Download CSV ─────────────────────────────────────────────────────────────
function downloadRecordsCSV() {
  const headers = ['Emp No','Employee Name','Department','Period','Type','Submitted By','Date Submitted','Date Received','Verified By','Status','Remarks']
  const rows = filtered.value.map(r => [
    r.employeeNo, r.employeeName, r.department, r.period, r.transmittalType,
    r.submittedBy, r.dateSubmitted, r.dateReceived || '', r.verifiedBy || '', r.status, r.remarks || ''
  ])
  downloadCSV('DTR_Records', headers, rows)
}

function downloadHistoryCSV() {
  const headers = ['Timestamp','Processed By','Action','Emp No','Employee Name','Period','Type','Status','Remarks']
  const rows = filteredHistory.value.map(h => [
    h.created_at, h.processed_by, h.action, h.employee_no, h.employee_name,
    h.period, h.transmittal_type, h.status, h.remarks || ''
  ])
  downloadCSV('DTR_History', headers, rows)
}

function downloadCSV(filename, headers, rows) {
  const escape = v => `"${String(v).replace(/"/g, '""')}"`
  const csv = [headers.map(escape).join(','), ...rows.map(r => r.map(escape).join(','))].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `${filename}_${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="page">
    <!-- Tabs -->
    <div class="tab-bar">
      <button class="tab-btn" :class="{ active: activeTab === 'records' }" @click="activeTab = 'records'">
        <span class="icon-svg" v-html="svgIcons.document"></span> DTR Records
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
        🕐 Transmittal History
        <span class="history-count">{{ dtrHistory.length }}</span>
      </button>
    </div>

    <!-- DTR Records Tab -->
    <div v-if="activeTab === 'records'">
      <div class="toolbar">
        <div class="toolbar-left">
          <div class="search-wrap">
            <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
            <input v-model="search" class="search-input" placeholder="Search employee..." />
          </div>
          <AppSelect
            v-model="filterType"
            :options="[{ label: 'All Types', value: '' }, ...store.transmittalTypes.map(t => ({ label: t, value: t }))]"
            placeholder="All Types"
          />
          <AppSelect
            v-model="filterStatus"
            :options="[{ label: 'All Status', value: '' }, ...store.statuses.map(s => ({ label: s, value: s }))]"
            placeholder="All Status"
          />
        </div>
        <div class="toolbar-right">
          <span class="record-count">{{ filtered.length }} record(s)</span>
          <button class="btn btn-print" @click="printDTRRecords(filtered, { Type: filterType, Status: filterStatus })">
            <span class="icon-svg" v-html="svgIcons.print"></span> Print
          </button>
          <button class="btn btn-download" @click="downloadRecordsCSV">
            <span class="icon-svg" v-html="svgIcons.download"></span> Download
          </button>
          <button v-if="hasPermission('DTR Transmittal', 'Add')" class="btn btn-blue" @click="openAdd">
            <span class="icon-svg" v-html="svgIcons.add"></span> Add DTR Record
          </button>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Emp No</th>
              <th>Employee Name</th>
              <th>Department</th>
              <th>Period</th>
              <th>Type</th>
              <th>Submitted By</th>
              <th>Date Submitted</th>
              <th>Date Received</th>
              <th>Verified By</th>
              <th>Status</th>
              <th>Signatories</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="12" class="empty-row">No DTR records found.</td>
            </tr>
            <tr v-for="r in filtered" :key="r.id">
              <td><span class="emp-no">{{ r.employeeNo }}</span></td>
              <td><strong>{{ r.employeeName }}</strong></td>
              <td>{{ r.department }}</td>
              <td>{{ r.period }}</td>
              <td>
                <span class="badge" :class="r.transmittalType === 'Main' ? 'badge-blue' : 'badge-purple'">
                  {{ r.transmittalType }}
                </span>
              </td>
              <td>{{ r.submittedBy }}</td>
              <td>{{ r.dateSubmitted }}</td>
              <td>{{ r.dateReceived || '—' }}</td>
              <td>{{ r.verifiedBy || '—' }}</td>
              <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
              <td>
                <div class="sig-list">
                  <span v-for="sig in r.signatories" :key="sig"
                    class="sig-badge"
                    :class="r.signedBy.includes(sig) ? 'signed' : 'unsigned'">
                    {{ sig }}
                  </span>
                </div>
              </td>
              <td>
                <div class="action-btns">
                  <button v-if="hasPermission('DTR Transmittal', 'Edit')" class="btn-icon" @click="openEdit(r)">
                    <span class="icon-svg" v-html="svgIcons.edit"></span>
                  </button>
                  <button v-if="hasPermission('DTR Transmittal', 'Delete')" class="btn-icon danger" @click="deleteRec(r.id)">
                    <span class="icon-svg" v-html="svgIcons.delete"></span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- History Tab -->
    <div v-if="activeTab === 'history'">
      <div class="toolbar">
        <div class="toolbar-left">
          <div class="search-wrap">
            <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
            <input v-model="historySearch" class="search-input" placeholder="Search employee..." />
          </div>
          <AppSelect
            v-model="historyFilterStatus"
            :options="[{ label: 'All Status', value: '' }, ...store.statuses.map(s => ({ label: s, value: s }))]"
            placeholder="All Status"
          />
        </div>
        <div class="toolbar-right">
          <span class="record-count">{{ filteredHistory.length }} record(s)</span>
          <button class="btn btn-print" @click="printDTRRecords(filteredHistory, { Status: historyFilterStatus })">
            <span class="icon-svg" v-html="svgIcons.print"></span> Print
          </button>
          <button class="btn btn-download" @click="downloadHistoryCSV">
            <span class="icon-svg" v-html="svgIcons.download"></span> Download
          </button>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Processed By</th>
              <th>Action</th>
              <th>Emp No</th>
              <th>Employee Name</th>
              <th>Period</th>
              <th>Type</th>
              <th>Status</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredHistory.length === 0">
              <td colspan="9" class="empty-row">No history records found.</td>
            </tr>
            <tr v-for="h in filteredHistory" :key="h.id">
              <td class="timestamp">{{ h.created_at }}</td>
              <td>{{ h.processed_by }}</td>
              <td><strong class="action-text">{{ h.action }}</strong></td>
              <td><span class="emp-no">{{ h.employee_no }}</span></td>
              <td>{{ h.employee_name }}</td>
              <td>{{ h.period }}</td>
              <td>
                <span class="badge" :class="h.transmittal_type === 'Main' ? 'badge-blue' : 'badge-purple'">
                  {{ h.transmittal_type }}
                </span>
              </td>
              <td><span class="badge" :class="statusClass(h.status)">{{ h.status }}</span></td>
              <td class="remarks-cell">{{ h.remarks || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit DTR Record' : 'Add DTR Record' }}</h3>
          <button class="close-btn" @click="showForm = false">
            <span class="icon-svg" v-html="svgIcons.close"></span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Employee</label>
              <div class="emp-combobox">
                <div class="emp-input-wrap">
                  <input
                    v-model="empSearch"
                    class="emp-search-input"
                    placeholder="Search by name, employee no, or department..."
                    @focus="empDropOpen = true"
                    @blur="onEmpBlur"
                    autocomplete="off"
                  />
                  <button v-if="form.employeeNo" type="button" class="emp-clear-btn" @click="clearEmployee" title="Clear">✕</button>
                </div>
                <div v-if="empDropOpen" class="emp-dropdown">
                  <div v-if="filteredEmps.length === 0" class="emp-option-empty">No employees found.</div>
                  <div
                    v-for="emp in filteredEmps"
                    :key="emp.id"
                    class="emp-option"
                    @mousedown.prevent="selectEmployee(emp)"
                  >
                    <div class="emp-opt-avatar">{{ emp.firstName[0] }}{{ emp.lastName[0] }}</div>
                    <div class="emp-opt-info">
                      <span class="emp-opt-name">{{ emp.lastName }}, {{ emp.firstName }}</span>
                      <span class="emp-opt-meta">{{ emp.employeeNo }} · {{ emp.department }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <span v-if="formErrors.employeeNo || formErrors.employeeName" class="field-error">
                {{ formErrors.employeeNo || formErrors.employeeName }}
              </span>
            </div>
            <!-- Read-only filled fields -->
            <div class="form-group">
              <label>Employee No.</label>
              <input :value="form.employeeNo" readonly class="input-readonly" placeholder="Auto-filled" />
            </div>
            <div class="form-group">
              <label>Employee Name</label>
              <input :value="form.employeeName" readonly class="input-readonly" placeholder="Auto-filled" />
            </div>
            <div class="form-group">
              <label>Department</label>
              <input :value="form.department" readonly class="input-readonly" placeholder="Auto-filled" />
            </div>
            <div class="form-group">
              <label>Period</label>
              <input v-model="form.period" />
            </div>
            <div class="form-group">
              <label>Transmittal Type</label>
              <AppSelect v-model="form.transmittalType" :options="store.transmittalTypes" />
            </div>
            <div class="form-group">
              <label>Submitted By</label>
              <input v-model="form.submittedBy" />
            </div>
            <div class="form-group">
              <label>Date Submitted</label>
              <input v-model="form.dateSubmitted" type="date" />
            </div>
            <div class="form-group">
              <label>Date Received</label>
              <input v-model="form.dateReceived" type="date" />
            </div>
            <div class="form-group">
              <label>Verified By</label>
              <input v-model="form.verifiedBy" />
            </div>
            <div class="form-group">
              <label>Verification Date</label>
              <input v-model="form.verificationDate" type="date" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <AppSelect v-model="form.status" :options="store.statuses" />
            </div>
            <div class="form-group full">
              <label>Remarks</label>
              <textarea v-model="form.remarks" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false">Cancel</button>
          <button class="btn btn-primary" @click="save">
            <span class="icon-svg" v-html="svgIcons.save"></span> Save
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <AppModal
      v-if="showDeleteModal"
      type="delete"
      title="Delete DTR Record"
      message="Are you sure you want to delete this DTR record?"
      :detail="deleteTarget?.employeeName + ' — ' + deleteTarget?.period"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Save Confirmation -->
    <AppModal
      v-if="showSaveModal"
      type="confirm"
      :title="editId ? 'Update DTR Record' : 'Add DTR Record'"
      :message="editId ? 'Save changes to this DTR record?' : 'Add this new DTR record?'"
      :detail="form.employeeName + ' — ' + form.period"
      :confirmLabel="editId ? 'Yes, Update' : 'Yes, Add'"
      @confirm="confirmSave"
      @cancel="showSaveModal = false"
    />
  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.tab-bar {
  display: flex; gap: 4px; margin-bottom: 16px;
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
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.search-wrap { position: relative; display: inline-flex; align-items: center; }
.search-icon { position: absolute; left: 10px; color: #aaa; pointer-events: none; }
.search-input { padding: 8px 14px 8px 34px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; width: 240px; outline: none; }
.filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }
.record-count { font-size: 13px; color: #888; }
.btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary { background: #1a6b3c; color: #fff; }
.btn-blue { background: #1a3a5c; color: #fff; }
.btn-blue:hover { background: #2980b9; }
.btn-print { background: #6c757d; color: #fff; }
.btn-print:hover { background: #5a6268; }
.btn-download { background: #27ae60; color: #fff; }
.btn-download:hover { background: #1e8449; }
.btn-secondary { background: #f0f4f8; color: #1a6b3c; border: 1px solid #ddd; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 60vh; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table thead tr th { position: sticky; top: 0; z-index: 2; background: #1a3a5c; }
.data-table th { padding: 11px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.emp-no { font-family: monospace; font-size: 11px; color: #888; }
.timestamp { font-family: monospace; font-size: 11px; color: #888; white-space: nowrap; }
.action-text { color: #1a6b3c; }
.remarks-cell { max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-orange { background: #fef3e2; color: #e67e22; }
.badge-blue { background: #ebf5fb; color: #2980b9; }
.badge-green { background: #eafaf1; color: #27ae60; }
.badge-purple { background: #f5eef8; color: #8e44ad; }
.badge-red { background: #fdecea; color: #c0392b; }
.badge-gray { background: #f4f4f4; color: #666; }
.sig-list { display: flex; flex-wrap: wrap; gap: 4px; }
.sig-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
.sig-badge.signed { background: #eafaf1; color: #27ae60; }
.sig-badge.unsigned { background: #fef3e2; color: #e67e22; }
.action-btns { display: flex; gap: 4px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 3px; border-radius: 4px; display: inline-flex; align-items: center; }
.btn-icon:hover { background: #f0f4f8; }
.btn-icon.danger:hover { background: #fdecea; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: #fff; border-radius: 12px; width: 700px; max-width: 95vw; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f0f4f8; }
.modal-header h3 { margin: 0; color: #1a6b3c; }
.close-btn { background: none; border: none; cursor: pointer; color: #888; display: inline-flex; align-items: center; padding: 4px; border-radius: 4px; }
.close-btn:hover { background: #f0f4f8; }
.modal-body { padding: 20px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #f0f4f8; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select, .form-group textarea { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; }
.form-group input:focus, .form-group select:focus { border-color: #1a6b3c; }
.field-error { font-size: 11px; color: #c0392b; margin-top: 2px; }

/* Employee combobox */
.emp-combobox { position:relative; }
.emp-input-wrap { position:relative; display:flex; align-items:center; }
.emp-search-input { width:100%; padding:8px 32px 8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; box-sizing:border-box; }
.emp-search-input:focus { border-color:#1a6b3c; }
.emp-clear-btn { position:absolute; right:8px; background:none; border:none; cursor:pointer; color:#aaa; font-size:13px; line-height:1; padding:0; }
.emp-clear-btn:hover { color:#e74c3c; }
.emp-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:200; max-height:220px; overflow-y:auto; }
.emp-option { display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; transition:background 0.15s; }
.emp-option:hover { background:#f0f4f8; }
.emp-opt-avatar { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.emp-opt-info { display:flex; flex-direction:column; gap:1px; }
.emp-opt-name { font-size:13px; font-weight:600; color:#1a1a2e; }
.emp-opt-meta { font-size:11px; color:#888; }
.emp-option-empty { padding:12px; text-align:center; color:#aaa; font-size:13px; }
.input-readonly { background:#f8f9fa !important; color:#555; cursor:default; }
</style>
