<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTravelOrderStore } from '@/stores/travel_orders'
import { useEmployeeStore } from '@/stores/employees'
import { usePermissions } from '@/composables/usePermissions'
import AppModal from '@/components/AppModal.vue'

const store = useTravelOrderStore()
const empStore = useEmployeeStore()
const { hasPermission, loadPermissions } = usePermissions()

// Fetch travel orders on component mount
onMounted(async () => {
  await loadPermissions()
  store.fetchRecords()
  empStore.fetchDepartments()
  empStore.fetchEmployees()
})

// Department options for dropdowns
const departmentOptions = computed(() => [
  { label: 'All Departments', value: '' },
  ...empStore.departments.map(d => ({ label: d, value: d }))
])

// Employee search/autocomplete
const empSearch = ref('')
const showEmpDropdown = ref(false)

const filteredEmployees = computed(() => {
  const q = empSearch.value.toLowerCase().trim()
  if (!q) return empStore.employees.slice(0, 20)
  return empStore.employees.filter(e => {
    const full = `${e.lastName} ${e.firstName} ${e.middleName ?? ''}`.toLowerCase()
    const no   = (e.employeeNo ?? '').toLowerCase()
    return full.includes(q) || no.includes(q)
  }).slice(0, 20)
})

function fullName(e) {
  const mid = e.middleName ? ` ${e.middleName.charAt(0)}.` : ''
  return `${e.lastName}, ${e.firstName}${mid}`
}

function selectEmployee(emp) {
  form.value.employeeId  = emp.id
  form.value.employeeNo  = emp.employeeNo
  form.value.employeeName = fullName(emp)
  form.value.department  = emp.department
  empSearch.value        = fullName(emp)
  showEmpDropdown.value  = false
  formErrors.value.employeeName = ''
  formErrors.value.employeeNo   = ''
}

function onEmpSearchBlur() {
  // Delay so click on dropdown item fires first
  setTimeout(() => { showEmpDropdown.value = false }, 180)
}

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  save: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
}
const search = ref('')
const filterStatus = ref('')
const filterDept   = ref('')
const filterDateFrom = ref('')
const filterDateTo   = ref('')
const showForm = ref(false)
const editId = ref(null)

const blankForm = () => ({
  employeeId: null, employeeNo: '', employeeName: '', department: '', destination: '',
  purpose: '', dateFrom: '', dateTo: '', days: 1,
  transport: 'Public Transport', approvedBy: '', status: 'Pending', remarks: '',
})
const form = ref(blankForm())

const formErrors = ref({ employeeNo: '', employeeName: '' })


function openAdd() {
  editId.value = null
  form.value = blankForm()
  empSearch.value = ''
  formErrors.value = { employeeNo: '', employeeName: '' }
  showForm.value = true
}
function openEdit(r) {
  editId.value = r.id
  form.value = { ...r }
  empSearch.value = r.employeeName
  formErrors.value = { employeeNo: '', employeeName: '' }
  showForm.value = true
}
const showSaveModal = ref(false)
const saving = ref(false)

function save() {
  formErrors.value = { employeeNo: '', employeeName: '' }
  let valid = true
  if (!form.value.employeeNo.trim()) { formErrors.value.employeeNo = 'Employee No. is required.'; valid = false }
  if (!form.value.employeeName.trim()) { formErrors.value.employeeName = 'Employee Name is required.'; valid = false }
  if (!valid) return
  showSaveModal.value = true
}
async function confirmSave() {
  saving.value = true
  try {
    if (editId.value) {
      await store.updateRecord(editId.value, form.value)
    } else {
      await store.addRecord(form.value)
    }
    showSaveModal.value = false
    showForm.value = false
  } catch (err) {
    alert('Error saving travel order: ' + err.message)
  } finally {
    saving.value = false
  }
}
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

