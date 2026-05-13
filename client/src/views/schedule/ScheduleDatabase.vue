<script setup>
import { ref, computed } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useEmployeeStore } from '@/stores/employees'
import { useAuthStore } from '@/stores/auth'

const store    = useScheduleStore()
const empStore = useEmployeeStore()
const auth     = useAuthStore()

// ── Icons ────────────────────────────────────────────────────────────────────
const svgIcons = {
  search:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  save:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  warn:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
}

// ── Constants ────────────────────────────────────────────────────────────────
const ALL_DAYS     = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MAX_WORK_DAYS = 6

const SHIFT_TIMES = {
  Morning:   '07:00 AM - 03:00 PM',
  Afternoon: '03:00 PM - 11:00 PM',
  Night:     '11:00 PM - 07:00 AM',
  Split:     '06:00 AM - 10:00 AM / 02:00 PM - 06:00 PM',
  Flexible:  'Flexible',
}

// ── Form state ───────────────────────────────────────────────────────────────
function blankForm() {
  const defaultDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  return {
    employeeNo:    '',
    employeeName:  '',
    department:    '',
    shift:         'Morning',
    shiftTime:     '07:00 AM - 03:00 PM',
    days:          [...defaultDays],
    effectiveDate: '',
    endDate:       '',
    restDay:       ALL_DAYS.filter(d => !defaultDays.includes(d)).join(', '),
  }
}

const form     = ref(blankForm())
const showForm = ref(false)
const editId   = ref(null)
const saving   = ref(false)
const showConfirm = ref(false)

// ── Delete modal state ───────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

// ── Toolbar filters ──────────────────────────────────────────────────────────
const search      = ref('')
const filterDept  = ref('')
const filterShift = ref('')

// ── Employee combobox ────────────────────────────────────────────────────────
const empSearch   = ref('')
const empDropOpen = ref(false)

const filteredEmps = computed(() => {
  const q = empSearch.value.toLowerCase().trim()
  if (!q) return empStore.employees.slice(0, 50)
  return empStore.employees.filter(e =>
    e.lastName.toLowerCase().includes(q) ||
    e.firstName.toLowerCase().includes(q) ||
    e.employeeNo.toLowerCase().includes(q)
  ).slice(0, 50)
})

function selectEmployee(emp) {
  form.value.employeeNo   = emp.employeeNo
  form.value.employeeName = `${emp.lastName}, ${emp.firstName}`
  form.value.department   = emp.department
  empSearch.value   = `${emp.employeeNo} — ${emp.lastName}, ${emp.firstName}`
  empDropOpen.value = false
}

function onEmpSearchInput() {
  empDropOpen.value       = true
  form.value.employeeNo   = ''
  form.value.employeeName = ''
  form.value.department   = ''
}

function onEmpBlur() {
  setTimeout(() => { empDropOpen.value = false }, 180)
}

// ── Days logic ───────────────────────────────────────────────────────────────
const restDays = computed(() =>
  ALL_DAYS.filter(d => !form.value.days.includes(d))
)

function onDayChange() {
  form.value.restDay = restDays.value.join(', ')
}

function isDayDisabled(day) {
  return form.value.days.length >= MAX_WORK_DAYS && !form.value.days.includes(day)
}

function onShiftChange() {
  form.value.shiftTime = SHIFT_TIMES[form.value.shift] || ''
}

// ── Open modals ──────────────────────────────────────────────────────────────
function openAdd() {
  editId.value      = null
  form.value        = blankForm()
  empSearch.value   = ''
  empDropOpen.value = false
  showForm.value    = true
}

function openEdit(s) {
  editId.value = s.id
  form.value   = {
    employeeNo:    s.employeeNo,
    employeeName:  s.employeeName,
    department:    s.department,
    shift:         s.shift,
    shiftTime:     s.shiftTime,
    days:          [...(s.days ?? [])],
    effectiveDate: s.effectiveDate,
    endDate:       s.endDate,
    restDay:       ALL_DAYS.filter(d => !(s.days ?? []).includes(d)).join(', '),
  }
  empSearch.value   = s.employeeNo ? `${s.employeeNo} — ${s.employeeName}` : ''
  empDropOpen.value = false
  showForm.value    = true
}

// ── Save ─────────────────────────────────────────────────────────────────────
async function save() {
  if (saving.value) return
  // Show confirmation only for updates
  if (editId.value) { showConfirm.value = true; return }
  await doSave()
}

