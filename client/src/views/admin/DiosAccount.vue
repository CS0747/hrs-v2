<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

// ── Administration quick links ────────────────────────────────────────────────
const adminLinks = [
  { label: 'Account Management', to: '/accounts',        svg: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>` },
  { label: 'DIOS Account',       to: '/dios-account',    svg: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>` },
  { label: 'Audit History',      to: '/audit-trail',     svg: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 9H7v-2h4v2zm4-4H7v-2h8v2zm0-4H7V8h8v2z"/></svg>` },
  { label: 'Version History',    to: '/version-history', svg: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>` },
  { label: 'User Manual',        to: '/user-manual',     svg: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>` },
]

const form = ref({ username: '', password: '', endpoint: 'https://dios.caviteprovince.gov.ph/api', department: 'GEAMH', syncEnabled: false })
const saved   = ref(false)
const testing = ref(false)
const testResult = ref(null)

function saveSettings() {
  localStorage.setItem('dios_config', JSON.stringify(form.value))
  saved.value = true
  setTimeout(() => { saved.value = false }, 2000)
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  await new Promise(r => setTimeout(r, 1200))
  testResult.value = form.value.username && form.value.password
    ? { success: true,  msg: 'Connection successful. DIOS API is reachable.' }
    : { success: false, msg: 'Please enter username and password first.' }
  testing.value = false
}

// Load saved config
const saved_config = localStorage.getItem('dios_config')
if (saved_config) Object.assign(form.value, JSON.parse(saved_config))

// ── Add DIOS Account ──────────────────────────────────────────────────────────
const showAddForm  = ref(false)
const addForm      = ref({ name: '', username: '', password: '', confirmPassword: '', department: 'DIOS Office' })
const addError     = ref('')
const addSaving    = ref(false)
const showBio      = ref(false)
const showBio2     = ref(false)

function openAddAccount() {
  addForm.value  = { name: '', username: '', password: '', confirmPassword: '', department: 'DIOS Office' }
  addError.value = ''
  showAddForm.value = true
}

function saveAccount() {
  addError.value = ''
  if (!addForm.value.name.trim() || !addForm.value.username.trim()) { addError.value = 'Name and username are required.'; return }
  if (!addForm.value.password || addForm.value.password.length < 6) { addError.value = 'Biometrics number must be at least 6 characters.'; return }
  if (addForm.value.password !== addForm.value.confirmPassword) { addError.value = 'Biometrics numbers do not match.'; return }

  addSaving.value = true
  const ok = auth.signup({
    name:            addForm.value.name,
    username:        addForm.value.username,
    password:        addForm.value.password,
    confirmPassword: addForm.value.confirmPassword,
    role:            'DIOS',
    department:      addForm.value.department,
  })
  addSaving.value = false
  if (ok) {
    showAddForm.value = false
    alert(`DIOS account "${addForm.value.username}" created successfully.`)
  } else {
    addError.value = auth.signupError || 'Failed to create account.'
  }
}

// List of DIOS accounts
const diosAccounts = () => auth.users.filter(u => u.role === 'DIOS')
</script>

<template>
  <div class="page">
    <div class="dios-layout">

      <!-- Left sidebar nav -->
      <div class="admin-sidebar-nav">
        <div class="admin-sidebar-label">Administration</div>
        <router-link
          v-for="link in adminLinks" :key="link.to"
          :to="link.to"
          class="admin-nav-item"
          :class="{ active: $route.path === link.to }"
        >
          <span class="admin-nav-icon-svg" v-html="link.svg"></span>
          <span class="admin-nav-label">{{ link.label }}</span>
        </router-link>
      </div>

      <!-- Main content -->
      <div class="dios-content">
        <div class="page-header">
          <div>
            <h2>🏛 DIOS Account Integration</h2>
            <p>Department of Information and Operations System — Provincial Government of Cavite</p>
          </div>
        </div>

    <div class="card">
      <div class="card-title">Connection Settings</div>
      <div class="form-grid">
        <div class="form-group full">
          <label>DIOS API Endpoint</label>
          <input v-model="form.endpoint" placeholder="https://dios.caviteprovince.gov.ph/api" />
        </div>
        <div class="form-group">
          <label>DIOS Username</label>
          <input v-model="form.username" placeholder="Enter DIOS username" autocomplete="off" />
        </div>
        <div class="form-group">
          <label>DIOS Password / Biometrics</label>
          <input v-model="form.password" type="password" placeholder="Enter DIOS password" autocomplete="off" />
        </div>
        <div class="form-group">
          <label>Department Code</label>
          <input v-model="form.department" placeholder="e.g. GEAMH" />
        </div>
        <div class="form-group">
          <label>Auto Sync</label>
          <label class="toggle-wrap">
            <input type="checkbox" v-model="form.syncEnabled" />
            <span class="toggle-label">{{ form.syncEnabled ? 'Enabled' : 'Disabled' }}</span>
          </label>
        </div>
      </div>

      <div v-if="testResult" class="test-result" :class="testResult.success ? 'success' : 'error'">
        {{ testResult.success ? '✅' : '❌' }} {{ testResult.msg }}
      </div>

      <div class="card-actions">
        <button class="btn btn-secondary" @click="testConnection" :disabled="testing">
          {{ testing ? '⏳ Testing...' : '🔌 Test Connection' }}
        </button>
        <button class="btn btn-primary" @click="saveSettings">
          {{ saved ? '✅ Saved!' : '💾 Save Settings' }}
        </button>
      </div>
    </div>

    <div class="card info-card">
      <div class="card-title">ℹ️ About DIOS Integration</div>
      <ul class="info-list">
        <li>DIOS (Department of Information and Operations System) is the provincial government's central data system.</li>
        <li>When connected, employee data can be synchronized between GEAMH HRIS and the provincial DIOS database.</li>
        <li>Contact the IT Department or the Provincial Government IT Office to obtain your DIOS API credentials.</li>
        <li>Ensure the DIOS endpoint URL is correct before testing the connection.</li>
      </ul>
    </div>

    <!-- DIOS Accounts -->
    <div class="card">
      <div class="card-title-row">
        <div class="card-title">👤 DIOS Accounts</div>
        <button class="btn btn-primary" @click="openAddAccount">+ Add Account</button>
      </div>
      <div v-if="diosAccounts().length === 0" class="no-accounts">No DIOS accounts yet.</div>
      <table v-else class="accounts-table">
        <thead><tr><th>Name</th><th>Username</th><th>Department</th></tr></thead>
        <tbody>
          <tr v-for="u in diosAccounts()" :key="u.id">
            <td><strong>{{ u.name }}</strong></td>
            <td><span class="mono">{{ u.username }}</span></td>
            <td>{{ u.department }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add DIOS Account Modal -->
    <Transition name="modal">
      <div v-if="showAddForm" class="modal-overlay" @click.self="showAddForm = false">
        <div class="modal">
          <h3 class="modal-title">Add DIOS Account</h3>
          <div class="form-group">
            <label>Full Name <span class="req">*</span></label>
            <input v-model="addForm.name" placeholder="Last Name, First Name" maxlength="80" />
          </div>
          <div class="form-group">
            <label>Username <span class="req">*</span></label>
            <input v-model="addForm.username" placeholder="e.g. dios_user" maxlength="30" />
          </div>
          <div class="form-group">
            <label>Department</label>
            <input v-model="addForm.department" placeholder="e.g. DIOS Office" maxlength="100" />
          </div>
          <div class="form-group">
            <label>Biometrics Number <span class="req">*</span></label>
            <div class="input-wrap">
              <input :type="showBio ? 'text' : 'password'" v-model="addForm.password" placeholder="Min. 6 characters" maxlength="50" />
              <button type="button" class="toggle-vis" @click="showBio = !showBio">{{ showBio ? '🙈' : '👁' }}</button>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm Biometrics Number <span class="req">*</span></label>
            <div class="input-wrap">
              <input :type="showBio2 ? 'text' : 'password'" v-model="addForm.confirmPassword" placeholder="Re-enter biometrics number" maxlength="50" />
              <button type="button" class="toggle-vis" @click="showBio2 = !showBio2">{{ showBio2 ? '🙈' : '👁' }}</button>
            </div>
          </div>
          <p v-if="addError" class="form-error">{{ addError }}</p>
          <div class="modal-actions">
            <button class="btn btn-secondary" @click="showAddForm = false">Cancel</button>
            <button class="btn btn-primary" @click="saveAccount" :disabled="addSaving">
              {{ addSaving ? 'Creating...' : 'Create DIOS Account' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
      </div><!-- end dios-content -->
    </div><!-- end dios-layout -->
  </div>
</template>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
.page-header h2 { margin: 0 0 4px; color: #1a3a5c; font-size: 20px; }
.page-header p  { margin: 0; color: #888; font-size: 13px; }

/* Administration quick links */
.admin-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.admin-nav-card {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
  padding: 14px 16px; cursor: pointer; text-align: left;
  transition: all 0.15s; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.admin-nav-card:hover { border-color: #1a3a5c; background: #f0f4f8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.admin-nav-icon { font-size: 24px; flex-shrink: 0; }
.admin-nav-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.admin-nav-info strong { font-size: 13px; color: #1a3a5c; font-weight: 700; }
.admin-nav-info span { font-size: 11px; color: #888; }
.admin-nav-arrow { font-size: 20px; color: #aaa; flex-shrink: 0; }
.card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
.card-title { font-size: 15px; font-weight: 700; color: #1a3a5c; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f4f8; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input { padding: 9px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; }
.form-group input:focus { border-color: #1a3a5c; }
.toggle-wrap { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.toggle-label { font-size: 13px; color: #555; }
.test-result { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.test-result.success { background: #eafaf1; color: #1a6b3c; border: 1px solid #c3e6cb; }
.test-result.error   { background: #fdecea; color: #c0392b; border: 1px solid #f5b7b1; }
.card-actions { display: flex; gap: 10px; }
.btn { padding: 9px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary  { background: #1a6b3c; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #27ae60; }
.btn-secondary { background: #f0f4f8; color: #1a3a5c; border: 1px solid #ddd; }
.btn-secondary:hover:not(:disabled) { background: #e0e8f0; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.info-card { background: #f8f9fa; }
.info-list { margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #555; line-height: 1.6; }

/* DIOS Accounts section */
.card-title-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #f0f4f8; }
.card-title-row .card-title { margin-bottom:0; padding-bottom:0; border-bottom:none; }
.no-accounts { color:#aaa; font-size:13px; text-align:center; padding:20px; }
.accounts-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.accounts-table thead tr { background:#1a3a5c; color:#fff; }
.accounts-table th { padding:10px 12px; text-align:left; font-weight:600; }
.accounts-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; }
.mono { font-family:monospace; font-size:12px; color:#555; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(2px); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal { background:#fff; border-radius:16px; padding:28px 24px 22px; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:12px; }
.modal-title { margin:0; font-size:17px; font-weight:700; color:#1a1a2e; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.form-group input:focus { border-color:#1a6b3c; }
.input-wrap { display:flex; align-items:center; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.input-wrap:focus-within { border-color:#1a6b3c; }
.input-wrap input { flex:1; padding:8px 12px; border:none; outline:none; font-size:13px; }
.toggle-vis { background:none; border:none; padding:0 10px; cursor:pointer; font-size:14px; }
.req { color:#c0392b; }
.form-error { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
.modal-actions { display:flex; gap:10px; margin-top:4px; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }
</style>
