<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useEmployeeStore } from '@/stores/employees'
import AppSelect from '@/components/AppSelect.vue'

const router = useRouter()
const store  = useEmployeeStore()

const search         = ref('')
const filterService  = ref('')
const filterStatus   = ref('')
const filterGender   = ref('')
const filterGroup    = ref('')
const sortBy       = ref('lastName')
const sortDir      = ref('asc')
function resetPage() {}

// ── Delete modal ─────────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

function promptDelete(emp) {
  deleteTarget.value = {
    id: emp.id,
    name: `${emp.lastName}, ${emp.firstName}`,
    position: emp.position || '—',
    department: emp.department || '—',
  }
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  deleteTarget.value    = null
}

function confirmDelete() {
  if (deleteTarget.value) {
    store.deleteEmployee(deleteTarget.value.id)
  }
  cancelDelete()
}

// ── Navigate to edit ─────────────────────────────────────────────────────────
function goEdit(emp) {
  router.push(`/employees/${emp.id}/edit`)
}

// ── Icons ────────────────────────────────────────────────────────────────────
const svgIcons = {
  search:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
  add:     '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
  edit:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
  delete:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
  warn:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
  close:   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
}

function toggleSort(col) {
  if (sortBy.value === col) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortBy.value = col; sortDir.value = 'asc' }
}

const filtered = computed(() => {
  let list = store.employees.filter(e => {
    const q = search.value.toLowerCase()
    const matchSearch   = !q || e.lastName.toLowerCase().includes(q) || e.firstName.toLowerCase().includes(q) || e.employeeNo.toLowerCase().includes(q) || e.position.toLowerCase().includes(q)
    const matchStatus   = !filterStatus.value || e.employmentStatus === filterStatus.value
    const matchGender   = !filterGender.value || e.gender === filterGender.value
    const matchGroup    = !filterGroup.value  || e.employeeNo.toUpperCase().startsWith(filterGroup.value)
    const matchService  = (() => {
      if (!filterService.value) return true
      const years = getYearsOfService(e.dateHired)
      if (filterService.value === '5')  return years >= 5  && years < 10
      if (filterService.value === '10') return years >= 10
      return true
    })()
    return matchSearch && matchStatus && matchGender && matchGroup && matchService
  })
  list = [...list].sort((a, b) => {
    let va = a[sortBy.value] ?? '', vb = b[sortBy.value] ?? ''
    if (typeof va === 'string') va = va.toLowerCase()
    if (typeof vb === 'string') vb = vb.toLowerCase()
    if (va < vb) return sortDir.value === 'asc' ? -1 : 1
    if (va > vb) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })
  return list
})

function getAge(birthDate) {
  if (!birthDate) return '—'
  const today = new Date(), bd = new Date(birthDate)
  let age = today.getFullYear() - bd.getFullYear()
  const m = today.getMonth() - bd.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) age--
  return age
}

function getYearsOfService(dateHired) {
  if (!dateHired) return 0
  const today = new Date(), hired = new Date(dateHired)
  let years = today.getFullYear() - hired.getFullYear()
  const m = today.getMonth() - hired.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < hired.getDate())) years--
  return Math.max(0, years)
}

function statusClass(status) {
  return { 'Permanent':'badge-green','Casual':'badge-blue','Contractual':'badge-orange','Job Order':'badge-gray','Co-terminus':'badge-purple' }[status] || 'badge-gray'
}

function sortIcon(col) {
  if (sortBy.value !== col) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}
</script>