async function doSave() {
  showConfirm.value = false
  saving.value = true
  try {
    if (editId.value) {
      await store.updateSchedule(editId.value, { ...form.value })
    } else {
      await store.addSchedule({ ...form.value })
    }
    showForm.value = false
  } catch (e) {
    alert('Failed to save schedule: ' + e.message)
  } finally {
    saving.value = false
  }
}

// ── Delete ────────────────────────────────────────────────────────────────────
function promptDelete(s) {
  deleteTarget.value  = s
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  deleteTarget.value  = null
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await store.deleteSchedule(deleteTarget.value.id)
  } catch (e) {
    alert('Failed to delete: ' + e.message)
  } finally {
    cancelDelete()
  }
}

// ── Approval workflow ─────────────────────────────────────────────────────────
// Schedules need approval before they can be edited
// Status: Pending → Approved / Rejected
const filterApproval = ref('')

function approveSchedule(s) {
  store.updateSchedule(s.id, { ...s, approvalStatus: 'Approved', approvedBy: 'HR AMELA', approvedAt: auth.nowTimestamp() })
}
function rejectSchedule(s) {
  store.updateSchedule(s.id, { ...s, approvalStatus: 'Rejected' })
}

// Only allow editing approved schedules
function canEditSchedule(s) {
  return !s.approvalStatus || s.approvalStatus === 'Approved'
}

function approvalBadge(s) {
  if (!s.approvalStatus || s.approvalStatus === 'Pending') return 'badge-orange'
  if (s.approvalStatus === 'Approved') return 'badge-green'
  return 'badge-red'
}
// ── Filtered table list ───────────────────────────────────────────────────────
const filtered = computed(() =>
  store.schedules.filter(s => {
    const q = search.value.toLowerCase()
    const matchSearch = !q || s.employeeName.toLowerCase().includes(q) || s.employeeNo.toLowerCase().includes(q)
    const matchDept     = !filterDept.value     || s.department === filterDept.value
    const matchShift    = !filterShift.value    || s.shift === filterShift.value
    const matchApproval = !filterApproval.value || (s.approvalStatus || 'Pending') === filterApproval.value
    return matchSearch && matchDept && matchShift && matchApproval
  })
)

function shiftColor(shift) {
  const map = {
    Morning: 'badge-yellow', Afternoon: 'badge-orange',
    Night: 'badge-blue', Split: 'badge-purple', Flexible: 'badge-gray',
  }
  return map[shift] || 'badge-gray'
}
</script>

