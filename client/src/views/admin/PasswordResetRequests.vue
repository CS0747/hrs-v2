<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

onMounted(() => {
  // Only DIOS can access this page
  if (auth.userRole !== 'DIOS') router.replace('/')
  fetchRequests()
})

const icons = {
  check: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  close: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  lock: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>`,
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
}

const requests = ref([])
const loading = ref(false)
const search = ref('')
const filterStatus = ref('')

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return requests.value.filter(r => {
    const matchSearch = !q || r.username.toLowerCase().includes(q) || r.user_name.toLowerCase().includes(q)
    const matchStatus = !filterStatus.value || r.status === filterStatus.value
    return matchSearch && matchStatus
  })
})

async function fetchRequests() {
  loading.value = true
  try {
    const res = await auth.apiFetch('http://localhost/hrs-v2/server/api/auth.php?action=get_password_reset_requests')
    const data = await res.json()
    if (Array.isArray(data.requests)) {
      requests.value = data.requests
    }
  } catch (e) {
    console.error('Failed to fetch password reset requests:', e)
  } finally {
    loading.value = false
  }
}

// Reset password modal
const showResetModal = ref(false)
const resetTarget = ref(null)
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPwd = ref(false)
const showConfirmPwd = ref(false)
const resetError = ref('')
const resetting = ref(false)

function openResetModal(request) {
  resetTarget.value = request
  newPassword.value = ''
  confirmPassword.value = ''
  showNewPwd.value = false
  showConfirmPwd.value = false
  resetError.value = ''
  showResetModal.value = true
}

async function confirmReset() {
  resetError.value = ''
  
  if (!newPassword.value) {
    resetError.value = 'New password is required.'
    return
  }
  
  if (newPassword.value.length < 6) {
    resetError.value = 'Password must be at least 6 characters.'
    return
  }
  
  if (newPassword.value !== confirmPassword.value) {
    resetError.value = 'Passwords do not match.'
    return
  }
  
  resetting.value = true
  try {
    const res = await auth.apiFetch('http://localhost/hrs-v2/server/api/auth.php?action=process_password_reset', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        request_id: resetTarget.value.id,
        user_id: resetTarget.value.user_id,
        new_password: newPassword.value,
        action: 'approve'
      })
    })
    
    const data = await res.json()
    
    if (res.ok) {
      showResetModal.value = false
      await fetchRequests()
      alert(`Password reset successful for ${resetTarget.value.user_name}`)
    } else {
      resetError.value = data.error || 'Failed to reset password.'
    }
  } catch (e) {
    resetError.value = 'Connection error. Please try again.'
  } finally {
    resetting.value = false
  }
}

async function rejectRequest(request) {
  if (!confirm(`Reject password reset request from ${request.user_name}?`)) return
  
  try {
    const res = await auth.apiFetch('http://localhost/hrs-v2/server/api/auth.php?action=process_password_reset', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        request_id: request.id,
        user_id: request.user_id,
        action: 'reject'
      })
    })
    
    if (res.ok) {
      await fetchRequests()
    } else {
      alert('Failed to reject request')
    }
  } catch (e) {
    alert('Error: ' + e.message)
  }
}

function statusColor(status) {
  return {
    pending: '#e67e22',
    approved: '#27ae60',
    rejected: '#e74c3c'
  }[status] || '#666'
}

function statusBg(status) {
  return {
    pending: '#fef3e2',
    approved: '#e8f5ee',
    rejected: '#fdecea'
  }[status] || '#f4f4f4'
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1>Password Reset Requests</h1>
      <p>Review and approve password reset requests from users</p>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg" v-html="icons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search by username or name..." />
        </div>
        <select v-model="filterStatus" class="filter-select">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} request(s)</span>
        <button class="btn btn-secondary" @click="fetchRequests" :disabled="loading">
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Username</th>
            <th>Requested At</th>
            <th>Status</th>
            <th>Processed By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="empty-row">Loading...</td></tr>
          <tr v-else-if="filtered.length === 0"><td colspan="6" class="empty-row">No password reset requests found.</td></tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><strong>{{ r.user_name }}</strong></td>
            <td><span class="mono">{{ r.username }}</span></td>
            <td>{{ formatDate(r.requested_at) }}</td>
            <td>
              <span class="status-badge" :style="{ background: statusBg(r.status), color: statusColor(r.status) }">
                {{ r.status.toUpperCase() }}
              </span>
            </td>
            <td>{{ r.processed_by || '—' }}</td>
            <td>
              <div class="action-btns" v-if="r.status === 'pending'">
                <button class="btn-icon success" title="Reset Password" @click="openResetModal(r)">
                  <span class="icon-svg" v-html="icons.check"></span>
                </button>
                <button class="btn-icon danger" title="Reject" @click="rejectRequest(r)">
                  <span class="icon-svg" v-html="icons.close"></span>
                </button>
              </div>
              <span v-else class="processed-text">{{ formatDate(r.processed_at) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reset Password Modal -->
    <Transition name="modal">
      <div v-if="showResetModal" class="modal-overlay" @click.self="showResetModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Reset Password</h3>
            <button class="close-btn" @click="showResetModal = false">✕</button>
          </div>

          <div class="modal-body">
            <div class="user-info">
              <div class="user-avatar">{{ resetTarget?.user_name?.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase() }}</div>
              <div>
                <strong>{{ resetTarget?.user_name }}</strong>
                <span class="user-sub">{{ resetTarget?.username }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>New Password <span class="req">*</span></label>
              <div class="input-wrap">
                <span class="field-icon" v-html="icons.lock"></span>
                <input 
                  :type="showNewPwd ? 'text' : 'password'" 
                  v-model="newPassword" 
                  placeholder="Min. 6 characters"
                  @keyup.enter="confirmReset"
                />
                <button type="button" class="toggle-vis" @click="showNewPwd = !showNewPwd">
                  {{ showNewPwd ? '🙈' : '👁' }}
                </button>
              </div>
            </div>

            <div class="form-group">
              <label>Confirm Password <span class="req">*</span></label>
              <div class="input-wrap">
                <span class="field-icon" v-html="icons.lock"></span>
                <input 
                  :type="showConfirmPwd ? 'text' : 'password'" 
                  v-model="confirmPassword" 
                  placeholder="Re-enter password"
                  @keyup.enter="confirmReset"
                />
                <button type="button" class="toggle-vis" @click="showConfirmPwd = !showConfirmPwd">
                  {{ showConfirmPwd ? '🙈' : '👁' }}
                </button>
              </div>
            </div>

            <p v-if="resetError" class="form-error">{{ resetError }}</p>
          </div>

          <div class="modal-footer">
            <button class="btn btn-cancel" @click="showResetModal = false" :disabled="resetting">Cancel</button>
            <button class="btn btn-confirm" @click="confirmReset" :disabled="resetting">
              {{ resetting ? 'Resetting...' : 'Reset Password' }}
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
.page-header { margin-bottom: 20px; }
.page-header h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; color: #1a3a5c; }
.page-header p { margin: 0; font-size: 14px; color: #888; }
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-wrap .icon-svg { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:260px; outline:none; }
.filter-select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; outline:none; background:#fff; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.btn-secondary:hover:not(:disabled) { background:#e0e8f0; }
.btn-secondary:disabled { opacity:0.6; cursor:not-allowed; }
.table-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow-x:auto; }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table th { padding:12px 14px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:11px 14px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.mono { font-family:monospace; font-size:12px; color:#555; }
.status-badge { padding:3px 10px; border-radius:10px; font-size:11px; font-weight:700; }
.action-btns { display:flex; gap:6px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; border-radius:4px; display:inline-flex; align-items:center; color:#555; }
.btn-icon:hover { background:#f0f4f8; }
.btn-icon.success:hover { background:#eafaf1; color:#27ae60; }
.btn-icon.danger:hover { background:#fdecea; color:#e74c3c; }
.processed-text { font-size:12px; color:#888; }
.empty-row { text-align:center; color:#aaa; padding:40px; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal { background:#fff; border-radius:16px; width:460px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid #f0f4f8; }
.modal-header h3 { margin:0; font-size:18px; font-weight:700; color:#1a3a5c; }
.close-btn { background:none; border:none; font-size:24px; color:#888; cursor:pointer; padding:0; width:28px; height:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; }
.close-btn:hover { background:#f0f4f8; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.user-info { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; }
.user-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.user-info strong { display:block; font-size:14px; color:#1a1a2e; }
.user-sub { font-size:12px; color:#888; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.input-wrap { display:flex; align-items:center; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.input-wrap:focus-within { border-color:#1a6b3c; }
.field-icon { padding:0 8px; color:#aaa; display:flex; align-items:center; }
.field-icon :deep(svg) { width:14px; height:14px; fill:#aaa; }
.input-wrap input { flex:1; padding:8px 8px; border:none; outline:none; font-size:13px; }
.toggle-vis { background:none; border:none; padding:0 8px; cursor:pointer; font-size:14px; }
.req { color:#c0392b; }
.form-error { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
.modal-footer { display:flex; gap:10px; padding:16px 24px; border-top:1px solid #f0f4f8; }
.btn-cancel { flex:1; padding:10px; border-radius:8px; background:#f0f4f8; color:#555; border:1px solid #ddd; font-size:13px; font-weight:600; cursor:pointer; }
.btn-cancel:hover:not(:disabled) { background:#e0e8f0; }
.btn-confirm { flex:1; padding:10px; border-radius:8px; background:#1a6b3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; }
.btn-confirm:hover:not(:disabled) { background:#27ae60; }
.btn-cancel:disabled, .btn-confirm:disabled { opacity:0.6; cursor:not-allowed; }

/* Transition */
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }
</style>
