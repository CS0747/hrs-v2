<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

onMounted(() => {
  // Allow Super Admin, Admin, and DIOS to access Account Management
  const allowed = ['Super Admin', 'Admin', 'DIOS']
  if (!allowed.includes(auth.userRole)) router.replace('/')
})

// ── Icons ────────────────────────────────────────────────────────────────────
const icons = {
  add:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  warn:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
  user:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>`,
  lock:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>`,
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
}

const roles = ['Admin', 'Super Admin', 'IT', 'Section Admin', 'DIOS']
const search = ref('')

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return auth.users.filter(u =>
    !q || u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q) || u.role.toLowerCase().includes(q)
  )
})

// ── Form modal ───────────────────────────────────────────────────────────────
const showForm   = ref(false)
const editId     = ref(null)
const saving     = ref(false)
const formError  = ref('')
const showBio    = ref(false)
const showBio2   = ref(false)

const form = ref({ name: '', username: '', role: 'Admin', department: 'Human Resources', password: '', confirmPassword: '' })

function openAdd() {
  editId.value = null
  form.value   = { name: '', username: '', role: 'Admin', department: 'Human Resources', password: '', confirmPassword: '' }
  formError.value = ''
  showForm.value  = true
}

function openEdit(u) {
  editId.value = u.id
  form.value   = { name: u.name, username: u.username, role: u.role, department: u.department || 'Human Resources', password: '', confirmPassword: '' }
  formError.value = ''
  showForm.value  = true
}

function save() {
  formError.value = ''
  if (!form.value.name.trim() || !form.value.username.trim()) { formError.value = 'Name and username are required.'; return }
  if (!editId.value && !form.value.password) { formError.value = 'Biometrics number is required.'; return }
  if (form.value.password && form.value.password.length < 6) { formError.value = 'Biometrics number must be at least 6 characters.'; return }
  if (form.value.password && form.value.password !== form.value.confirmPassword) { formError.value = 'Biometrics numbers do not match.'; return }

  saving.value = true
  if (editId.value) {
    const data = { name: form.value.name, username: form.value.username, role: form.value.role, department: form.value.department }
    if (form.value.password) data.password = form.value.password
    auth.updateUser(editId.value, data)
  } else {
    auth.signup({ name: form.value.name, username: form.value.username, password: form.value.password, confirmPassword: form.value.confirmPassword, role: form.value.role, department: form.value.department })
  }
  saving.value    = false
  showForm.value  = false
}

// ── Delete modal ─────────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

function promptDelete(u) {
  if (u.id === auth.currentUser?.id) { alert("You can't delete your own account."); return }
  deleteTarget.value    = u
  showDeleteModal.value = true
}

function confirmDelete() {
  if (deleteTarget.value) auth.deleteUser(deleteTarget.value.id)
  showDeleteModal.value = false
  deleteTarget.value    = null
}