<template>
  <div class="page">

    <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
        <AppSelect
          v-model="filterDept"
          :options="[{ label: 'All Departments', value: '' }, ...empStore.departments.map(d => ({ label: d, value: d }))]"
          placeholder="All Departments"
        />
        <AppSelect
          v-model="filterShift"
          :options="[{ label: 'All Shifts', value: '' }, ...store.shifts.map(s => ({ label: s, value: s }))]"
          placeholder="All Shifts"
        />
        <AppSelect
          v-model="filterApproval"
          :options="[{ label: 'All Approval', value: '' }, { label: 'Pending', value: 'Pending' }, { label: 'Approved', value: 'Approved' }, { label: 'Rejected', value: 'Rejected' }]"
          placeholder="All Approval"
        />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} schedule(s)</span>
        <button class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Schedule
        </button>
      </div>
    </div>

    <!-- ── Table ──────────────────────────────────────────────────────────── -->
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Shift</th>
            <th>Shift Time</th><th>Days</th><th>Effective Date</th>
            <th>End Date</th><th>Rest Day</th><th>Approval</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading"><td colspan="9" class="empty-row">Loading...</td></tr>
          <tr v-else-if="filtered.length === 0"><td colspan="9" class="empty-row">No schedules found.</td></tr>
          <tr v-for="s in filtered" :key="s.id">
            <td>
              <strong>{{ s.employeeName }}</strong>
              <div class="sub-text">{{ s.employeeNo }}</div>
            </td>
            <td>{{ s.department }}</td>
            <td><span class="badge" :class="shiftColor(s.shift)">{{ s.shift }}</span></td>
            <td class="shift-time">{{ s.shiftTime }}</td>
            <td>
              <div class="days-row">
                <span v-for="d in ALL_DAYS" :key="d" class="day-chip" :class="{ active: (s.days ?? []).includes(d) }">
                  {{ d }}
                </span>
              </div>
            </td>
            <td>{{ s.effectiveDate }}</td>
            <td>{{ s.endDate }}</td>
            <td class="rest-day">{{ s.restDay }}</td>
            <td>
              <div class="approval-cell">
                <span class="badge" :class="approvalBadge(s)">{{ s.approvalStatus || 'Pending' }}</span>
                <div v-if="!s.approvalStatus || s.approvalStatus === 'Pending'" class="approval-btns">
                  <button class="btn-approve" @click="approveSchedule(s)" title="Approve">✓</button>
                  <button class="btn-reject"  @click="rejectSchedule(s)"  title="Reject">✗</button>
                </div>
                <div v-if="s.approvalStatus === 'Approved'" class="approved-by">by {{ s.approvedBy }}</div>
              </div>
            </td>
            <td>
              <div class="action-btns">
                <button class="btn-icon" @click="openEdit(s)" :disabled="!canEditSchedule(s)" :title="canEditSchedule(s) ? 'Edit' : 'Needs approval first'">
                  <span class="icon-svg" v-html="svgIcons.edit"></span>
                </button>
                <button class="btn-icon danger" @click="promptDelete(s)">
                  <span class="icon-svg" v-html="svgIcons.delete"></span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit Schedule' : 'Add Schedule' }}</h3>
          <button class="close-btn" @click="showForm = false">
            <span class="icon-svg" v-html="svgIcons.close"></span>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-grid">

            <!-- Employee searchable combobox -->
            <div class="form-group full">
              <label>Employee</label>
              <div class="emp-combobox">
                <input
                  v-model="empSearch"
                  class="emp-search-input"
                  placeholder="Type name or employee no..."
                  @input="onEmpSearchInput"
                  @focus="empDropOpen = true"
                  @blur="onEmpBlur"
                  autocomplete="off"
                />
                <div v-if="empDropOpen && filteredEmps.length" class="emp-dropdown">
                  <div
                    v-for="emp in filteredEmps"
                    :key="emp.id"
                    class="emp-option"
                    @mousedown.prevent="selectEmployee(emp)"
                  >
                    <span class="emp-opt-no">{{ emp.employeeNo }}</span>
                    <span class="emp-opt-name">{{ emp.lastName }}, {{ emp.firstName }}</span>
                    <span class="emp-opt-dept">{{ emp.department }}</span>
                  </div>
                </div>
                <div v-if="empDropOpen && !filteredEmps.length" class="emp-dropdown">
                  <div class="emp-option-empty">No employees found.</div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Employee Name</label>
              <input v-model="form.employeeName" readonly class="readonly-input" />
            </div>
            <div class="form-group">
              <label>Department</label>
              <input v-model="form.department" readonly class="readonly-input" />
            </div>
            <div class="form-group">
              <label>Shift</label>
              <AppSelect v-model="form.shift" :options="store.shifts" @update:modelValue="onShiftChange" />
            </div>
            <div class="form-group">
              <label>Shift Time</label>
              <input v-model="form.shiftTime" />
            </div>
            <div class="form-group">
              <label>Effective Date</label>
              <input v-model="form.effectiveDate" type="date" />
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input v-model="form.endDate" type="date" />
            </div>
            <div class="form-group">
              <label>Rest Day</label>
              <input v-model="form.restDay" readonly class="readonly-input" />
            </div>

            <!-- Working days picker -->
            <div class="form-group full">
              <label>
                Working Days
                <span class="days-count">({{ form.days.length }}/{{ MAX_WORK_DAYS }} selected)</span>
              </label>
              <div class="days-picker">
                <label
                  v-for="d in ALL_DAYS"
                  :key="d"
                  class="day-toggle"
                  :class="{ selected: form.days.includes(d), disabled: isDayDisabled(d) }"
                >
                  <input
                    type="checkbox"
                    :value="d"
                    v-model="form.days"
                    :disabled="isDayDisabled(d)"
                    @change="onDayChange"
                  />
                  {{ d }}
                </label>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-primary" @click="save" :disabled="saving">
            <span class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Delete Confirmation Modal ─────────────────────────────────────── -->
  <Transition name="modal">
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDelete">
      <div class="del-modal">
        <div class="del-icon-wrap">
          <span class="del-icon" v-html="svgIcons.warn"></span>
        </div>
        <h3 class="del-title">Delete Schedule</h3>
        <p class="del-message">Are you sure you want to delete this schedule?</p>

        <div class="del-card">
          <div class="del-card-row">
            <span class="del-label">Employee</span>
            <span class="del-value">{{ deleteTarget?.employeeName }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Emp No.</span>
            <span class="del-value mono">{{ deleteTarget?.employeeNo }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Department</span>
            <span class="del-value">{{ deleteTarget?.department || '—' }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Shift</span>
            <span class="del-value">
              <span class="badge" :class="shiftColor(deleteTarget?.shift)">{{ deleteTarget?.shift }}</span>
            </span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Days</span>
            <span class="del-value">
              <span
                v-for="d in ALL_DAYS" :key="d"
                class="day-chip"
                :class="{ active: (deleteTarget?.days ?? []).includes(d) }"
              >{{ d }}</span>
            </span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Effective</span>
            <span class="del-value">{{ deleteTarget?.effectiveDate || '—' }}</span>
          </div>
        </div>

        <p class="del-warning">This action cannot be undone.</p>

        <div class="del-actions">
          <button class="btn btn-cancel" @click="cancelDelete">Cancel</button>
          <button class="btn btn-delete" @click="confirmDelete">
            <span class="icon-svg" v-html="svgIcons.delete"></span>
            Yes, Delete
          </button>
        </div>
      </div>
    </div>
  </Transition>
  <!-- ── Update Confirmation Modal ─────────────────────────────────────── -->
  <Transition name="modal">
    <div v-if="showConfirm" class="modal-overlay" @click.self="showConfirm = false">
      <div class="confirm-modal">
        <div class="confirm-icon-wrap">
          <span class="icon-svg lg" v-html="svgIcons.save"></span>
        </div>
        <h3 class="confirm-title">Save Changes</h3>
        <p class="confirm-msg">Are you sure you want to update this schedule?</p>
        <div class="confirm-card">
          <div class="part-avatar">{{ form.employeeName?.split(',')[1]?.trim()[0] }}{{ form.employeeName?.split(',')[0]?.trim()[0] }}</div>
          <div>
            <strong>{{ form.employeeName }}</strong>
            <span>{{ form.shift }} · {{ form.shiftTime }}</span>
            <span>{{ form.department }}</span>
          </div>
        </div>
        <p class="confirm-note">All changes will be saved to the database.</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="showConfirm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-confirm-ok" @click="doSave" :disabled="saving">
            <span class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Yes, Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }

/* Toolbar */
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-icon { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:240px; outline:none; }
.record-count { font-size:13px; color:#888; }

/* Buttons */
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:background 0.2s; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-primary:hover:not(:disabled) { background:#2980b9; }
.btn-primary:disabled { background:#a0b4c8; cursor:not-allowed; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }

/* Table */
.table-wrapper { overflow-x:auto; overflow-y:auto; max-height:60vh; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:12px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 12px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.sub-text { font-size:11px; color:#888; }
.shift-time { font-size:11px; color:#555; white-space:nowrap; }
.rest-day { font-size:11px; color:#888; }
.days-row { display:flex; gap:3px; flex-wrap:wrap; }
.day-chip { padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#f0f4f8; color:#aaa; }
.day-chip.active { background:#1a3a5c; color:#fff; }
.badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-yellow { background:#fef9e7; color:#b7950b; }
.badge-orange { background:#fef3e2; color:#e67e22; }
.badge-blue { background:#ebf5fb; color:#2980b9; }
.badge-purple { background:#f5eef8; color:#8e44ad; }
.badge-gray { background:#f4f4f4; color:#666; }
.action-btns { display:flex; gap:4px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:3px; border-radius:4px; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#f0f4f8; }
.btn-icon.danger:hover { background:#fdecea; }
.btn-icon:disabled { opacity:0.3; cursor:not-allowed; }
.approval-cell { display:flex; flex-direction:column; gap:4px; }
.approval-btns { display:flex; gap:4px; }
.btn-approve { background:#eafaf1; color:#27ae60; border:1px solid #27ae60; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-approve:hover { background:#27ae60; color:#fff; }
.btn-reject  { background:#fdecea; color:#e74c3c; border:1px solid #e74c3c; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-reject:hover  { background:#e74c3c; color:#fff; }
.approved-by { font-size:10px; color:#888; }
.empty-row { text-align:center; color:#aaa; padding:40px; }

/* Modal */
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
.form-group input, .form-group select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.readonly-input { background:#f8f9fa !important; color:#555; cursor:default; }

/* Days picker */
.days-picker { display:flex; gap:8px; flex-wrap:wrap; margin-top:4px; }
.days-count { font-size:11px; color:#888; font-weight:400; margin-left:6px; }
.day-toggle {
  display:flex; align-items:center; gap:6px;
  padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600;
  cursor:pointer; border:2px solid #ddd; background:#f9fafb;
  color:#888; transition:all 0.15s; user-select:none;
}
.day-toggle input[type="checkbox"] { display:none; }
.day-toggle.selected { background:#1a3a5c; border-color:#1a3a5c; color:#fff; }
.day-toggle.disabled { background:#f4f4f4; border-color:#eee; color:#ccc; cursor:not-allowed; opacity:0.6; }
.day-toggle:not(.selected):not(.disabled):hover { border-color:#1a3a5c; color:#1a3a5c; background:#e8f0fe; }

/* Employee combobox */
.emp-combobox { position:relative; }
.emp-search-input { width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; box-sizing:border-box; }
.emp-search-input:focus { border-color:#1a3a5c; }
.emp-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; max-height:220px; overflow-y:auto; }
.emp-option { display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; transition:background 0.15s; border-bottom:1px solid #f5f5f5; }
.emp-option:last-child { border-bottom:none; }
.emp-option:hover { background:#f0f9f4; }
.emp-opt-no { font-family:monospace; font-size:11px; color:#888; flex-shrink:0; min-width:90px; }
.emp-opt-name { font-size:13px; font-weight:600; color:#1a1a2e; flex:1; }
.emp-opt-dept { font-size:11px; color:#aaa; flex-shrink:0; }
.emp-option-empty { padding:12px; text-align:center; color:#aaa; font-size:13px; }

/* ── Delete modal ── */
.del-modal {
  background: #fff; border-radius: 16px; padding: 32px 28px 24px;
  width: 100%; max-width: 420px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  text-align: center;
}
.del-icon-wrap {
  width: 56px; height: 56px; border-radius: 50%;
  background: #fef3e2;
  display: flex; align-items: center; justify-content: center;
}
.del-icon { width: 28px; height: 28px; color: #e67e22; }
.del-icon :deep(svg) { width: 28px; height: 28px; fill: #e67e22; }
.del-title   { margin: 0; font-size: 18px; font-weight: 700; color: #1a1a2e; }
.del-message { margin: 0; font-size: 14px; color: #555; }
.del-warning { margin: 0; font-size: 12px; color: #e74c3c; font-weight: 600; }
.del-card {
  width: 100%; background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 10px; padding: 12px 16px;
  display: flex; flex-direction: column; gap: 8px; text-align: left;
}
.del-card-row { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.del-label { font-weight: 600; color: #888; font-size: 11px; min-width: 72px; }
.del-value { color: #1a1a2e; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.del-value.mono { font-family: monospace; font-size: 12px; }
.del-actions { display: flex; gap: 10px; width: 100%; margin-top: 4px; }
.btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #f0f4f8; color: #555; border: 1px solid #ddd;
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-cancel:hover { background: #e0e8f0; }
.btn-delete {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #e74c3c; color: #fff; border: none;
  font-size: 13px; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-delete:hover { background: #c0392b; }
.btn-delete .icon-svg :deep(svg) { fill: #fff; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .del-modal, .modal-leave-active .del-modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .del-modal, .modal-leave-to .del-modal { transform: scale(0.95); opacity: 0; }

/* ── Update Confirmation modal ── */
.confirm-modal {
  background:#fff; border-radius:16px; padding:32px 28px 24px;
  width:100%; max-width:420px;
  box-shadow:0 20px 60px rgba(0,0,0,0.2);
  display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center;
}
.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#e8f0fe; display:flex; align-items:center; justify-content:center; }
.icon-svg.lg { width:28px; height:28px; }
.icon-svg.lg :deep(svg) { width:28px; height:28px; fill:#1a3a5c; }
.confirm-title { margin:0; font-size:18px; font-weight:700; color:#1a1a2e; }
.confirm-msg { margin:0; font-size:14px; color:#555; }
.confirm-card { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; width:100%; text-align:left; }
.confirm-card .part-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.confirm-card strong { display:block; font-size:14px; color:#1a1a2e; }
.confirm-card span { display:block; font-size:12px; color:#888; }
.confirm-note { margin:0; font-size:12px; color:#1a3a5c; font-weight:600; }
.confirm-actions { display:flex; gap:10px; width:100%; margin-top:4px; }
.btn-confirm-ok { flex:1; padding:10px; border-radius:8px; background:#1a3a5c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; transition:background 0.2s; }
.btn-confirm-ok:hover:not(:disabled) { background:#2980b9; }
.btn-confirm-ok:disabled { background:#a0b4c8; cursor:not-allowed; }
.btn-confirm-ok .icon-svg :deep(svg) { fill:#fff; }
</style>
