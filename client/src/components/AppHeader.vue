<script setup>
import { useRoute, useRouter } from "vue-router"
import { computed, ref, onMounted, onUnmounted } from "vue"
import { useAuthStore } from "@/stores/auth"
import { useLiveNotifications } from "@/composables/useLiveNotifications"
import AppModal from "@/components/AppModal.vue"

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const emit = defineEmits(["toggle-sidebar"])

// Real-time notifications
const {
  allNotifications,
  unreadNotifications,
  unreadCount,
  markAsRead,
  markAllAsRead,
  deleteNotification,
} = useLiveNotifications()

const showNotificationPanel = ref(false)
const notificationPanelRef = ref(null)

function toggleNotificationPanel() {
  showNotificationPanel.value = !showNotificationPanel.value
}

function handleNotificationClickOutside(e) {
  if (notificationPanelRef.value && !notificationPanelRef.value.contains(e.target)) {
    showNotificationPanel.value = false
  }
}

async function handleNotificationClick(notification) {
  // Mark as read
  if (notification.is_read === 0) {
    await markAsRead(notification.id)
  }
  
  // Navigate to link if available
  if (notification.link) {
    router.push(notification.link)
    showNotificationPanel.value = false
  }
}

async function handleMarkAllRead() {
  await markAllAsRead()
}

