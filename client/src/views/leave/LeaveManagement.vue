<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useLeaveStore } from '@/stores/leave'
import { useEmployeeStore } from '@/stores/employees'
import AppModal from '@/components/AppModal.vue'

const store = useLeaveStore()
const employeeStore = useEmployeeStore()

// Fetch leave records when component mounts
onMounted(() => {
  store.fetchRecords()
  document.addEventListener('mousedown', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})

const employeeDropdownRef = ref(null)

function handleClickOutside(e) {
  if (employeeDropdownRef.value && !employeeDropdownRef.value.contains(e.target)) {
    showEmployeeDropdown.value = false
  }
}

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  save:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
}

const search = ref('')
const filterType = ref('')
const filterStatus = ref('')
const showForm = ref(false)
const editId = ref(null)

// ── AppModal state ────────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const showSaveModal   = ref(false)
const deleteTarget    = ref(null)

function promptDelete(id) {
  deleteTarget.value = store.leaveRecords.find(r => r.id === id)
  showDeleteModal.value = true
}
function confirmDelete() {
  if (deleteTarget.value) store.deleteRecord(deleteTarget.value.id)
  showDeleteModal.value = false
  deleteTarget.value = null
}

const blankForm = () => ({
  employeeId: null, employeeNo: '', employeeName: '', department: '',
  leaveType: 'Vacation Leave', dateFrom: '', dateTo: '', days: 1,
  reason: '', status: 'Pending', approvedBy: '', dateApproved: '', remarks: '',
})
const form = ref(blankForm())
const formErrors = ref({ employeeId: '' })

// Employee dropdown state
const showEmployeeDropdown = ref(false)
const employeeSearch = ref('')

const filteredEmployees = computed(() => {
  const query = employeeSearch.value.toLowerCase()
  if (!query) return employeeStore.employees.slice(0, 50) // Show first 50 if no search
  return employeeStore.employees.filter(emp => {
    const fullName = `${emp.lastName}, ${emp.firstName} ${emp.middleName ? emp.middleName.charAt(0) + '.' : ''}`.toLowerCase()
    const empNo = emp.employeeNo.toLowerCase()
    return fullName.includes(query) || empNo.includes(query)
  }).slice(0, 50)
})

function selectEmployee(emp) {
  form.value.employeeId = emp.id
  form.value.employeeNo = emp.employeeNo
  form.value.employeeName = `${emp.lastName}, ${emp.firstName}${emp.middleName ? ' ' + emp.middleName.charAt(0) + '.' : ''}`.trim()
  form.value.department = emp.department
  employeeSearch.value = form.value.employeeName
  showEmployeeDropdown.value = false
  formErrors.value.employeeId = ''
}

function clearEmployeeSelection() {
  form.value.employeeId = null
  form.value.employeeNo = ''
  form.value.employeeName = ''
  form.value.department = ''
  employeeSearch.value = ''
}

function openAdd() {
  editId.value = null
  form.value = blankForm()
  formErrors.value = { employeeId: '' }
  employeeSearch.value = ''
  showForm.value = true
}
function openEdit(rec) {
  editId.value = rec.id
  form.value = { ...rec }
  formErrors.value = { employeeId: '' }
  employeeSearch.value = rec.employeeName
  showForm.value = true
}

function save() {
  formErrors.value = { employeeId: '' }
  let valid = true
  if (!form.value.employeeId) { 
    formErrors.value.employeeId = 'Please select an employee from the list.'
    valid = false 
  }
  if (!valid) return
  showSaveModal.value = true
}
function confirmSave() {
  if (editId.value) store.updateRecord(editId.value, { ...form.value })
  else store.addRecord({ ...form.value })
  showSaveModal.value = false
  showForm.value = false
}

const filtered = computed(() => store.leaveRecords.filter(r => {
  const q = search.value.toLowerCase()
  const matchSearch = !q || r.employeeName.toLowerCase().includes(q)
  const matchType   = !filterType.value   || r.leaveType === filterType.value
  const matchStatus = !filterStatus.value || r.status    === filterStatus.value
  return matchSearch && matchType && matchStatus
}))

function statusClass(s) {
  return { Pending: 'badge-orange', Approved: 'badge-green', Disapproved: 'badge-red', Cancelled: 'badge-gray' }[s] || 'badge-gray'
}
</script>

