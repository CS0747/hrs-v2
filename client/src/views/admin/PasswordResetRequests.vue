<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { API_ENDPOINTS } from '@/config/api'

const auth = useAuthStore()
const requests = ref([])
const loading = ref(false)
const search = ref('')
const filterStatus = ref('')

// Reset password modal
const showResetModal = ref(false)
const resetTarget = ref(null)
const newPassword = ref('')
const resetLoading = ref(false)

onMounted(() => {
  fetchRequests()
})

async function fetchRequests() {
  loading.value = true
  try {
    const response = await auth.apiFetch(`${API_ENDPOINTS.AUTH}?action=get_password_reset_requests`)
    const data = await response.json()
    if (response.ok) {
      requests.value = data.requests || []
    }
  } catch (err) {
    console.error('Failed to fetch requests:', err)
  } finally {
    loading.value = false
  }
}

const filtered = computed(() => {
  return requests.value.filter(r => {
    const matchSearch = !search.value || 
      r.username.toLowerCase().includes(search.value.toLowerCase()) ||
      r.user_full_name.toLowerCase().includes(search.value.toLowerCase())
    const matchStatus = !filterStatus.value || r.status === filterStatus.value
    return matchSearch && matchStatus
  })
})

function openResetModal(request) {
  resetTarget.value = request
  newPassword.value = ''
  showResetModal.value = true
}

async function resetPassword() {
  if (!newPassword.value || newPassword.value.length < 6) {
    alert('Password must be at least 6 characters')
    return
  }
  
  resetLoading.value = true
  try {
    const response = await auth.apiFetch(`${API_ENDPOINTS.AUTH}?action=process_password_reset`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        request_id: resetTarget.value.id,
        user_id: resetTarget.value.user_id,
        action: 'approve',
        new_password: newPassword.value
      })
    })
    
    if (response.ok) {
      showResetModal.value = false
      fetchRequests()
      alert('Password reset successfully')
    } else {
      const data = await response.json()
      alert(data.error || 'Failed to reset password')
    }
  } catch (err) {
    alert('Connection error: ' + err.message)
  } finally {
    resetLoading.value = false
  }
}

async function rejectRequest(request) {
  if (!confirm(`Reject password reset request from ${request.user_name}?`)) return
  
  try {
    const response = await auth.apiFetch(`${API_ENDPOINTS.AUTH}?action=process_password_reset`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        request_id: request.id,
        user_id: request.user_id,
        action: 'reject'
      })
    })
    
    if (response.ok) {
      fetchRequests()
    }
  } catch (err) {
    alert('Connection error: ' + err.message)
  }
}

function statusBadge(status) {
  const map = {
    pending: 'badge-orange',
    approved: 'badge-green',
    rejected: 'badge-red'
  }
  return map[status] || 'badge-gray'
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1>Password Reset Requests</h1>
      <p>Review and approve password reset requests from users</p>
    </div>

    <div class="toolbar">
      <div class="search-wrap">
        <input v-model="search" class="search-input" placeholder="Search by username or name..." />
      </div>
      <select v-model="filterStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <button class="btn btn-secondary" @click="fetchRequests">
        Refresh
      </button>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Requested At</th>
            <th>Status</th>
            <th>Processed At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="empty-row">Loading...</td>
          </tr>
          <tr v-else-if="!filtered.length">
            <td colspan="7" class="empty-row">No requests found</td>
          </tr>
          <tr v-else v-for="req in filtered" :key="req.id">
            <td>{{ req.id }}</td>
            <td><strong>{{ req.username }}</strong></td>
            <td>{{ req.user_name }}</td>
            <td>{{ new Date(req.requested_at).toLocaleString() }}</td>
            <td>
              <span class="badge" :class="statusBadge(req.status)">
                {{ req.status }}
              </span>
            </td>
            <td>{{ req.processed_at ? new Date(req.processed_at).toLocaleString() : '—' }}</td>
            <td>
              <div class="action-btns" v-if="req.status === 'pending'">
                <button class="btn-approve" @click="openResetModal(req)">
                  Approve & Reset
                </button>
                <button class="btn-reject" @click="rejectRequest(req)">
                  Reject
                </button>
              </div>
              <span v-else class="text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reset Password Modal -->
    <div v-if="showResetModal" class="modal-overlay" @click.self="showResetModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3>Reset Password</h3>
          <button class="close-btn" @click="showResetModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="user-info">
            <p><strong>Username:</strong> {{ resetTarget?.username }}</p>
            <p><strong>Full Name:</strong> {{ resetTarget?.user_name }}</p>
          </div>
          <div class="form-group">
            <label>New Password (min 6 characters)</label>
            <input 
              v-model="newPassword" 
              type="password" 
              class="form-input"
              placeholder="Enter new password"
              @keyup.enter="resetPassword"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showResetModal = false" :disabled="resetLoading">
            Cancel
          </button>
          <button class="btn btn-primary" @click="resetPassword" :disabled="resetLoading">
            {{ resetLoading ? 'Resetting...' : 'Reset Password' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page { padding: 24px; }
.page-header { margin-bottom: 24px; }
.page-header h1 { margin: 0 0 8px; color: #1a3a5c; }
.page-header p { margin: 0; color: #666; font-size: 14px; }

.toolbar { display: flex; gap: 12px; margin-bottom: 16px; align-items: center; }
.search-wrap { flex: 1; max-width: 400px; }
.search-input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; }
.filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; }

.btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; }
.btn-primary { background: #1a3a5c; color: #fff; }
.btn-secondary { background: #f0f4f8; color: #1a3a5c; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.table-wrapper { overflow-x: auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table th { padding: 12px; text-align: left; font-weight: 600; }
.data-table td { padding: 10px 12px; border-bottom: 1px solid #f0f4f8; }
.data-table tbody tr:hover { background: #f9fafb; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }

.badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-orange { background: #fff3e0; color: #e65100; }
.badge-green { background: #e8f5e9; color: #2e7d32; }
.badge-red { background: #ffebee; color: #c62828; }
.badge-gray { background: #f5f5f5; color: #666; }

.action-btns { display: flex; gap: 6px; }
.btn-approve { padding: 4px 12px; background: #4caf50; color: #fff; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; }
.btn-reject { padding: 4px 12px; background: #f44336; color: #fff; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; }
.text-muted { color: #999; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: #fff; border-radius: 12px; width: 500px; max-width: 95vw; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f4f8; }
.modal-header h3 { margin: 0; color: #1a3a5c; }
.close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #888; }
.modal-body { padding: 20px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #f0f4f8; }

.user-info { background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
.user-info p { margin: 4px 0; font-size: 13px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #555; }
.form-input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
</style>
