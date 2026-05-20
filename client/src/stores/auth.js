import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API_ENDPOINTS } from '@/config/api'


const AUTH_API = API_ENDPOINTS.AUTH

const AUDIT_API = API_ENDPOINTS.AUDIT_LOGS

export const useAuthStore = defineStore('auth', () => {

  // ── Session ───────────────────────────────────────────────────────────────
  const currentUser = ref(JSON.parse(sessionStorage.getItem('hris_user') || 'null'))
  const loginError = ref('')
  const signupError = ref('')
  const loginLoading = ref(false)

  const isLoggedIn = computed(() => !!currentUser.value)
  const userRole = computed(() => currentUser.value?.role ?? '')
  const isSuperAdmin = computed(() => userRole.value === 'Super Admin')
  const isAdminOrAbove = computed(() => ['Super Admin', 'Admin', 'DIOS'].includes(userRole.value))
  const isSectionAdmin = computed(() => userRole.value === 'Section Admin')
  const isIT = computed(() => userRole.value === 'IT')
  const isFullAccess = computed(() => ['Super Admin', 'Admin', 'IT', 'DIOS'].includes(userRole.value))

  function canEdit(section = '') {
    if (['Super Admin', 'Admin', 'IT', 'DIOS'].includes(userRole.value)) return true
    if (userRole.value === 'Section Admin' && section === 'schedule') return true
    return false
  }

  // ── Users list (loaded from DB, cached in memory) ─────────────────────────
  const users = ref([])

  async function fetchUsers() {
    try {
      const res = await apiFetch(`${AUTH_API}?action=users`)
      const data = await res.json()
      if (Array.isArray(data.users)) {
        // Don't expose passwords — they're not returned by the API anyway
        users.value = data.users.map(u => ({ ...u, password: undefined }))
      }
    } catch { /* silent — keep existing list */ }
  }

  // ── Login (DB-backed) ─────────────────────────────────────────────────────
  async function login(username, password) {
    loginError.value = ''
    loginLoading.value = true
    try {
      const res = await fetch(`${AUTH_API}?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
      })
      const data = await res.json()
      if (!res.ok) {
        loginError.value = data.error || 'Invalid username or password.'
        return false
      }
      currentUser.value = data.user
      sessionStorage.setItem('hris_user', JSON.stringify(data.user))
      addLog('Login', 'Auth', `${data.user.name} logged in.`, { actionType: 'LOGIN' })
      return true
    } catch (e) {
      loginError.value = 'Connection error. Please try again.'
      return false
    } finally {
      loginLoading.value = false
    }
  }

  // ── Signup (DB-backed) ────────────────────────────────────────────────────
  async function signup(data) {
    signupError.value = ''
    if (!data.username || !data.password || !data.name) {
      signupError.value = 'Username, password, and full name are required.'
      return false
    }
    if (data.password.length < 6) {
      signupError.value = 'Password must be at least 6 characters.'
      return false
    }
    if (data.password !== data.confirmPassword) {
      signupError.value = 'Passwords do not match.'
      return false
    }
    try {
      const res = await apiFetch(`${AUTH_API}?action=signup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          username: data.username,
          password: data.password,
          name: data.name,
          role: data.role || 'Admin',
          department: data.department || 'Human Resources',
          position: data.position || '',
        }),
      })
      const json = await res.json()
      if (!res.ok) {
        signupError.value = json.error || 'Failed to create account.'
        return false
      }
      await fetchUsers()
      addLog('Sign Up', 'Auth', `New user ${data.name} (${data.username}) registered.`)
      return true
    } catch (e) {
      signupError.value = 'Connection error. Please try again.'
      return false
    }
  }

  // ── Update profile ────────────────────────────────────────────────────────
  async function updateProfile(data) {
    const id = currentUser.value?.id
    if (!id) return
    try {
      await apiFetch(`${AUTH_API}?action=update_profile&id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
      currentUser.value = { ...currentUser.value, ...data }
      sessionStorage.setItem('hris_user', JSON.stringify(currentUser.value))
      addLog('Profile Updated', 'Auth', `${currentUser.value.name} updated their profile.`)
    } catch { /* silent */ }
  }

  // ── Delete user ───────────────────────────────────────────────────────────
  async function deleteUser(id) {
    if (id === currentUser.value?.id) return false
    try {
      const res = await apiFetch(`${AUTH_API}?action=delete_user&id=${id}`, { method: 'DELETE' })
      if (!res.ok) return false
      users.value = users.value.filter(u => u.id !== id)
      addLog('User Deleted', 'Auth', `User ID ${id} deactivated.`)
      return true
    } catch { return false }
  }

  // ── Update user ───────────────────────────────────────────────────────────
  async function updateUser(id, data) {
    try {
      const res = await apiFetch(`${AUTH_API}?action=update_profile&id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
      if (!res.ok) return false

      // Update users list
      const idx = users.value.findIndex(u => u.id === id)
      if (idx !== -1) users.value[idx] = { ...users.value[idx], ...data }

      // If updating current user, update session storage
      if (currentUser.value && currentUser.value.id === id) {
        currentUser.value = { ...currentUser.value, ...data }
        sessionStorage.setItem('hris_user', JSON.stringify(currentUser.value))
      }

      addLog('User Updated', 'Auth', `User ${data.name ?? id} updated.`)
      return true
    } catch { return false }
  }

  // ── Logout ────────────────────────────────────────────────────────────────
  function logout() {
    if (currentUser.value) {
      addLog('Logout', 'Auth', `${currentUser.value.name} logged out.`, { actionType: 'LOGOUT' })
    }
    currentUser.value = null
    sessionStorage.removeItem('hris_user')
  }

  // ── Refresh current user data from database ───────────────────────────────
  async function refreshCurrentUser() {
    if (!currentUser.value?.id) return false
    try {
      const res = await apiFetch(`${AUTH_API}?action=users`)
      const data = await res.json()
      if (Array.isArray(data.users)) {
        const updatedUser = data.users.find(u => u.id === currentUser.value.id)
        if (updatedUser) {
          currentUser.value = { ...updatedUser, password: undefined }
          sessionStorage.setItem('hris_user', JSON.stringify(currentUser.value))
          return true
        }
      }
    } catch (e) {
      console.error('Failed to refresh user:', e)
    }
    return false
  }

  // ── Profile change requests (localStorage — lightweight approval flow) ────
  const profileRequests = ref(JSON.parse(localStorage.getItem('hris_profile_requests') || '[]'))

  function saveRequests() {
    localStorage.setItem('hris_profile_requests', JSON.stringify(profileRequests.value))
  }

  function requestProfileChange(data) {
    const req = {
      id: Date.now(),
      userId: currentUser.value?.id,
      userName: currentUser.value?.name,
      userRole: currentUser.value?.role,
      requestedAt: nowTimestamp(),
      status: 'pending',
      changes: data,
    }
    profileRequests.value.unshift(req)
    saveRequests()
    addLog('Profile Change Requested', 'Auth', `${currentUser.value?.name} requested a profile update.`)
    return req.id
  }

  function approveProfileRequest(reqId) {
    const req = profileRequests.value.find(r => r.id === reqId)
    if (!req || req.status !== 'pending') return false
    req.status = 'approved'
    saveRequests()
    updateUser(req.userId, req.changes)
    addLog('Profile Change Approved', 'Auth', `Profile update for ${req.userName} was approved.`)
    return true
  }

  function rejectProfileRequest(reqId) {
    const req = profileRequests.value.find(r => r.id === reqId)
    if (!req || req.status !== 'pending') return false
    req.status = 'rejected'
    saveRequests()
    addLog('Profile Change Rejected', 'Auth', `Profile update for ${req.userName} was rejected.`)
    return true
  }

  const pendingProfileRequests = computed(() => {
    if (userRole.value === 'DIOS') return profileRequests.value.filter(r => r.status === 'pending')
    if (userRole.value === 'Super Admin') return profileRequests.value.filter(r => r.status === 'pending' && r.userRole === 'Admin')
    return []
  })

  const myPendingRequest = computed(() =>
    profileRequests.value.find(r => r.userId === currentUser.value?.id && r.status === 'pending') || null
  )

  // ── Activity log + audit ──────────────────────────────────────────────────
  const activityLog = ref(JSON.parse(sessionStorage.getItem('hris_log') || '[]'))

  // ── API Request Wrapper with X-User-Id Header ─────────────────────────────
  function apiFetch(url, options = {}) {
    const headers = options.headers || {}

    // Add X-User-Id header if user is logged in
    if (currentUser.value?.id) {
      headers['X-User-Id'] = String(currentUser.value.id)
    }

    return fetch(url, {
      ...options,
      headers,
    })
  }

  function nowTimestamp() {
    const d = new Date()
    const pad = n => String(n).padStart(2, '0')
    const mm = pad(d.getMonth() + 1)
    const dd = pad(d.getDate())
    const yyyy = d.getFullYear()
    const hh = pad(d.getHours() % 12 || 12)
    const min = pad(d.getMinutes())
    const sec = pad(d.getSeconds())
    const ampm = d.getHours() < 12 ? 'AM' : 'PM'
    return `${mm}/${dd}/${yyyy}, ${hh}:${min}:${sec} ${ampm}`
  }

  function addLog(action, module, details, extra = {}) {
    const entry = {
      id: Date.now(),
      timestamp: nowTimestamp(),
      user: currentUser.value?.name || 'System',
      action, module, details,
      status: 'OK',
      ...extra,
    }
    activityLog.value.unshift(entry)
    if (activityLog.value.length > 200) activityLog.value = activityLog.value.slice(0, 200)
    sessionStorage.setItem('hris_log', JSON.stringify(activityLog.value))

    apiFetch(AUDIT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: currentUser.value?.id ?? null,
        user_name: currentUser.value?.name ?? 'System',
        action,
        action_type: extra.actionType ?? 'OTHER',
        module,
        affected_table: extra.table ?? null,
        record_id: extra.recordId ?? null,
        old_values: extra.oldValues ?? null,
        new_values: extra.newValues ?? null,
        details,
        status: extra.status ?? 'OK',
      }),
    }).catch(() => { })
  }

  // Load users on store init
  fetchUsers()

  return {
    currentUser, isLoggedIn, loginError, signupError, loginLoading,
    activityLog, users, userRole,
    isSectionAdmin, isIT, isFullAccess, canEdit, isSuperAdmin, isAdminOrAbove,
    login, signup, logout, refreshCurrentUser, updateProfile, updateUser, deleteUser,
    addLog, nowTimestamp, fetchUsers, apiFetch,
    profileRequests, pendingProfileRequests, myPendingRequest,
    requestProfileChange, approveProfileRequest, rejectProfileRequest,
  }
})