function deleteRec(id) {
  deleteTarget.value = store.travelOrders.find(r => r.id === id)
  showDeleteModal.value = true
}
async function confirmDelete() {
  if (deleteTarget.value) {
    try {
      await store.deleteRecord(deleteTarget.value.id)
      showDeleteModal.value = false
      deleteTarget.value = null
    } catch (err) {
      alert('Error deleting travel order: ' + err.message)
    }
  }
}

const filtered = computed(() => store.travelOrders.filter(r => {
  const q = search.value.toLowerCase()
  const matchSearch = !q || r.employeeName.toLowerCase().includes(q) || r.destination.toLowerCase().includes(q)
  const matchStatus = !filterStatus.value || r.status === filterStatus.value
  const matchDept   = !filterDept.value   || r.department === filterDept.value
  const matchFrom   = !filterDateFrom.value || r.dateFrom >= filterDateFrom.value
  const matchTo     = !filterDateTo.value   || r.dateTo   <= filterDateTo.value
  return matchSearch && matchStatus && matchDept && matchFrom && matchTo
}))

function printRecords() {
  const rows = filtered.value.map(r =>
    `<tr><td>${r.employeeName}</td><td>${r.department}</td><td>${r.destination}</td><td>${r.purpose}</td><td>${r.dateFrom}</td><td>${r.dateTo}</td><td>${r.days}</td><td>${r.transport}</td><td>${r.status}</td></tr>`
  ).join('')
  const logoUrl = window.location.origin + '/GEAMH LOGO.png'
  const html = `<html><head><title>Travel Orders</title><style>
    body{font-family:Arial,sans-serif;padding:24px}
    .ph{display:flex;align-items:center;gap:14px;border-bottom:2px solid #1a3a5c;padding-bottom:10px;margin-bottom:14px}
    .ph img{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid #1a6b3c}
    .ph h2{margin:0;font-size:15px;color:#1a3a5c}.ph p{margin:2px 0 0;font-size:11px;color:#555}
    .meta{font-size:11px;color:#888;margin-bottom:12px}
    table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#1a3a5c;color:#fff}
    tr:nth-child(even){background:#f9fafb}
  </style></head><body>
    <div class="ph"><img src="${logoUrl}" alt="GEAMH"/><div><h2>General Emilio Aguinaldo Memorial Hospital</h2><p>Human Resource Information System (HRIS)</p></div></div>
    <div class="meta">Travel Orders &mdash; Printed: ${new Date().toLocaleString('en-PH',{hour12:true})}</div>
    <table><thead><tr><th>Employee</th><th>Dept</th><th>Destination</th><th>Purpose</th><th>From</th><th>To</th><th>Days</th><th>Transport</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>
    <script>window.onload=function(){window.print()}<\/script>
  </body></html>`
  const w = window.open('', '_blank')
  w.document.write(html)
  w.document.close()
}

function statusClass(s) {
  return s === 'Approved' ? 'badge-green' : s === 'Pending' ? 'badge-orange' : 'badge-red'
}
</script>