function roleColor(role) {
  return { 'Super Admin': '#1a6b3c', 'Admin': '#1a3a5c', 'IT': '#8e44ad', 'Section Admin': '#e67e22' }[role] || '#666'
}
function roleBg(role) {
  return { 'Super Admin': '#e8f5ee', 'Admin': '#e8f0fe', 'IT': '#f5eef8', 'Section Admin': '#fef3e2' }[role] || '#f4f4f4'
}
function initials(name) {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<template>
  <div class="page">
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg" v-html="icons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search accounts..." />
        </div>
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} account(s)</span>
        <button class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="icons.add"></span> Add Account
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Username</th>
            <th>Role</th>
            <th>Department</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0"><td colspan="5" class="empty-row">No accounts found.</td></tr>
          <tr v-for="u in filtered" :key="u.id" :class="{ 'current-row': u.id === auth.currentUser?.id }">
            <td>
              <div class="user-cell">
                <div class="user-avatar" :style="{ background: roleColor(u.role) }">{{ initials(u.name) }}</div>
                <div>
                  <strong>{{ u.name }}</strong>
                  <div class="user-sub">{{ u.id === auth.currentUser?.id ? '(You)' : '' }}</div>
                </div>
              </div>
            </td>
            <td><span class="mono">{{ u.username }}</span></td>
            <td>
              <span class="role-badge" :style="{ background: roleBg(u.role), color: roleColor(u.role) }">
                {{ u.role }}
              </span>
            </td>
            <td>{{ u.department || '—' }}</td>
            <td>
              <div class="action-btns">
                <button class="btn-icon" title="Edit" @click="openEdit(u)">
                  <span class="icon-svg" v-html="icons.edit"></span>
                </button>
                <button class="btn-icon danger" title="Delete" @click="promptDelete(u)" :disabled="u.id === auth.currentUser?.id">
                  <span class="icon-svg" v-html="icons.delete"></span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Modal -->
    <Transition name="modal">
      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal">
          <h3 class="modal-title">
            <span class="icon-svg" v-html="editId ? icons.edit : icons.add"></span>
            {{ editId ? 'Edit Account' : 'Add Account' }}
          </h3>

          <div class="form-group">
            <label>Full Name <span class="req">*</span></label>
            <input v-model="form.name" placeholder="Last Name, First Name" maxlength="80" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Username <span class="req">*</span></label>
              <input v-model="form.username" placeholder="e.g. jdelacruz" maxlength="30" />
            </div>
            <div class="form-group">
              <label>Role</label>
              <select v-model="form.role">
                <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Department</label>
            <input v-model="form.department" placeholder="e.g. Human Resources" maxlength="100" />
          </div>
          <div class="form-group">
            <label>Biometrics Number {{ editId ? '(leave blank to keep current)' : '*' }}</label>
            <div class="input-wrap">
              <span class="field-icon" v-html="icons.lock"></span>
              <input :type="showBio ? 'text' : 'password'" v-model="form.password" placeholder="Min. 6 characters" maxlength="50" />
              <button type="button" class="toggle-vis" @click="showBio = !showBio">{{ showBio ? '🙈' : '👁' }}</button>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm Biometrics Number</label>
            <div class="input-wrap">
              <span class="field-icon" v-html="icons.lock"></span>
              <input :type="showBio2 ? 'text' : 'password'" v-model="form.confirmPassword" placeholder="Re-enter biometrics number" maxlength="50" />
              <button type="button" class="toggle-vis" @click="showBio2 = !showBio2">{{ showBio2 ? '🙈' : '👁' }}</button>
            </div>
          </div>

          <p v-if="formError" class="form-error">{{ formError }}</p>
          <p v-if="auth.signupError" class="form-error">{{ auth.signupError }}</p>

          <div class="modal-actions">
            <button class="btn btn-cancel" @click="showForm = false">Cancel</button>
            <button class="btn btn-confirm" @click="save" :disabled="saving">
              {{ saving ? 'Saving...' : (editId ? 'Save Changes' : 'Add Account') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Modal -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal del-modal">
          <div class="del-icon-wrap"><span class="icon-svg del-icon" v-html="icons.warn"></span></div>
          <h3 class="modal-title">Delete Account</h3>
          <p class="modal-msg">Are you sure you want to delete this account?</p>
          <div class="del-card">
            <div class="user-avatar sm" :style="{ background: roleColor(deleteTarget?.role) }">{{ initials(deleteTarget?.name || '') }}</div>
            <div>
              <strong>{{ deleteTarget?.name }}</strong>
              <span>{{ deleteTarget?.role }} · {{ deleteTarget?.username }}</span>
            </div>
          </div>
          <p class="del-warn">This action cannot be undone.</p>
          <div class="modal-actions">
            <button class="btn btn-cancel" @click="showDeleteModal = false">Cancel</button>
            <button class="btn btn-delete" @click="confirmDelete">Yes, Delete</button>
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
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-wrap .icon-svg { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:260px; outline:none; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn-primary { background:#1a6b3c; color:#fff; }
.btn-primary:hover { background:#27ae60; }
.table-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow-x:auto; }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table th { padding:12px 14px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:11px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.current-row { background:#f0f9f4 !important; }
.user-cell { display:flex; align-items:center; gap:10px; }
.user-avatar { width:34px; height:34px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.user-avatar.sm { width:40px; height:40px; font-size:13px; }
.user-sub { font-size:11px; color:#1a6b3c; font-weight:600; }
.mono { font-family:monospace; font-size:12px; color:#555; }
.role-badge { padding:3px 10px; border-radius:10px; font-size:11px; font-weight:700; }
.action-btns { display:flex; gap:6px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; border-radius:4px; display:inline-flex; align-items:center; color:#555; }
.btn-icon:hover { background:#f0f4f8; color:#1a6b3c; }
.btn-icon.danger:hover { background:#fdecea; color:#e74c3c; }
.btn-icon:disabled { opacity:0.3; cursor:not-allowed; }
.empty-row { text-align:center; color:#aaa; padding:40px; }
/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(2px); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal { background:#fff; border-radius:16px; padding:28px 24px 22px; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:12px; }
.modal-title { margin:0; font-size:17px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; gap:8px; }
.modal-msg { margin:0; font-size:14px; color:#555; text-align:center; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input, .form-group select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.form-group input:focus, .form-group select:focus { border-color:#1a6b3c; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.input-wrap { display:flex; align-items:center; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.input-wrap:focus-within { border-color:#1a6b3c; }
.field-icon { padding:0 8px; color:#aaa; display:flex; align-items:center; }
.field-icon :deep(svg) { width:14px; height:14px; fill:#aaa; }
.input-wrap input { flex:1; padding:8px 8px; border:none; outline:none; font-size:13px; }
.toggle-vis { background:none; border:none; padding:0 8px; cursor:pointer; font-size:14px; }
.req { color:#c0392b; }
.form-error { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
.modal-actions { display:flex; gap:10px; margin-top:4px; }
.btn-cancel { flex:1; padding:10px; border-radius:8px; background:#f0f4f8; color:#555; border:1px solid #ddd; font-size:13px; font-weight:600; cursor:pointer; }
.btn-cancel:hover { background:#e0e8f0; }
.btn-confirm { flex:1; padding:10px; border-radius:8px; background:#1a6b3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; }
.btn-confirm:hover:not(:disabled) { background:#27ae60; }
.btn-confirm:disabled { background:#a0c4b0; cursor:not-allowed; }
.btn-delete { flex:1; padding:10px; border-radius:8px; background:#e74c3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; }
.btn-delete:hover { background:#c0392b; }
/* Delete modal */
.del-modal { align-items:center; text-align:center; }
.del-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fef3e2; display:flex; align-items:center; justify-content:center; }
.del-icon { width:28px; height:28px; color:#e67e22; }
.del-icon :deep(svg) { width:28px; height:28px; fill:#e67e22; }
.del-card { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; width:100%; text-align:left; }
.del-card strong { display:block; font-size:14px; color:#1a1a2e; }
.del-card span { font-size:12px; color:#888; }
.del-warn { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
/* Transition */
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }
</style>