function formatTimeAgo(dateStr) {
  if (!dateStr) return 'Just now'
  
  try {
    const date = new Date(dateStr)
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
      return 'Just now'
    }
    
    const now = new Date()
    const seconds = Math.floor((now - date) / 1000)
    
    if (seconds < 60) return 'Just now'
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`
    return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
  } catch (error) {
    console.error('Error formatting date:', error)
    return 'Just now'
  }
}

function getNotifIcon(type) {
  const icons = {
    password_reset: '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>',
    leave_request: '<path d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/>',
    travel_order: '<path d="M21 5h-9.17C6.41 5 2 9.41 2 14.83V15h20V6c0-.55-.45-1-1-1m-8 6H5.01c1.34-2.38 3.89-4 6.82-4H13zm-8 8h16c.55 0 1-.45 1-1v-2H2c0 1.65 1.35 3 3 3"/>',
    employee_added: '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
    employee_updated: '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
    training_added: '<path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>',
    dtr_submitted: '<path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>',
    schedule_assigned: '<path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>',
    audit_log: '<path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 9H7v-2h4v2zm4-4H7v-2h8v2zm0-4H7V8h8v2z"/>',
  }
  return icons[type] || icons.audit_log
}

function getNotifIconStyle(type) {
  const styles = {
    password_reset: 'background: #ebf5fb; color: #2980b9;',
    leave_request: 'background: #fff8e1; color: #f59e0b;',
    travel_order: 'background: #fef3e2; color: #e67e22;',
    employee_added: 'background: #eafaf1; color: #10b981;',
    employee_updated: 'background: #e0f2fe; color: #0284c7;',
    training_added: 'background: #e8f5ee; color: #1a6b3c;',
    dtr_submitted: 'background: #fef3c7; color: #d97706;',
    schedule_assigned: 'background: #ede9fe; color: #7c3aed;',
    audit_log: 'background: #f3f4f6; color: #6b7280;',
  }
  return styles[type] || styles.audit_log
}

onMounted(() => {
  document.addEventListener("mousedown", handleNotificationClickOutside)
})
onUnmounted(() => {
  document.removeEventListener("mousedown", handleNotificationClickOutside)
})

const pageTitle = computed(() => {
  const map = {
    "/": "Dashboard",
    "/employees": "Employee Masterlist",
    "/employees/new": "Add Employee",
    "/employees/birthdays": "Birthday Celebrants",
    "/dtr": "DTR Transmittal",
    "/leave": "Leave Management",
    "/to": "Travel Order (T.O.)",
    "/verification": "Verification",
    "/tracking": "Tracking & Receiving",
    "/signatories": "Signatories",
    "/audit": "Transmittal Summary",
    "/dios-control": "DIOS System Control",
    "/schedule": "Schedule Database",
    "/ai-scanning": "AI Scanning Tools",
    "/accounts": "Account Management",
    "/trainings": "Trainings Management",
  }
  return map[route.path] || "HRIS"
})

const today = new Date().toLocaleDateString("en-PH", {
  weekday: "long", year: "numeric", month: "long", day: "numeric"
})

const currentTime = ref(new Date().toLocaleTimeString("en-PH", { hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: true }))
let clockInterval = null
onMounted(() => {
  clockInterval = setInterval(() => {
    currentTime.value = new Date().toLocaleTimeString("en-PH", { hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: true })
  }, 1000)
})
onUnmounted(() => clearInterval(clockInterval))

const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const showProfileModal = ref(false)

function toggleDropdown() { dropdownOpen.value = !dropdownOpen.value }

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    dropdownOpen.value = false
  }
}
onMounted(() => document.addEventListener("mousedown", handleClickOutside))
onUnmounted(() => document.removeEventListener("mousedown", handleClickOutside))

const showLogoutModal = ref(false)

function logout() {
  dropdownOpen.value = false
  showLogoutModal.value = true
}
function confirmLogout() {
  showLogoutModal.value = false
  auth.logout()
  router.push('/login')
}

function openProfile() {
  dropdownOpen.value = false
  profileForm.value = {
    name: auth.currentUser?.name || "",
    username: auth.currentUser?.username || "",
    role: auth.currentUser?.role || "",
  }
  profilePicPreview.value = auth.currentUser?.avatar || null
  profileError.value = ""
  profileSuccess.value = ""
  showProfileModal.value = false
  setTimeout(() => { showProfileModal.value = true }, 10)
}

const profileForm = ref({ name: "", username: "", role: "" })
const profilePicPreview = ref(null)
const profileError = ref("")
const profileSuccess = ref("")
const profileFileInput = ref(null)

function onPicChange(e) {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => { profilePicPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function saveProfile() {
  profileError.value = ""
  if (!profileForm.value.name.trim()) { profileError.value = "Name is required."; return }
  if (!profileForm.value.username.trim()) { profileError.value = "Username is required."; return }
  
  const updateData = {
    name:     profileForm.value.name,
    username: profileForm.value.username,
    avatar:   profilePicPreview.value,
  }
  if (auth.userRole === 'DIOS') updateData.role = profileForm.value.role

  auth.updateProfile(updateData)
  profileSuccess.value = "Profile updated successfully!"
  setTimeout(() => { showProfileModal.value = false }, 1200)
}

const initials = computed(() => {
  const name = auth.currentUser?.name || "HR"
  return name.split(" ").map(n => n[0]).join("").slice(0, 2).toUpperCase()
})
</script>

<template>
  <header class="app-header">
    <div class="header-left">
      <button class="logo-toggle" @click="emit('toggle-sidebar')" title="Toggle sidebar">
        <img src="/GEAMH LOGO.png" alt="GEAMH" class="header-logo" />
      </button>
      <div class="title-block">
        <h1 class="page-title">{{ pageTitle }}</h1>
        <span class="breadcrumb">General Emilio Aguinaldo Memorial Hospital</span>
      </div>
    </div>

    <div class="header-right">
      <span class="date-display">{{ today }}</span>
      <span class="time-display">{{ currentTime }}</span>
      
      <!-- Notification Bell -->
      <div class="notification-bell-wrapper" ref="notificationPanelRef">
        <button class="notification-bell" @click="toggleNotificationPanel" :class="{ active: showNotificationPanel }">
          <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
          </svg>
          <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>

        <transition name="dropdown">
          <div v-if="showNotificationPanel" class="notification-panel">
            <div class="notification-panel-header">
              <h3>Notifications</h3>
              <div class="header-actions">
                <span class="unread-count">{{ unreadCount }} unread</span>
                <button v-if="unreadCount > 0" class="mark-all-btn" @click="handleMarkAllRead" title="Mark all as read">
                  <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/>
                  </svg>
                </button>
              </div>
            </div>
            <div class="notification-panel-body">
              <div v-if="allNotifications.length === 0" class="no-notifications">
                <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32" style="color: #10b981;">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <p>No notifications</p>
                <span>You're all caught up!</span>
              </div>
              
              <div v-else class="notifications-list">
                <div
                  v-for="notif in allNotifications.slice(0, 10)"
                  :key="notif.id"
                  class="notification-item"
                  :class="{ unread: notif.is_read === 0 }"
                  @click="handleNotificationClick(notif)"
                >
                  <div class="notif-icon" :style="getNotifIconStyle(notif.type)">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" v-html="getNotifIcon(notif.type)"></svg>
                  </div>
                  <div class="notif-content">
                    <div class="notif-title">{{ notif.title }}</div>
                    <div class="notif-message">{{ notif.message }}</div>
                    <div class="notif-time">{{ formatTimeAgo(notif.created_at) }}</div>
                  </div>
                  <div v-if="notif.is_read === 0" class="unread-dot"></div>
                  <button class="notif-delete" @click.stop="deleteNotification(notif.id)" title="Delete">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
      
      <div class="profile-wrapper" ref="dropdownRef">
        <button class="profile-btn" @click="toggleDropdown" :class="{ active: dropdownOpen }">
          <div class="profile-avatar">
            <img v-if="auth.currentUser?.avatar" :src="auth.currentUser.avatar" class="avatar-img" />
            <span v-else>{{ initials }}</span>
          </div>
          <div class="profile-info">
            <span class="profile-name">{{ auth.currentUser?.name || "HR Admin" }}</span>
            <span class="profile-role">{{ auth.currentUser?.role || "" }}</span>
            <span v-if="auth.currentUser?.role === 'Section Admin' || auth.currentUser?.role === 'Admin'" class="profile-dept">{{ auth.currentUser?.department || "" }}</span>
          </div>
          <span class="chevron">{{ dropdownOpen ? "▲" : "▼" }}</span>
        </button>

        <transition name="dropdown">
          <div v-if="dropdownOpen" class="dropdown-menu">
            <div class="dropdown-header">
              <div class="dropdown-avatar">
                <img v-if="auth.currentUser?.avatar" :src="auth.currentUser.avatar" class="avatar-img-lg" />
                <span v-else>{{ initials }}</span>
              </div>
              <div>
                <div class="dropdown-name">{{ auth.currentUser?.name }}</div>
                <div class="dropdown-role">{{ auth.currentUser?.role }}</div>
                <div v-if="auth.currentUser?.role === 'Section Admin' || auth.currentUser?.role === 'Admin'" class="dropdown-dept">{{ auth.currentUser?.department }}</div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item" @click="openProfile">
              <span class="di-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></span>
              My Profile
            </button>

            <div class="dropdown-divider"></div>
            <button class="dropdown-item logout-item" @click="logout">
              <span class="di-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg></span>
              Log Out
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>

  <!-- Profile Modal -->
  <teleport to="body">
    <div v-if="showProfileModal" class="modal-overlay" @click.self="showProfileModal = false">
      <div class="profile-modal">
        <div class="modal-header">
          <h3>My Profile</h3>
          <button class="close-btn" @click="showProfileModal = false">&#x2715;</button>
        </div>
        <div class="modal-body">
          <!-- Avatar -->
          <div class="avatar-section">
            <div class="avatar-preview">
              <img v-if="profilePicPreview" :src="profilePicPreview" class="avatar-preview-img" />
              <span v-else class="avatar-preview-initials">{{ initials }}</span>
            </div>
            <button class="change-pic-btn" @click="profileFileInput.click()">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              Change Photo
            </button>
            <input ref="profileFileInput" type="file" accept="image/*" hidden @change="onPicChange" />
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Full Name</label>
              <input v-model="profileForm.name" placeholder="Full Name" />
            </div>
            <div class="form-group">
              <label>Username</label>
              <input v-model="profileForm.username" placeholder="Username" />
            </div>
            <div class="form-group">
              <label>Role</label>
              <!-- DIOS: editable dropdown showing DIOS only -->
              <select v-if="auth.userRole === 'DIOS'" v-model="profileForm.role">
                <option value="DIOS">DIOS</option>
              </select>
              <!-- Super Admin / Admin: read-only role display -->
              <input v-else :value="profileForm.role" readonly class="input-readonly" />
            </div>
          </div>

          <div v-if="profileError" class="form-error">{{ profileError }}</div>
          <div v-if="profileSuccess" class="form-success">{{ profileSuccess }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showProfileModal = false">Cancel</button>
          <button class="btn-save" @click="saveProfile">Save Changes</button>
        </div>
      </div>
    </div>
  </teleport>

  <!-- Logout Confirmation -->
  <AppModal
    v-if="showLogoutModal"
    type="warning"
    title="Log Out"
    message="Are you sure you want to log out?"
    confirmLabel="Yes, Log Out"
    @confirm="confirmLogout"
    @cancel="showLogoutModal = false"
  />
</template>

<style scoped>
.app-header {
  background:#fff; border-bottom:2px solid #b7dfc8; padding:10px 20px;
  display:flex; align-items:center; justify-content:space-between;
  box-shadow:0 2px 8px rgba(0,0,0,0.06); position:sticky; top:0; z-index:100;
}
.header-left { display:flex; align-items:center; gap:12px; }
.logo-toggle { background:none; border:none; padding:2px; cursor:pointer; border-radius:50%; transition:transform 0.2s; }
.logo-toggle:hover { transform:scale(1.08); }
.header-logo { width:42px; height:42px; border-radius:50%; object-fit:cover; display:block; }
.title-block { display:flex; flex-direction:column; }
.page-title { font-size:20px; font-weight:700; color:#1a6b3c; margin:0; line-height:1.2; }
.breadcrumb { font-size:11px; color:#888; margin-top:2px; }
.header-right { display:flex; align-items:center; gap:16px; }
.date-display { font-size:12px; color:#555; }
.time-display { font-size:12px; color:#1a6b3c; font-weight:700; font-variant-numeric: tabular-nums; }

/* Notification Bell */
.notification-bell-wrapper { position:relative; }
.notification-bell {
  display:flex; align-items:center; justify-content:center;
  width:38px; height:38px; border-radius:50%;
  background:#f0f9f4; border:1.5px solid #b7dfc8;
  cursor:pointer; transition:all 0.2s; position:relative;
}
.notification-bell:hover, .notification-bell.active { background:#e8f5ee; border-color:#1a6b3c; }
.notification-bell svg { fill:#1a6b3c; }
.notification-badge {
  position:absolute; top:-4px; right:-4px;
  background:#e74c3c; color:#fff;
  font-size:10px; font-weight:800; border-radius:10px;
  padding:2px 6px; min-width:18px; text-align:center;
  box-shadow:0 2px 6px rgba(231,76,60,0.4);
}
.notification-panel {
  position:absolute; top:calc(100% + 8px); right:0;
  background:#fff; border-radius:12px; width:340px;
  box-shadow:0 8px 32px rgba(0,0,0,0.15); border:1px solid #e8f5ee;
  overflow:hidden; z-index:200;
}
.notification-panel-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 16px; background:#f8f9fa; border-bottom:1px solid #e9ecef;
}
.notification-panel-header h3 { margin:0; font-size:14px; font-weight:700; color:#1a3a5c; }
.header-actions { display:flex; align-items:center; gap:8px; }
.unread-count {
  font-size:11px; font-weight:600; color:#888;
}
.mark-all-btn {
  background:none; border:none; padding:4px; cursor:pointer;
  color:#1a6b3c; display:flex; align-items:center; border-radius:4px;
  transition:background 0.15s;
}
.mark-all-btn:hover { background:#e8f5ee; }
.notification-panel-body { padding:0; max-height:500px; overflow-y:auto; }
.notifications-list { display:flex; flex-direction:column; }
.notification-item {
  display:flex; align-items:flex-start; gap:12px;
  padding:12px 16px; text-decoration:none;
  transition:background 0.15s; cursor:pointer;
  border-bottom:1px solid #f0f4f8; position:relative;
}
.notification-item:hover { background:#f8f9fa; }
.notification-item.unread { background:#f0f9ff; }
.notification-item.unread:hover { background:#e0f2fe; }
.notif-icon {
  width:36px; height:36px; border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
}
.notif-content { flex:1; min-width:0; }
.notif-title { font-size:13px; font-weight:600; color:#1a3a5c; margin-bottom:2px; }
.notif-message { font-size:12px; color:#555; margin-bottom:4px; line-height:1.4; }
.notif-time { font-size:10px; color:#aaa; }
.unread-dot {
  width:8px; height:8px; border-radius:50%;
  background:#3b82f6; flex-shrink:0; margin-top:4px;
}
.notif-delete {
  background:none; border:none; padding:4px; cursor:pointer;
  color:#aaa; display:flex; align-items:center; border-radius:4px;
  transition:all 0.15s; opacity:0; flex-shrink:0;
}
.notification-item:hover .notif-delete { opacity:1; }
.notif-delete:hover { background:#fdecea; color:#e74c3c; }
.no-notifications {
  display:flex; flex-direction:column; align-items:center;
  padding:40px 16px; text-align:center;
}
.no-notifications p { margin:8px 0 4px; font-size:14px; font-weight:600; color:#1a3a5c; }
.no-notifications span { font-size:12px; color:#888; }

.profile-wrapper { position:relative; }
.profile-btn {
  display:flex; align-items:center; gap:10px;
  background:#f0f9f4; border:1.5px solid #b7dfc8;
  padding:6px 12px 6px 6px; border-radius:24px; cursor:pointer; transition:all 0.2s;
}
.profile-btn:hover, .profile-btn.active { background:#e8f5ee; border-color:#1a6b3c; }
.profile-avatar {
  width:32px; height:32px; border-radius:50%;
  background:linear-gradient(135deg,#1a6b3c,#27ae60);
  color:#fff; display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:800; flex-shrink:0; overflow:hidden;
}
.avatar-img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.profile-info { display:flex; flex-direction:column; line-height:1.2; text-align:left; }
.profile-name { font-size:13px; font-weight:700; color:#1a6b3c; }
.profile-role { font-size:10px; color:#27ae60; }
.profile-dept { font-size:9px; color:#888; font-style:italic; margin-top:1px; }
.chevron { font-size:10px; color:#888; }
.dropdown-menu {
  position:absolute; top:calc(100% + 8px); right:0;
  background:#fff; border-radius:12px; min-width:230px;
  box-shadow:0 8px 32px rgba(0,0,0,0.15); border:1px solid #e8f5ee;
  overflow:hidden; z-index:200;
}
.dropdown-header {
  display:flex; align-items:center; gap:12px; padding:14px 16px;
  background:linear-gradient(135deg,#1a6b3c,#27ae60); color:#fff;
}
.dropdown-avatar {
  width:40px; height:40px; border-radius:50%;
  background:rgba(255,255,255,0.25);
  display:flex; align-items:center; justify-content:center;
  font-size:15px; font-weight:800; flex-shrink:0; overflow:hidden;
}
.avatar-img-lg { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.dropdown-name { font-size:14px; font-weight:700; }
.dropdown-role { font-size:11px; opacity:0.85; }
.dropdown-dept { font-size:10px; opacity:0.75; font-style:italic; margin-top:2px; }
.dropdown-divider { height:1px; background:#f0f4f8; margin:4px 0; }
.dropdown-item {
  width:100%; display:flex; align-items:center; gap:10px;
  padding:11px 16px; background:none; border:none;
  cursor:pointer; font-size:13px; color:#333; transition:background 0.15s; text-align:left;
}
.dropdown-item:hover { background:#f0f9f4; color:#1a6b3c; }
.di-icon { width:20px; height:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.di-icon svg { width:18px; height:18px; fill:#888; }
.dropdown-item:hover .di-icon svg { fill:#1a6b3c; }
.logout-item { color:#e67e22; }
.logout-item:hover { background:#fef3e2; }
.logout-item .di-icon svg { fill:#e67e22; }
.dropdown-enter-active,.dropdown-leave-active { transition:all 0.15s ease; }
.dropdown-enter-from,.dropdown-leave-to { opacity:0; transform:translateY(-6px); }
/* Modal overlay */
.modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,0.45);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display:flex; align-items:center; justify-content:center; z-index:9999;
}
/* Profile modal */
.profile-modal {
  background:#fff; border-radius:14px; width:480px; max-width:95vw;
  box-shadow:0 20px 60px rgba(0,0,0,0.25); overflow:hidden;
  animation: profileFadeDown 0.35s ease both;
}
.modal-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:16px 20px; background:linear-gradient(135deg,#1a6b3c,#27ae60); color:#fff;
}
.modal-header h3 { margin:0; font-size:17px; }
.close-btn { background:none; border:none; color:#fff; font-size:18px; cursor:pointer; opacity:0.8; }
.close-btn:hover { opacity:1; }
.modal-body { padding:20px; }
.avatar-section { display:flex; flex-direction:column; align-items:center; gap:10px; margin-bottom:20px; }
.avatar-preview {
  width:80px; height:80px; border-radius:50%;
  background:linear-gradient(135deg,#1a6b3c,#27ae60);
  display:flex; align-items:center; justify-content:center;
  font-size:28px; font-weight:800; color:#fff; overflow:hidden;
  border:3px solid #b7dfc8;
}
.avatar-preview-img { width:100%; height:100%; object-fit:cover; }
.avatar-preview-initials { font-size:28px; font-weight:800; }
.change-pic-btn {
  display:flex; align-items:center; gap:6px;
  background:#e8f5ee; border:1px solid #b7dfc8; color:#1a6b3c;
  padding:6px 14px; border-radius:20px; cursor:pointer; font-size:12px; font-weight:600;
}
.change-pic-btn:hover { background:#d4edda; }
.form-grid { display:flex; flex-direction:column; gap:12px; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:11px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:0.4px; }
.optional { font-size:10px; color:#aaa; text-transform:none; font-weight:400; }
.form-group input, .form-group select {
  padding:9px 12px; border:1.5px solid #ddd; border-radius:8px;
  font-size:13px; outline:none; transition:border-color 0.2s;
}
.form-group input:focus, .form-group select:focus { border-color:#1a6b3c; }
.form-error { background:#fdecea; color:#c0392b; padding:8px 12px; border-radius:6px; font-size:12px; margin-top:8px; }
.form-success { background:#eafaf1; color:#1a6b3c; padding:8px 12px; border-radius:6px; font-size:12px; margin-top:8px; font-weight:600; }
.modal-footer {
  display:flex; justify-content:flex-end; gap:10px;
  padding:14px 20px; border-top:1px solid #f0f4f8; background:#fafafa;
}
.btn-cancel {
  padding:9px 20px; border-radius:8px; border:1px solid #ddd;
  background:#fff; cursor:pointer; font-size:13px; font-weight:600; color:#555;
}
.btn-cancel:hover { background:#f0f4f8; }
.btn-save {
  padding:9px 20px; border-radius:8px; border:none;
  background:#1a6b3c; color:#fff; cursor:pointer; font-size:13px; font-weight:600;
}
.btn-save:hover { background:#27ae60; }

@keyframes profileFadeDown {
  from {
    opacity: 0;
    transform: translateY(-28px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Pending dot on My Profile */
.pending-dot { color: #e67e22; font-size: 10px; margin-left: auto; }

/* Requests button */
.requests-item { color: #2980b9; }
.requests-item:hover { background: #ebf5fb; }
.requests-item .di-icon svg { fill: #2980b9; }
.req-badge {
  margin-left: auto; background: #e74c3c; color: #fff;
  font-size: 10px; font-weight: 800; border-radius: 10px;
  padding: 1px 7px; min-width: 18px; text-align: center;
}

/* Approval notice in profile modal */
.approval-notice {
  display: flex; align-items: center; gap: 6px;
  background: #ebf5fb; border: 1px solid #aed6f1; border-radius: 6px;
  padding: 8px 12px; font-size: 12px; color: #2980b9; margin-top: 8px;
}
.approval-notice svg { fill: #2980b9; flex-shrink: 0; }
.pending-notice {
  background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px;
  padding: 8px 12px; font-size: 12px; color: #7d5a00; margin-top: 6px;
}
.input-readonly {
  padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  font-size: 13px; background: #f8f9fa; color: #888; cursor: not-allowed;
}

/* Requests modal */
.requests-modal {
  background: #fff; border-radius: 14px; width: 500px; max-width: 95vw;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden;
  animation: profileFadeDown 0.35s ease both;
}
.requests-body { padding: 16px 20px; max-height: 60vh; overflow-y: auto; }
.no-requests { text-align: center; color: #aaa; padding: 24px; font-size: 14px; }
.request-card {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
  border: 1px solid #e9ecef; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px;
  background: #fafafa;
}
.req-info { flex: 1; }
.req-name { font-size: 14px; font-weight: 700; color: #1a3a5c; display: flex; align-items: center; gap: 8px; }
.req-role-chip {
  font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 8px;
  background: #ebf5fb; color: #2980b9;
}
.req-date { font-size: 11px; color: #aaa; margin: 3px 0 8px; }
.req-changes { font-size: 12px; color: #555; display: flex; flex-direction: column; gap: 3px; }
.req-changes strong { color: #1a3a5c; }
.req-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
.btn-approve {
  padding: 7px 14px; border-radius: 7px; border: none;
  background: #1a6b3c; color: #fff; cursor: pointer; font-size: 12px; font-weight: 700;
}
.btn-approve:hover { background: #27ae60; }
.btn-reject {
  padding: 7px 14px; border-radius: 7px; border: none;
  background: #e74c3c; color: #fff; cursor: pointer; font-size: 12px; font-weight: 700;
}
.btn-reject:hover { background: #c0392b; }
</style>