<template>
  <div class="page">
    <!-- Loading State -->
    <div v-if="store.loading" class="loading-overlay">
      <div class="spinner"></div>
      <p>Loading leave records...</p>
    </div>

    <!-- Error State -->
    <div v-if="store.error" class="error-banner">
      <strong>Error:</strong> {{ store.error }}
      <button class="btn-retry" @click="store.fetchRecords()">Retry</button>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
        <AppSelect v-model="filterType"   :options="[{ label: 'All Leave Types', value: '' }, ...store.leaveTypes.map(t => ({ label: t, value: t }))]" placeholder="All Leave Types" />
        <AppSelect v-model="filterStatus" :options="[{ label: 'All Status', value: '' }, ...store.statuses.map(s => ({ label: s, value: s }))]" placeholder="All Status" />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} record(s)</span>
        <button class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Leave
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Leave Type</th>
            <th>Date From</th><th>Date To</th><th>Days</th>
            <th>Reason</th><th>Status</th><th>Approved By</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0"><td colspan="10" class="empty-row">No leave records found.</td></tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><strong>{{ r.employeeName }}</strong><div class="sub-text">{{ r.employeeNo }}</div></td>
            <td>{{ r.department }}</td>
            <td><span class="leave-type">{{ r.leaveType }}</span></td>
            <td>{{ r.dateFrom }}</td><td>{{ r.dateTo }}</td>
            <td class="days-cell">{{ r.days }}</td>
            <td class="reason-cell">{{ r.reason }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td>{{ r.approvedBy || '—' }}</td>
            <td>
              <div class="action-btns">
                <button class="btn-icon" @click="openEdit(r)"><span class="icon-svg" v-html="svgIcons.edit"></span></button>
                <button class="btn-icon danger" @click="promptDelete(r.id)"><span class="icon-svg" v-html="svgIcons.delete"></span></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Form Modal -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit Leave Record' : 'Add Leave Request' }}</h3>
          <button class="close-btn" @click="showForm = false"><span class="icon-svg" v-html="svgIcons.close"></span></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full employee-selector" ref="employeeDropdownRef">
              <label>Select Employee</label>
              <div class="employee-input-wrapper">
                <input 
                  v-model="employeeSearch" 
                  @focus="showEmployeeDropdown = true"
                  @input="showEmployeeDropdown = true"
                  placeholder="Search by name or employee number..." 
                  class="employee-search-input"
                  autocomplete="off"
                />
                <button 
                  v-if="form.employeeId" 
                  type="button" 
                  class="clear-employee-btn" 
                  @click="clearEmployeeSelection"
                  title="Clear selection"
                >
                  <span class="icon-svg" v-html="svgIcons.close"></span>
                </button>
              </div>
              <span v-if="formErrors.employeeId" class="field-error">{{ formErrors.employeeId }}</span>
              
              <div v-if="showEmployeeDropdown && filteredEmployees.length > 0" class="employee-dropdown">
                <div 
                  v-for="emp in filteredEmployees" 
                  :key="emp.id"
                  class="employee-option"
                  :class="{ selected: emp.id === form.employeeId }"
                  @mousedown.prevent="selectEmployee(emp)"
                >
                  <div class="employee-option-main">
                    <strong>{{ emp.lastName }}, {{ emp.firstName }}{{ emp.middleName ? ' ' + emp.middleName.charAt(0) + '.' : '' }}</strong>
                    <span class="employee-option-no">{{ emp.employeeNo }}</span>
                  </div>
                  <div class="employee-option-dept">{{ emp.department }} • {{ emp.position }}</div>
                </div>
              </div>
              <div v-else-if="showEmployeeDropdown && employeeSearch && filteredEmployees.length === 0" class="employee-dropdown-empty">
                No employees found matching "{{ employeeSearch }}"
              </div>
            </div>

            <div class="form-group">
              <label>Employee No.</label>
              <input v-model="form.employeeNo" readonly disabled placeholder="Auto-filled" />
            </div>
            <div class="form-group">
              <label>Employee Name</label>
              <input v-model="form.employeeName" readonly disabled placeholder="Auto-filled" />
            </div>
            <div class="form-group">
              <label>Department</label>
              <input v-model="form.department" readonly disabled placeholder="Auto-filled" />
            </div>
            <div class="form-group"><label>Leave Type</label><AppSelect v-model="form.leaveType" :options="store.leaveTypes" /></div>
            <div class="form-group"><label>Date From</label><input v-model="form.dateFrom" type="date" /></div>
            <div class="form-group"><label>Date To</label><input v-model="form.dateTo" type="date" /></div>
            <div class="form-group"><label>No. of Days</label><input v-model.number="form.days" type="number" min="1" /></div>
            <div class="form-group"><label>Status</label><AppSelect v-model="form.status" :options="store.statuses" /></div>
            <div class="form-group"><label>Approved By</label><input v-model="form.approvedBy" /></div>
            <div class="form-group"><label>Date Approved</label><input v-model="form.dateApproved" type="date" /></div>
            <div class="form-group full"><label>Reason</label><textarea v-model="form.reason" rows="2"></textarea></div>
            <div class="form-group full"><label>Remarks</label><textarea v-model="form.remarks" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false">Cancel</button>
          <button class="btn btn-primary" @click="save"><span class="icon-svg" v-html="svgIcons.save"></span> Save</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <AppModal
      v-if="showDeleteModal"
      type="delete"
      title="Delete Leave Record"
      message="Are you sure you want to delete this leave record?"
      :detail="deleteTarget?.employeeName + ' — ' + deleteTarget?.leaveType"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Save Confirmation -->
    <AppModal
      v-if="showSaveModal"
      type="confirm"
      :title="editId ? 'Update Leave Record' : 'Add Leave Record'"
      :message="editId ? 'Save changes to this leave record?' : 'Add this new leave record?'"
      :detail="form.employeeName + ' — ' + form.leaveType"
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
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-icon { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:240px; outline:none; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.table-wrapper { overflow-x:auto; overflow-y:auto; max-height:60vh; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:12px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 12px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.sub-text { font-size:11px; color:#888; }
.leave-type { font-size:12px; color:#2980b9; font-weight:600; }
.days-cell { font-weight:700; color:#1a3a5c; text-align:center; }
.reason-cell { max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-orange { background:#fef3e2; color:#e67e22; }
.badge-green  { background:#eafaf1; color:#27ae60; }
.badge-red    { background:#fdecea; color:#c0392b; }
.badge-gray   { background:#f4f4f4; color:#666; }
.action-btns { display:flex; gap:4px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:3px; border-radius:4px; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#f0f4f8; }
.btn-icon.danger:hover { background:#fdecea; }
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
.form-group.full { grid-column:1 / -1; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input, .form-group select, .form-group textarea { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.form-group input:disabled { background:#f5f5f5; color:#888; cursor:not-allowed; }
.field-error { font-size:11px; color:#c0392b; margin-top:2px; }
.employee-selector { position:relative; }
.employee-input-wrapper { position:relative; display:flex; align-items:center; }
.employee-search-input { flex:1; padding:8px 36px 8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.employee-search-input:focus { border-color:#1a6b3c; box-shadow:0 0 0 3px rgba(26,107,60,0.1); }
.clear-employee-btn { position:absolute; right:8px; background:none; border:none; cursor:pointer; color:#888; padding:4px; border-radius:4px; display:flex; align-items:center; }
.clear-employee-btn:hover { background:#f0f4f8; color:#c0392b; }
.employee-dropdown { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); max-height:280px; overflow-y:auto; z-index:1000; margin-top:4px; }
.employee-dropdown-empty { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; padding:16px; text-align:center; color:#888; font-size:12px; z-index:1000; margin-top:4px; }
.employee-option { padding:10px 14px; cursor:pointer; border-bottom:1px solid #f0f4f8; transition:background 0.15s; }
.employee-option:last-child { border-bottom:none; }
.employee-option:hover { background:#f0f9f4; }
.employee-option.selected { background:#e8f5e9; }
.employee-option-main { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:3px; }
.employee-option-main strong { font-size:13px; color:#1a3a5c; }
.employee-option-no { font-size:11px; color:#888; background:#f0f4f8; padding:2px 8px; border-radius:4px; }
.employee-option-dept { font-size:11px; color:#666; }
.loading-overlay { position:fixed; inset:0; background:rgba(255,255,255,0.9); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:999; }
.spinner { width:40px; height:40px; border:4px solid #f0f4f8; border-top-color:#1a3a5c; border-radius:50%; animation:spin 0.8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.error-banner { background:#fdecea; color:#c0392b; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; }
.btn-retry { background:#c0392b; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; }
.btn-retry:hover { background:#a93226; }
</style>