<template>
  <div class="page">
    <!-- Loading State -->
    <div v-if="store.loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading travel orders...</p>
    </div>

    <!-- Error State -->
    <div v-if="store.error" class="error-banner">
      <strong>⚠️ Error:</strong> {{ store.error }}
      <button class="btn-retry" @click="store.fetchRecords()">Retry</button>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee, destination..." />
        </div>
        <AppSelect
          v-model="filterStatus"
          :options="[{ label: 'All Status', value: '' }, { label: 'Pending', value: 'Pending' }, { label: 'Approved', value: 'Approved' }, { label: 'Disapproved', value: 'Disapproved' }]"
          placeholder="All Status"
        />
        <AppSelect
          v-model="filterDept"
          :options="departmentOptions"
          placeholder="All Departments"
        />
        <input v-model="filterDateFrom" type="date" class="filter-input" title="Date From" />
        <input v-model="filterDateTo"   type="date" class="filter-input" title="Date To" />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} record(s)</span>
        <button class="btn btn-print" @click="printRecords">🖨 Print</button>
        <button v-if="hasPermission('Travel Orders', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add T.O.
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Destination</th>
            <th>Purpose</th><th>Date From</th><th>Date To</th>
            <th>Days</th><th>Transport</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0"><td colspan="10" class="empty-row">No T.O. records found.</td></tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><strong>{{ r.employeeName }}</strong><div class="sub-text">{{ r.employeeNo }}</div></td>
            <td>{{ r.department }}</td>
            <td>{{ r.destination }}</td>
            <td class="purpose-cell">{{ r.purpose }}</td>
            <td>{{ r.dateFrom }}</td>
            <td>{{ r.dateTo }}</td>
            <td class="days-cell">{{ r.days }}</td>
            <td>{{ r.transport }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td>
              <div class="action-btns">
                <button v-if="hasPermission('Travel Orders', 'Edit')" class="btn-icon" @click="openEdit(r)">
                  <span class="icon-svg" v-html="svgIcons.edit"></span>
                </button>
                <button v-if="hasPermission('Travel Orders', 'Delete')" class="btn-icon danger" @click="deleteRec(r.id)">
                  <span class="icon-svg" v-html="svgIcons.delete"></span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit T.O.' : 'Add Travel Order' }}</h3>
          <button class="close-btn" @click="showForm = false">
            <span class="icon-svg" v-html="svgIcons.close"></span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Employee</label>
              <div class="emp-search-wrap">
                <input
                  v-model="empSearch"
                  class="emp-search-input"
                  placeholder="Search by name or employee no..."
                  autocomplete="off"
                  @focus="showEmpDropdown = true"
                  @blur="onEmpSearchBlur"
                  @input="showEmpDropdown = true"
                />
                <div v-if="showEmpDropdown && filteredEmployees.length" class="emp-dropdown">
                  <div
                    v-for="emp in filteredEmployees"
                    :key="emp.id"
                    class="emp-option"
                    @mousedown.prevent="selectEmployee(emp)"
                  >
                    <span class="emp-opt-name">{{ fullName(emp) }}</span>
                    <span class="emp-opt-meta">{{ emp.employeeNo }} · {{ emp.department }}</span>
                  </div>
                </div>
                <div v-if="showEmpDropdown && empSearch && !filteredEmployees.length" class="emp-dropdown">
                  <div class="emp-option emp-no-result">No employees found.</div>
                </div>
              </div>
              <div v-if="form.employeeName" class="emp-selected-info">
                <span class="emp-tag">✓ {{ form.employeeName }}</span>
                <span class="emp-tag-sub">{{ form.employeeNo }} · {{ form.department }}</span>
              </div>
              <span v-if="formErrors.employeeName" class="field-error">{{ formErrors.employeeName }}</span>
            </div>
            <div class="form-group"><label>Department</label>
              <AppSelect
                v-model="form.department"
                :options="empStore.departments"
                placeholder="Select department..."
              />
            </div>
            <div class="form-group full"><label>Destination</label><input v-model="form.destination" /></div>
            <div class="form-group full"><label>Purpose</label><textarea v-model="form.purpose" rows="2"></textarea></div>
            <div class="form-group"><label>Date From</label><input v-model="form.dateFrom" type="date" /></div>
            <div class="form-group"><label>Date To</label><input v-model="form.dateTo" type="date" /></div>
            <div class="form-group"><label>No. of Days</label><input v-model.number="form.days" type="number" min="1" /></div>
            <div class="form-group"><label>Transport</label>
              <AppSelect v-model="form.transport" :options="['Public Transport', 'Government Vehicle', 'Private Vehicle']" />
            </div>
            <div class="form-group"><label>Approved By</label><input v-model="form.approvedBy" /></div>
            <div class="form-group"><label>Status</label>
              <AppSelect v-model="form.status" :options="['Pending', 'Approved', 'Disapproved']" />
            </div>
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
    <!-- Delete Confirmation -->
    <AppModal
      v-if="showDeleteModal"
      type="delete"
      title="Delete Travel Order"
      message="Are you sure you want to delete this travel order?"
      :detail="deleteTarget?.employeeName + ' — ' + deleteTarget?.destination"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Save Confirmation -->
    <AppModal
      v-if="showSaveModal"
      type="confirm"
      :title="editId ? 'Update Travel Order' : 'Add Travel Order'"
      :message="editId ? 'Save changes to this travel order?' : 'Add this new travel order?'"
      :detail="form.employeeName + ' — ' + form.destination"
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
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.search-wrap { position: relative; display: inline-flex; align-items: center; }
.search-icon { position: absolute; left: 10px; color: #aaa; pointer-events: none; }
.search-input { padding: 8px 14px 8px 34px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; width: 220px; outline: none; height: 36px; box-sizing: border-box; }
.filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; height: 36px; box-sizing: border-box; }
.filter-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; height: 36px; box-sizing: border-box; width: 150px; color: #333; }
.filter-input[type="date"] { width: 150px; cursor: pointer; }
.filter-input:focus { border-color: #1a3a5c; }
.search-input:focus { border-color: #1a3a5c; }
.record-count { font-size: 13px; color: #888; }
.btn { padding: 0 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; height: 36px; box-sizing: border-box; }
.btn-primary { background: #1a3a5c; color: #fff; }
.btn-secondary { background: #f0f4f8; color: #1a3a5c; border: 1px solid #ddd; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 60vh; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table thead tr th { position: sticky; top: 0; z-index: 2; background: #1a3a5c; }
.data-table th { padding: 11px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.sub-text { font-size: 11px; color: #888; }
.purpose-cell { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.days-cell { font-weight: 700; text-align: center; }
.badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-orange { background: #fef3e2; color: #e67e22; }
.badge-green { background: #eafaf1; color: #27ae60; }
.badge-red { background: #fdecea; color: #c0392b; }
.action-btns { display: flex; gap: 4px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 3px; border-radius: 4px; display: inline-flex; align-items: center; }
.btn-icon:hover { background: #f0f4f8; }
.btn-icon.danger:hover { background: #fdecea; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: #fff; border-radius: 12px; width: 700px; max-width: 95vw; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f0f4f8; }
.modal-header h3 { margin: 0; color: #1a3a5c; }
.close-btn { background: none; border: none; cursor: pointer; color: #888; display: inline-flex; align-items: center; padding: 4px; border-radius: 4px; }
.close-btn:hover { background: #f0f4f8; }
.modal-body { padding: 20px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #f0f4f8; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select, .form-group textarea { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; }
.field-error { font-size: 11px; color: #c0392b; margin-top: 2px; }
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

/* Employee search autocomplete */
.emp-search-wrap { position: relative; }
.emp-search-input {
  width: 100%; padding: 8px 12px; border: 1px solid #ddd;
  border-radius: 6px; font-size: 13px; outline: none;
  box-sizing: border-box;
}
.emp-search-input:focus { border-color: #1a3a5c; }
.emp-dropdown {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0;
  background: #fff; border: 1px solid #ddd; border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 9999;
  max-height: 220px; overflow-y: auto;
}
.emp-option {
  padding: 9px 14px; cursor: pointer; display: flex;
  flex-direction: column; gap: 2px; transition: background 0.15s;
}
.emp-option:hover { background: #f0f9f4; }
.emp-opt-name { font-size: 13px; font-weight: 600; color: #1a3a5c; }
.emp-opt-meta { font-size: 11px; color: #888; }
.emp-no-result { color: #aaa; font-size: 13px; cursor: default; }
.emp-selected-info {
  display: flex; align-items: center; gap: 10px;
  margin-top: 6px; flex-wrap: wrap;
}
.emp-tag {
  font-size: 12px; font-weight: 600; color: #1a6b3c;
  background: #eafaf1; padding: 3px 10px; border-radius: 12px;
}
.emp-tag-sub { font-size: 11px; color: #888; }
</style>
