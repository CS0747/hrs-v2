<script setup>
import { ref, computed, watchEffect, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth  = useAuthStore()

const props = defineProps({ open: { type: Boolean, default: true } })
const emit  = defineEmits(['toggle'])

// SVG icon paths — all filled, solid black style
const icons = {
  dashboard: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>`,
  employees: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  birthday: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 6c1.11 0 2-.89 2-2 0-.32-.08-.61-.21-.87L12 0l-1.79 3.13c-.13.26-.21.55-.21.87 0 1.11.89 2 2 2zm4.6 9.99l-1.07-1.07-1.08 1.07c-1.3 1.3-3.58 1.31-4.89 0l-1.07-1.07-1.09 1.07C6.75 17.74 5.88 18 5 18c-.88 0-1.75-.26-2.45-.66V22c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-4.66c-.7.4-1.57.66-2.45.66-.88 0-1.75-.26-2.5-.01zM18 9H6c-1.66 0-3 1.34-3 3v1.54c0 1.08.88 1.96 1.96 1.96.54 0 1.02-.22 1.38-.57l2.14-2.13 2.13 2.13c.74.74 2.03.74 2.77 0l2.14-2.13 2.13 2.13c.36.35.84.57 1.38.57C20.12 15.5 21 14.62 21 13.54V12c0-1.66-1.34-3-3-3z"/></svg>`,
  schedule: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  dtr: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  audit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  leave: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/><path d="M9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>`,
  to: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5h-9.17C6.41 5 2 9.41 2 14.83V15h20V6c0-.55-.45-1-1-1m-8 6H5.01c1.34-2.38 3.89-4 6.82-4H13zm-8 8h16c.55 0 1-.45 1-1v-2H2c0 1.65 1.35 3 3 3"/></svg>`,
  verification: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  tracking: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
  signatories: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  workflow: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>`,
  ai: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>`,
  hospital: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>`,
  hrgroup: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12.75c1.63 0 3.07.39 4.24.9 1.08.48 1.76 1.56 1.76 2.73V18H6v-1.61c0-1.18.68-2.26 1.76-2.73 1.17-.52 2.61-.91 4.24-.91zM4 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm1.13 1.1c-.37-.06-.74-.1-1.13-.1-.99 0-1.93.21-2.78.58C.48 14.9 0 15.62 0 16.43V18h4.5v-1.61c0-.83.23-1.61.63-2.29zM20 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm4 3.43c0-.81-.48-1.53-1.22-1.85-.85-.37-1.79-.58-2.78-.58-.39 0-.76.04-1.13.1.4.68.63 1.46.63 2.29V18H24v-1.57zM12 6c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z"/></svg>`,
  dtrgroup: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`,
  leavegroup: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  tools: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>`,
  admin: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>`,
  accounts:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  audittrail: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 9H7v-2h4v2zm4-4H7v-2h8v2zm0-4H7V8h8v2z"/></svg>`,
  versionhist:`<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>`,
  usermanual: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>`,
  dios:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>`,
  trainings: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>`,
  department: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
}

const allMenuGroups = computed(() => {
  const role = auth.userRole

  // Section Admin: only HR Management (no Departments) + User Manual
  if (role === 'Section Admin') {
    return [
      {
        label: 'HR Management',
        iconKey: 'hrgroup',
        items: [
          { label: 'Schedule Database', iconKey: 'schedule', to: '/schedule' },
        ],
      },
      {
        label: 'Administration',
        iconKey: 'admin',
        items: [
          { label: 'User Manual', iconKey: 'usermanual', to: '/user-manual' },
        ],
      },
    ]
  }

  // All other roles — full menu filtered by role
  return [
    {
      label: 'HR Management',
      iconKey: 'hrgroup',
      items: [
        { label: 'Employee Masterlist', iconKey: 'employees',  to: '/employees' },
        { label: 'Birthday Celebrants', iconKey: 'birthday',   to: '/employees/birthdays' },
        { label: 'Schedule Database',   iconKey: 'schedule',   to: '/schedule' },
        { label: 'Trainings',           iconKey: 'trainings',  to: '/trainings' },
        { label: 'Departments',         iconKey: 'department', to: '/departments' },
      ],
    },
    {
      label: 'Leave & T.O.',
      iconKey: 'leavegroup',
      items: [
        { label: 'Leave Management',    iconKey: 'leave', to: '/leave' },
        { label: 'Travel Order (T.O.)', iconKey: 'to',    to: '/to' },
      ],
    },
    {
      label: 'DTR & Transmittal',
      iconKey: 'dtrgroup',
      items: [
        { label: 'DTR Transmittal',     iconKey: 'dtr',   to: '/dtr' },
        { label: 'Transmittal Summary', iconKey: 'audit', to: '/audit' },
      ],
    },
    {
      label: 'Workflow',
      iconKey: 'workflow',
      items: [
        { label: 'Verification',         iconKey: 'verification', to: '/verification' },
        { label: 'Tracking & Receiving', iconKey: 'tracking',     to: '/tracking' },
        { label: 'Signatories',          iconKey: 'signatories',  to: '/signatories' },
      ],
    },
    {
      label: 'Tools',
      iconKey: 'tools',
      items: [
        { label: 'AI Scanning Tools', iconKey: 'ai', to: '/ai-scanning' },
      ],
    },
    ...(role === 'DIOS' ? [{
      label: 'Administration',
      iconKey: 'admin',
      items: [
        { label: 'Account Management', iconKey: 'accounts',    to: '/accounts' },
        { label: 'Audit History',      iconKey: 'audittrail',  to: '/audit-trail' },
        { label: 'Version History',    iconKey: 'versionhist', to: '/version-history' },
        { label: 'System Control',     iconKey: 'dios',        to: '/dios-control' },
        { label: 'User Manual',        iconKey: 'usermanual',  to: '/user-manual' },
      ],
    }] : [{
      label: 'Administration',
      iconKey: 'admin',
      items: [
        { label: 'Version History', iconKey: 'versionhist', to: '/version-history' },
        { label: 'User Manual',     iconKey: 'usermanual',  to: '/user-manual' },
      ],
    }]),
  ]
})

const menuGroups = allMenuGroups

const collapsed = ref({})
const manuallyToggled = ref({}) // Track which groups user manually toggled

function expandActiveGroup() {
  menuGroups.value.forEach((group, i) => {
    const hasActive = group.items.some(item => isActive(item.to))
    // Only auto-expand if user hasn't manually toggled this group
    if (hasActive && !manuallyToggled.value[i]) {
      collapsed.value[i] = false
    } else if (collapsed.value[i] === undefined) {
      collapsed.value[i] = true
    }
  })
}

// Initialize on mount
watchEffect(expandActiveGroup)

// Re-expand when route changes (e.g. quick action click)
watch(() => route.path, () => {
  // Clear manual toggle tracking on route change
  manuallyToggled.value = {}
  expandActiveGroup()
})

function toggle(i) {
  collapsed.value[i] = !collapsed.value[i]
  // Mark this group as manually toggled
  manuallyToggled.value[i] = true
}

function isActive(to) {
  if (route.path === to) return true
  if (route.path.startsWith(to + '/')) {
    const allItems = menuGroups.value.flatMap(g => g.items)
    const exactMatch = allItems.find(item => item.to === route.path)
    if (exactMatch) return false
    return true
  }
  return false
}

const sidebarOpen = ref(true)
</script>

<template>
  <aside class="sidebar" :class="{ collapsed: !props.open }">
    <div class="sidebar-header">
      <div class="hospital-logo clickable" @click="emit('toggle')" title="Toggle sidebar">
        <img src="/GEAMH LOGO.png" alt="GEAMH Logo" class="logo-img" />
        <div v-if="props.open" class="hospital-name">
          <strong>GEAMH</strong>
          <small>HRIS Portal</small>
        </div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/" class="nav-item home-link" :class="{ active: route.path === '/' }">
        <span class="nav-icon" v-html="icons.dashboard"></span>
        <span v-if="props.open" class="nav-label">Dashboard</span>
      </router-link>

      <div v-for="(group, i) in menuGroups" :key="i" class="nav-group">
        <button 
          class="group-header" 
          @click="toggle(i)"
        >
          <span class="nav-icon" v-html="icons[group.iconKey]"></span>
          <span v-if="props.open" class="group-label">{{ group.label }}</span>
          <span v-if="props.open" class="chevron" :class="{ rotated: !collapsed[i] }">▸</span>
        </button>
        <transition name="dropdown">
          <div v-if="!collapsed[i]" class="group-items">
          <router-link
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
          >
            <span class="nav-icon" v-html="icons[item.iconKey]"></span>
            <span v-if="props.open" class="nav-label">{{ item.label }}</span>
          </router-link>
          </div>
        </transition>
      </div>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 260px;
  height: 100vh;
  position: sticky;
  top: 0;
  background: linear-gradient(180deg, #1a6b3c 0%, #0d3d20 100%);
  color: #fff;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  flex-shrink: 0;
  overflow-y: auto;
  overflow-x: hidden;
}
.sidebar.collapsed { width: 60px; }
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  min-height: 70px;
}
.hospital-logo { display: flex; align-items: center; gap: 10px; }
.hospital-logo.clickable { cursor: pointer; }
.hospital-logo.clickable:hover .logo-img { opacity: 0.85; transform: scale(1.05); }
.logo-img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,0.3);
  transition: opacity 0.2s, transform 0.2s;
}
.logo-icon {
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.logo-icon :deep(svg) { width: 100%; height: 100%; fill: #fff; }
.hospital-name { display: flex; flex-direction: column; line-height: 1.2; }
.hospital-name strong { font-size: 14px; color: #fff; }
.hospital-name small { font-size: 10px; color: rgba(255,255,255,0.6); }
.sidebar-nav { padding: 8px 0; flex: 1; }
.home-link { 
  margin: 0 0 12px 0;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding-bottom: 12px;
}
.nav-group { margin-bottom: 8px; }
.group-header {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
  background: rgba(0,0,0,0.15);
  border: none;
  border-left: 3px solid transparent;
  color: #fff;
  cursor: pointer;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  box-sizing: border-box;
  margin: 8px 0 4px 0;
  transition: all 0.2s ease;
}
.group-header:hover { 
  color: #fff; 
  background: rgba(255,255,255,0.12); 
  border-left-color: #ffd700;
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(255,215,0,0.15);
}
.group-label { 
  flex: 1; 
  text-align: left;
  font-size: 11px;
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.chevron { 
  font-size: 11px; 
  color: rgba(255,255,255,0.6);
  font-weight: 900;
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-block;
}
.chevron.rotated {
  transform: rotate(90deg);
}
.group-header:hover .chevron {
  transform: scale(1.15);
}
.group-header:hover .chevron.rotated {
  transform: rotate(90deg) scale(1.15);
}
.group-items { 
  padding-left: 0;
  background: rgba(0,0,0,0.08);
  border-left: 2px solid rgba(255,215,0,0.2);
  margin-bottom: 4px;
  overflow: hidden;
}

/* Vue transition classes for smooth dropdown animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  transform-origin: top;
}

.dropdown-enter-from {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.dropdown-enter-to {
  opacity: 1;
  max-height: 800px;
  transform: translateY(0);
}

.dropdown-leave-from {
  opacity: 1;
  max-height: 800px;
  transform: translateY(0);
}

.dropdown-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}
.nav-item {
  width: 100%;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 8px 0 8px 36px;
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 500;
  transition: background 0.15s ease, color 0.15s ease, transform 0.1s ease, padding-left 0.15s ease;
  margin: 0;
  border-radius: 0;
  box-sizing: border-box;
  border-left: 3px solid transparent;
}
/* When sidebar is collapsed, center icon only */
.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 10px 0;
}
.sidebar.collapsed .group-header {
  justify-content: center;
  padding: 10px 0;
}
.sidebar.collapsed .home-link {
  justify-content: center;
}
.nav-item:hover { 
  background: rgba(255,255,255,0.1); 
  color: #fff;
  border-left-color: rgba(255,215,0,0.5);
  padding-left: 40px;
}
.nav-item:active { 
  transform: scale(0.98); 
  background: rgba(255,255,255,0.15); 
}
.nav-item.active {
  background: linear-gradient(90deg, #ffd700, #ffb300);
  color: #1a6b3c;
  font-weight: 600;
}

/* SVG icon container — exact fixed width so all icons share the same vertical axis */
.nav-icon {
  width: 20px;
  min-width: 20px;
  height: 20px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Default icon color = white */
.nav-icon :deep(svg) {
  width: 18px;
  height: 18px;
  fill: #000;
  filter: brightness(0) invert(1);
}

/* Active item — icon black on gold */
.nav-item.active .nav-icon :deep(svg) {
  filter: none;
  fill: #000;
}

/* Group header icons — larger and gold */
.group-header .nav-icon {
  width: 22px;
  min-width: 22px;
  height: 22px;
}
.group-header .nav-icon :deep(svg) {
  width: 20px;
  height: 20px;
  fill: #ffd700;
  filter: none;
  drop-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.nav-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
  text-align: left;
  flex: 1;
}
</style>
