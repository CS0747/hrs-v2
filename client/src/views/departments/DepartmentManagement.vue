<script setup>
import { ref, computed, onMounted } from 'vue'
import { useEmployeeStore } from '@/stores/employees'
import { usePermissions } from '@/composables/usePermissions'
import { API_ENDPOINTS } from '@/config/api'


const API      = API_ENDPOINTS.DEPARTMENTS
const empStore = useEmployeeStore()
const { hasPermission, loadPermissions } = usePermissions()

// ── State ────────────────────────────────────────────────────────────────────
const departments  = ref([])
const loading      = ref(false)
const search       = ref('')
const filterActive = ref('1') // '1' active, '0' inactive, '' all

// ── Modal state ──────────────────────────────────────────────────────────────
const showFormModal   = ref(false)
const showDeleteModal = ref(false)
const isEdit          = ref(false)
const saving          = ref(false)
const deleteTarget    = ref(null)

const form = ref({ id: null, name: '', code: '', description: '', active: 1 })
const formError = ref('')

// ── SVG icons ────────────────────────────────────────────────────────────────
const icons = {
  add:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  dept:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
  warn:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
}

// ── Fetch ────────────────────────────────────────────────────────────────────
async function fetchDepartments() {
  loading.value = true
  try {
    const res  = await fetch(`${API}?all=1`)
    const data = await res.json()
    departments.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Failed to fetch departments:', e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadPermissions()
  fetchDepartments()
})

// ── Filtered list ────────────────────────────────────────────────────────────
const filtered = computed(() => {
  return departments.value.filter(d => {
    const q = search.value.toLowerCase()
    const matchSearch = !q ||
      d.name.toLowerCase().includes(q) ||
      (d.code  ?? '').toLowerCase().includes(q) ||
      (d.description ?? '').toLowerCase().includes(q)
    const matchActive = filterActive.value === ''
      ? true
      : String(d.active) === filterActive.value
    return matchSearch && matchActive
  })
})

// ── Open Add modal ───────────────────────────────────────────────────────────
function openAdd() {
  isEdit.value    = false
  formError.value = ''
  form.value      = { id: null, name: '', code: '', description: '', active: 1 }
  showFormModal.value = true
}

// ── Open Edit modal ──────────────────────────────────────────────────────────
function openEdit(dept) {
  isEdit.value    = true
  formError.value = ''
  form.value      = { ...dept }
  showFormModal.value = true
}

// ── Save (add or update) ─────────────────────────────────────────────────────
async function save() {
  formError.value = ''
  if (!form.value.name.trim()) {
    formError.value = 'Department name is required.'
    return
  }
  saving.value = true
  try {
    const url    = isEdit.value ? `${API}?id=${form.value.id}` : API
    const method = isEdit.value ? 'PUT' : 'POST'
    const res    = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name:        form.value.name.trim(),
        code:        form.value.code.trim(),
        description: form.value.description.trim(),
        active:      form.value.active,
      }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Save failed')
    showFormModal.value = false
    await fetchDepartments()
    empStore.fetchDepartments()
  } catch (e) {
    formError.value = e.message
  } finally {
    saving.value = false
  }
}

// ── Delete (soft) ────────────────────────────────────────────────────────────
function promptDelete(dept) {
  deleteTarget.value  = dept
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  deleteTarget.value  = null
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await fetch(`${API}?id=${deleteTarget.value.id}`, { method: 'DELETE' })
    await fetchDepartments()
    empStore.fetchDepartments()
  } catch (e) {
    console.error('Delete failed:', e)
  } finally {
    cancelDelete()
  }
}