<template>
  <div class="page">

    <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search by name, ID, position..." />
        </div>
        <AppSelect v-model="filterService" :options="[{ label: 'All Service Years', value: '' }, { label: '5–9 Years', value: '5' }, { label: '10+ Years', value: '10' }]" placeholder="All Service Years" @update:modelValue="resetPage" />
        <AppSelect v-model="filterStatus" :options="[{ label: 'All Status', value: '' }, ...store.employmentStatuses.map(s => ({ label: s, value: s }))]" placeholder="All Status" @update:modelValue="resetPage" />
        <AppSelect v-model="filterGender" :options="[{ label: 'All Gender', value: '' }, { label: 'Male', value: 'Male' }, { label: 'Female', value: 'Female' }]" placeholder="All Gender" @update:modelValue="resetPage" />
        <AppSelect v-model="filterGroup"  :options="[{ label: 'All Groups', value: '' }, { label: 'KP', value: 'KP' }, { label: 'GEAMH', value: 'GEAMH' }]" placeholder="All Groups" @update:modelValue="resetPage" />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} record(s)</span>
        <button class="btn btn-primary" @click="router.push('/employees/new')">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Employee
        </button>
        <router-link to="/employees/birthdays" class="btn btn-secondary">🎂 Birthdays</router-link>
      </div>
    </div>

    <div class="main-layout">
      <!-- ── Table ──────────────────────────────────────────────────────────── -->
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th @click="toggleSort('employeeNo')" class="sortable">Emp No {{ sortIcon('employeeNo') }}</th>
              <th @click="toggleSort('lastName')" class="sortable">Name {{ sortIcon('lastName') }}</th>
              <th @click="toggleSort('position')" class="sortable">Position {{ sortIcon('position') }}</th>
              <th @click="toggleSort('department')" class="sortable">Department {{ sortIcon('department') }}</th>
              <th @click="toggleSort('employmentStatus')" class="sortable">Status {{ sortIcon('employmentStatus') }}</th>
              <th>Age</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="7" class="empty-row">No records found.</td>
            </tr>
            <tr v-for="emp in filtered" :key="emp.id">
              <td><span class="emp-no">{{ emp.employeeNo }}</span></td>
              <td>
                <div class="emp-name-cell">
                  <div class="emp-avatar">{{ emp.firstName[0] }}{{ emp.lastName[0] }}</div>
                  <div>
                    <strong>{{ emp.lastName }}, {{ emp.firstName }} {{ emp.middleName ? emp.middleName[0] + '.' : '' }}</strong>
                    <div class="emp-contact">{{ emp.email }}</div>
                  </div>
                </div>
              </td>
              <td>{{ emp.position }}</td>
              <td>{{ emp.department }}</td>
              <td><span class="badge" :class="statusClass(emp.employmentStatus)">{{ emp.employmentStatus }}</span></td>
              <td>{{ getAge(emp.birthDate) }}</td>
              <td>
                <div class="action-btns">
                  <button class="btn-icon" title="Edit" @click="goEdit(emp)">
                    <span class="icon-svg" v-html="svgIcons.edit"></span>
                  </button>
                  <button class="btn-icon danger" title="Delete" @click="promptDelete(emp)">
                    <span class="icon-svg" v-html="svgIcons.delete"></span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ── Delete Confirmation Modal ─────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDelete">
        <div class="modal">
          <div class="modal-icon-wrap">
            <span class="modal-icon" v-html="svgIcons.warn"></span>
          </div>
          <h3 class="modal-title">Delete Employee</h3>
          <p class="modal-message">Are you sure you want to delete this employee?</p>
          <div class="modal-employee-card">
            <div class="modal-emp-avatar">
              {{ deleteTarget?.name?.split(',')[1]?.trim()[0] }}{{ deleteTarget?.name?.split(',')[0]?.trim()[0] }}
            </div>
            <div class="modal-emp-info">
              <strong>{{ deleteTarget?.name }}</strong>
              <span>{{ deleteTarget?.position }} &bull; {{ deleteTarget?.department }}</span>
            </div>
          </div>
          <p class="modal-warning">This action cannot be undone.</p>
          <div class="modal-actions">
            <button class="btn btn-cancel" @click="cancelDelete">Cancel</button>
            <button class="btn btn-delete" @click="confirmDelete">
              <span class="icon-svg" v-html="svgIcons.delete"></span> Yes, Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.main-layout { display: flex; gap: 16px; align-items: flex-start; }

/* Toolbar */
.toolbar { position:sticky; top:0; z-index:10; background:#f0f4f8; padding:10px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-icon { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:260px; outline:none; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-primary:hover { background:#2980b9; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.btn-history { background:#1a6b3c; color:#fff; }
.btn-history:hover { background:#27ae60; }

/* Table */
.table-wrapper { flex:1; overflow-x:auto; overflow-y:auto; max-height:calc(100vh - 180px); background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); min-width:0; }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:12px 14px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table th.sortable { cursor:pointer; user-select:none; }
.data-table th.sortable:hover { background:#2980b9; }
.data-table td { padding:10px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.row-active { background:#e8f5ee !important; }
.emp-no { font-family:monospace; font-size:12px; color:#888; }
.emp-name-cell { display:flex; align-items:center; gap:10px; }
.emp-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.emp-contact { font-size:11px; color:#888; }
.badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-green  { background:#eafaf1; color:#27ae60; }
.badge-blue   { background:#ebf5fb; color:#2980b9; }
.badge-orange { background:#fef3e2; color:#e67e22; }
.badge-gray   { background:#f4f4f4; color:#666; }
.badge-purple { background:#f5eef8; color:#8e44ad; }
.action-btns { display:flex; gap:4px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; border-radius:4px; transition:background 0.2s; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#f0f4f8; }
.btn-icon.danger:hover { background:#fdecea; color:#e74c3c; }
.history-btn { color:#1a6b3c; }
.history-btn:hover { background:#e8f5ee !important; }
.empty-row { text-align:center; color:#aaa; padding:40px; }

/* Panel transition */
.panel-enter-active, .panel-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.panel-enter-from, .panel-leave-to { opacity:0; transform:translateX(20px); }

/* Delete Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(2px); }
.modal { background:#fff; border-radius:16px; padding:32px 28px 24px; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center; }
.modal-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fef3e2; display:flex; align-items:center; justify-content:center; }
.modal-icon { width:28px; height:28px; color:#e67e22; }
.modal-icon :deep(svg) { width:28px; height:28px; fill:#e67e22; }
.modal-title { margin:0; font-size:18px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; gap:8px; }
.modal-message { margin:0; font-size:14px; color:#555; }
.modal-employee-card { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; width:100%; text-align:left; }
.modal-emp-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#c0392b,#e74c3c); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.modal-emp-info { display:flex; flex-direction:column; gap:2px; }
.modal-emp-info strong { font-size:14px; color:#1a1a2e; }
.modal-emp-info span { font-size:12px; color:#888; }
.modal-warning { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
.modal-actions { display:flex; gap:10px; width:100%; margin-top:4px; }
.btn-cancel { flex:1; padding:10px; border-radius:8px; background:#f0f4f8; color:#555; border:1px solid #ddd; font-size:13px; font-weight:600; cursor:pointer; }
.btn-cancel:hover { background:#e0e8f0; }
.btn-delete { flex:1; padding:10px; border-radius:8px; background:#e74c3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.btn-delete:hover { background:#c0392b; }
.btn-delete .icon-svg :deep(svg) { fill:#fff; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }
</style>
