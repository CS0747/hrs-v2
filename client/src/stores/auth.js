import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const STORE_VERSION = 'v5'
  if (localStorage.getItem('hris_users_version') !== STORE_VERSION) {
    localStorage.removeItem('hris_users')
    localStorage.removeItem('hris_profile_requests')
    localStorage.setItem('hris_users_version', STORE_VERSION)
  }

  const storedUsers = JSON.parse(localStorage.getItem('hris_users') || 'null')
  const users = ref(storedUsers || [
    { id: 1, username: 'superadmin', password: 'superadmin123', name: 'Super Admin', role: 'Super Admin', department: 'Human Resources' },
    { id: 2, username: 'admin',      password: 'admin123',      name: 'HR Admin',    role: 'Admin',       department: 'Human Resources' },
    { id: 3, username: 'dios123',    password: '12345678',      name: 'DIOS User',   role: 'DIOS',        department: 'DIOS Office' },
  ])
  if (!storedUsers) localStorage.setItem('hris_users', JSON.stringify(users.value))

  // ── Profile change requests (pending approval) ──────────────────────────
  const profileRequests = ref(JSON.parse(localStorage.getItem('hris_profile_requests') || '[]'))

  function saveRequests() {
    localStorage.setItem('hris_profile_requests', JSON.stringify(profileRequests.value))
  }

  // Called by Super Admin or Admin when they want to update their profile
  function requestProfileChange(data) {
    const req = {
      id:          Date.now(),
      userId:      currentUser.value?.id,
      userName:    currentUser.value?.name,
      userRole:    currentUser.value?.role,
      requestedAt: nowTimestamp(),
      status:      'pending', // pending | approved | rejected
      changes:     data,      // { name, username, password? }
    }
    profileRequests.value.unshift(req)
    saveRequests()
    addLog('Profile Change Requested', 'Auth', `${currentUser.value?.name} requested a profile update. Awaiting approval.`)
    return req.id
  }

  // Called by DIOS (for any request) or Super Admin (for Admin requests only)
  function approveProfileRequest(reqId) {
    const req = profileRequests.value.find(r => r.id === reqId)
    if (!req || req.status !== 'pending') return false
    req.status = 'approved'
    saveRequests()
    // Apply the changes
    const idx = users.value.findIndex(u => u.id === req.userId)
    if (idx !== -1) {
      users.value[idx] = { ...users.value[idx], ...req.changes }
      localStorage.setItem('hris_users', JSON.stringify(users.value))
      // If the affected user is currently logged in, update their session
      if (currentUser.value?.id === req.userId) {
        const { password: _p, ...safeUser } = users.value[idx]
        currentUser.value = safeUser
        sessionStorage.setItem('hris_user', JSON.stringify(safeUser))
      }
    }
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

  // Pending requests visible to the current approver
  const pendingProfileRequests = computed(() => {
    if (userRole.value === 'DIOS') {
      // DIOS sees all pending requests
      return profileRequests.value.filter(r => r.status === 'pending')
    }
    if (userRole.value === 'Super Admin') {
      // Super Admin sees only Admin requests
      return profileRequests.value.filter(r => r.status === 'pending' && r.userRole === 'Admin')
    }
    return []
  })

  // My own pending request (for Super Admin / Admin to know their request is waiting)
  const myPendingRequest = computed(() =>
    profileRequests.value.find(r => r.userId === currentUser.value?.id && r.status === 'pending') || null
  )
  // ────────────────────────────────────────────────────────────────────────

  const currentUser = ref(JSON.parse(sessionStorage.getItem('hris_user') || 'null'))
  const loginError  = ref('')
  const signupError = ref('')

  const isLoggedIn = computed(() => !!currentUser.value)

  // Role helpers
  const userRole = computed(() => currentUser.value?.role ?? '')
  const isSectionAdmin = computed(() => userRole.value === 'Section Admin')
  const isIT           = computed(() => userRole.value === 'IT')
  const isFullAccess   = computed(() =>
    ['Super Admin', 'Admin', 'IT'].includes(userRole.value)
  )
  const isSuperAdmin   = computed(() => userRole.value === 'Super Admin')
  const isAdminOrAbove = computed(() => ['Super Admin', 'Admin'].includes(userRole.value))

  // Section Admin can edit only in Schedule Database
  function canEdit(section = '') {
    if (['Super Admin', 'Admin', 'IT'].includes(userRole.value)) return true
    if (userRole.value === 'Section Admin' && section === 'schedule') return true
    return false
  }

  const activityLog = ref(JSON.parse(sessionStorage.getItem('hris_log') || '[]'))

  function login(username, password) {
    loginError.value = ''
    const user = users.value.find(u => u.username === username && u.password === password)
    if (user) {
      const { password: _p, ...safeUser } = user
      currentUser.value = safeUser
      sessionStorage.setItem('hris_user', JSON.stringify(safeUser))
      addLog('Login', 'Auth', `${safeUser.name} logged in.`, { actionType: 'LOGIN' })
      return true
    }
    loginError.value = 'Invalid username or password.'
    return false
  }

  function signup(data) {
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
    if (users.value.find(u => u.username.toLowerCase() === data.username.toLowerCase())) {
      signupError.value = 'Username already exists. Please choose another.'
      return false
    }

    const newUser = {
      id: Date.now(),
      username:   data.username,
      password:   data.password,
      name:       data.name,
      role:       data.role || 'Admin',
      department: data.department || 'Human Resources',
    }
    users.value.push(newUser)
    localStorage.setItem('hris_users', JSON.stringify(users.value))
    addLog('Sign Up', 'Auth', `New user ${newUser.name} (${newUser.username}) registered.`)
    return true
  }

  function updateProfile(data) {
    const idx = users.value.findIndex(u => u.id === currentUser.value?.id)
    if (idx !== -1) {
      users.value[idx] = { ...users.value[idx], ...data }
      localStorage.setItem('hris_users', JSON.stringify(users.value))
      const { password: _p, ...safeUser } = users.value[idx]
      currentUser.value = safeUser
      sessionStorage.setItem('hris_user', JSON.stringify(safeUser))
      addLog('Profile Updated', 'Auth', `${safeUser.name} updated their profile.`)
    }
  }

  function logout() {
    if (currentUser.value) {
      addLog('Logout', 'Auth', `${currentUser.value.name} logged out.`, { actionType: 'LOGOUT' })
    }
    currentUser.value = null
    sessionStorage.removeItem('hris_user')
  }

  function deleteUser(id) {
    if (id === currentUser.value?.id) return false // can't delete self
    users.value = users.value.filter(u => u.id !== id)
    localStorage.setItem('hris_users', JSON.stringify(users.value))
    addLog('User Deleted', 'Auth', `User ID ${id} deleted.`)
    return true
  }

  function updateUser(id, data) {
    const idx = users.value.findIndex(u => u.id === id)
    if (idx === -1) return false
    users.value[idx] = { ...users.value[idx], ...data }
    localStorage.setItem('hris_users', JSON.stringify(users.value))
    addLog('User Updated', 'Auth', `User ${users.value[idx].name} updated.`)
    return true
  }

  const AUDIT_API = 'http://localhost/hrs-v2/server/api/audit_logs.php'

  // Consistent timestamp format: MM/DD/YYYY, hh:mm:ss AM/PM
  function nowTimestamp() {
    const d = new Date()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    const yyyy = d.getFullYear()
    const hh = String(d.getHours() % 12 || 12).padStart(2, '0')
    const min = String(d.getMinutes()).padStart(2, '0')
    const sec = String(d.getSeconds()).padStart(2, '0')
    const ampm = d.getHours() < 12 ? 'AM' : 'PM'
    return `${mm}/${dd}/${yyyy}, ${hh}:${min}:${sec} ${ampm}`
  }

  function addLog(action, module, details, extra = {}) {
    const entry = {
      id:        Date.now(),
      timestamp: nowTimestamp(),
      user:      currentUser.value?.name || 'System',
      action, module, details,
      status: 'OK',
      ...extra,
    }
    activityLog.value.unshift(entry)
    if (activityLog.value.length > 200) activityLog.value = activityLog.value.slice(0, 200)
    sessionStorage.setItem('hris_log', JSON.stringify(activityLog.value))

    // Persist to DB asynchronously — fire and forget
    fetch(AUDIT_API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id:        currentUser.value?.id   ?? null,
        user_name:      currentUser.value?.name ?? 'System',
        action,
        action_type:    extra.actionType  ?? 'OTHER',
        module,
        affected_table: extra.table       ?? null,
        record_id:      extra.recordId    ?? null,
        old_values:     extra.oldValues   ?? null,
        new_values:     extra.newValues   ?? null,
        details,
        status:         extra.status      ?? 'OK',
      }),
    }).catch(() => {}) // silent fail — local log still works
  }

  return {
    currentUser, isLoggedIn, loginError, signupError,
    activityLog, users, userRole, isSectionAdmin, isIT, isFullAccess, canEdit,
    isSuperAdmin, isAdminOrAbove, deleteUser, updateUser,
    login, signup, logout, updateProfile, addLog, nowTimestamp,
    profileRequests, pendingProfileRequests, myPendingRequest,
    requestProfileChange, approveProfileRequest, rejectProfileRequest,
  }
})