// ── Employee count per department ─────────────────────────────────────────────
const empCountByDept = computed(() => {
  const counts = {}
  for (const emp of empStore.employees) {
    if (emp.department) {
      counts[emp.department] = (counts[emp.department] || 0) + 1
    }
  }
  return counts
})
async function toggleActive(dept) {
  try {
    await fetch(`${API}?id=${dept.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...dept, active: dept.active ? 0 : 1 }),
    })
    await fetchDepartments()
    empStore.fetchDepartments()
  } catch (e) {
    console.error('Toggle failed:', e)
  }
}
</script>

<template>
  <div class="page">

    <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="icons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search departments..." />
        </div>
        <select v-model="filterActive" class="filter-select">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
          <option value="">All</option>
        </select>
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} department(s)</span>
        <button v-if="hasPermission('Departments', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="icons.add"></span> Add Department
        </button>
      </div>
    </div>

    <!-- ── Table ──────────────────────────────────────────────────────────── -->
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Department Name</th>
            <th>Code</th>
            <th>Employees</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="empty-row">Loading...</td>
          </tr>
          <tr v-else-if="filtered.length === 0">
            <td colspan="7" class="empty-row">No departments found.</td>
          </tr>
          <tr v-for="(dept, i) in filtered" :key="dept.id">
            <td class="num-col">{{ i + 1 }}</td>
            <td>
              <div class="dept-name-cell">
                <span class="dept-icon" v-html="icons.dept"></span>
                <strong>{{ dept.name }}</strong>
              </div>
            </td>
            <td><span class="code-badge">{{ dept.code || '—' }}</span></td>
            <td>
              <span class="emp-count-badge">
                {{ empCountByDept[dept.name] || 0 }}
              </span>
            </td>
            <td class="desc-col">{{ dept.description || '—' }}</td>
            <td>
              <button
                class="status-toggle"
                :class="dept.active ? 'active' : 'inactive'"
                @click="toggleActive(dept)"
                :title="dept.active ? 'Click to deactivate' : 'Click to activate'"
              >
                {{ dept.active ? 'Active' : 'Inactive' }}
              </button>
            </td>
            <td>
              <div class="action-btns">
                <button v-if="hasPermission('Departments', 'Edit')" class="btn-icon" title="Edit" @click="openEdit(dept)">
                  <span class="icon-svg" v-html="icons.edit"></span>
                </button>
                <button v-if="hasPermission('Departments', 'Delete')" class="btn-icon danger" title="Deactivate" @click="promptDelete(dept)">
                  <span class="icon-svg" v-html="icons.delete"></span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal">
          <h3 class="modal-title">
            <span class="icon-svg" v-html="isEdit ? icons.edit : icons.add"></span>
            {{ isEdit ? 'Edit Department' : 'Add Department' }}
          </h3>

          <div class="form-group">
            <label>Department Name <span class="req">*</span></label>
            <input v-model="form.name" placeholder="e.g. Nursing" maxlength="100" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Code</label>
              <input v-model="form.code" placeholder="e.g. NUR" maxlength="20" style="text-transform:uppercase" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.active">
                <option :value="1">Active</option>
                <option :value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <input v-model="form.description" placeholder="Brief description" maxlength="255" />
          </div>

          <p v-if="formError" class="form-error">{{ formError }}</p>

          <div class="modal-actions">
            <button class="btn btn-cancel" @click="showFormModal = false" :disabled="saving">Cancel</button>
            <button class="btn btn-confirm" @click="save" :disabled="saving">
              {{ saving ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Department') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Delete Confirmation Modal ─────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDelete">
        <div class="modal">
          <div class="modal-icon-wrap warn">
            <span class="icon-svg lg" v-html="icons.warn"></span>
          </div>
          <h3 class="modal-title">Deactivate Department</h3>
          <p class="modal-message">Are you sure you want to deactivate this department?</p>
          <div class="dept-card">
            <span class="dept-icon" v-html="icons.dept"></span>
            <div>
              <strong>{{ deleteTarget?.name }}</strong>
              <span>{{ deleteTarget?.code || 'No code' }}</span>
            </div>
          </div>
          <p class="modal-note">The department will be set to inactive. No data will be lost.</p>
          <div class="modal-actions">
            <button class="btn btn-cancel" @click="cancelDelete">Cancel</button>
            <button class="btn btn-delete" @click="confirmDelete">Yes, Deactivate</button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.icon-svg.lg { width:28px; height:28px; }
.icon-svg.lg :deep(svg) { width:28px; height:28px; fill:#e67e22; }

.page { padding: 24px; }

/* ── Toolbar ── */
.toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
  position: sticky; top: 0; z-index: 10;
  background: #f0f4f8; padding: 10px 0;
}
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.search-wrap { position: relative; display: inline-flex; align-items: center; }
.search-icon { position: absolute; left: 10px; color: #aaa; pointer-events: none; }
.search-input {
  padding: 8px 14px 8px 34px; border: 1px solid #ddd;
  border-radius: 8px; font-size: 13px; width: 260px; outline: none;
}
.search-input:focus { border-color: #1a6b3c; }
.filter-select {
  padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
  font-size: 13px; outline: none; background: #fff; cursor: pointer;
}
.record-count { font-size: 13px; color: #888; }

/* ── Buttons ── */
.btn {
  padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer;
  font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;
  transition: background 0.2s;
}
.btn-primary { background: #1a6b3c; color: #fff; }
.btn-primary:hover { background: #27ae60; }

/* ── Table ── */
.table-wrapper {
  background: #fff; border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07); overflow-x: auto;
}
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table th { padding: 12px 14px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table td { padding: 11px 14px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.num-col { color: #aaa; font-size: 12px; width: 40px; }
.desc-col { color: #666; font-size: 12px; max-width: 260px; }

.dept-name-cell { display: flex; align-items: center; gap: 8px; }
.dept-icon { color: #1a6b3c; flex-shrink: 0; }

.code-badge {
  background: #e8f5ee; color: #1a6b3c;
  padding: 2px 10px; border-radius: 10px;
  font-size: 11px; font-weight: 700; font-family: monospace;
}

.emp-count-badge {
  display: inline-flex; align-items: center; justify-content: center;
  background: #ebf5fb; color: #2980b9;
  padding: 3px 12px; border-radius: 12px;
  font-size: 12px; font-weight: 700; min-width: 32px;
}

.status-toggle {
  padding: 3px 12px; border-radius: 12px; border: none;
  font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.status-toggle.active   { background: #eafaf1; color: #27ae60; }
.status-toggle.inactive { background: #f4f4f4; color: #999; }
.status-toggle:hover    { opacity: 0.75; }

.action-btns { display: flex; gap: 6px; }
.btn-icon {
  background: none; border: none; cursor: pointer; padding: 4px;
  border-radius: 4px; transition: background 0.2s;
  display: inline-flex; align-items: center; color: #555;
}
.btn-icon:hover        { background: #f0f4f8; color: #1a6b3c; }
.btn-icon.danger:hover { background: #fdecea; color: #e74c3c; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }

/* ── Modals ── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45); backdrop-filter: blur(2px);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: #fff; border-radius: 16px; padding: 28px 24px 22px;
  width: 100%; max-width: 440px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  display: flex; flex-direction: column; gap: 14px;
}
.modal-title {
  margin: 0; font-size: 17px; font-weight: 700; color: #1a1a2e;
  display: flex; align-items: center; gap: 8px;
}
.modal-message { margin: 0; font-size: 14px; color: #555; text-align: center; }
.modal-note    { margin: 0; font-size: 12px; color: #1a6b3c; font-weight: 600; text-align: center; }
.modal-icon-wrap {
  width: 56px; height: 56px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  align-self: center;
}
.modal-icon-wrap.warn { background: #fef3e2; }

.dept-card {
  display: flex; align-items: center; gap: 12px;
  background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 10px; padding: 12px 16px;
}
.dept-card strong { display: block; font-size: 14px; color: #1a1a2e; }
.dept-card span   { font-size: 12px; color: #888; }

/* Form inside modal */
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select {
  padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;
  font-size: 13px; outline: none; background: #fff;
}
.form-group input:focus, .form-group select:focus { border-color: #1a6b3c; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.req { color: #c0392b; }
.form-error { margin: 0; font-size: 12px; color: #e74c3c; font-weight: 600; }

.modal-actions { display: flex; gap: 10px; margin-top: 4px; }
.btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #f0f4f8; color: #555; border: 1px solid #ddd;
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-cancel:hover:not(:disabled) { background: #e0e8f0; }
.btn-cancel:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-confirm {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #1a6b3c; color: #fff; border: none;
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-confirm:hover:not(:disabled) { background: #27ae60; }
.btn-confirm:disabled { background: #a0c4b0; cursor: not-allowed; }
.btn-delete {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #e74c3c; color: #fff; border: none;
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-delete:hover { background: #c0392b; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform: scale(0.95); opacity: 0; }
</style>
