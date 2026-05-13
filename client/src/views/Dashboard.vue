<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useEmployeeStore } from '@/stores/employees'
import { useLeaveStore } from '@/stores/leave'
import { useDTRStore } from '@/stores/dtr'

const router = useRouter()
const empStore = useEmployeeStore()
const leaveStore = useLeaveStore()
const dtrStore = useDTRStore()

const svgIcons = {
  employees: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  birthday:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 6c1.11 0 2-.89 2-2 0-.32-.08-.61-.21-.87L12 0l-1.79 3.13A1.99 1.99 0 0 0 10 4c0 1.11.89 2 2 2zm4.6 9.99l-1.07-1.07-1.08 1.07c-1.3 1.3-3.58 1.31-4.89 0l-1.07-1.07-1.09 1.07C6.75 17.74 5.88 18 5 18c-.88 0-1.75-.26-2.45-.66V22c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-4.66c-.7.4-1.57.66-2.45.66-.88 0-1.75-.26-2.5-.01zM18 9H6c-1.66 0-3 1.34-3 3v1.54c0 1.08.88 1.96 1.96 1.96.54 0 1.02-.22 1.38-.57l2.14-2.13 2.13 2.13c.74.74 2.03.74 2.77 0l2.14-2.13 2.13 2.13c.36.35.84.57 1.38.57C20.12 15.5 21 14.62 21 13.54V12c0-1.66-1.34-3-3-3z"/></svg>`,
  leave:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  dtr:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  ai:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 10.5h-1.5V9A4.5 4.5 0 0 0 15 4.5h-.09A3.5 3.5 0 0 0 8.09 4.5H8A4.5 4.5 0 0 0 3.5 9v1.5H2a1 1 0 0 0-1 1V18a1 1 0 0 0 1 1h19a1 1 0 0 0 1-1v-6.5a1 1 0 0 0-1-1zM9 15H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></svg>`,
  calendar:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  trainings: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>`,
  hospital:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H8v-4h4v4zm0-6H8V7h4v4zm4 6h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>`,
  check:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  prev:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>`,
  next:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>`,
}

const stats = computed(() => [
  { label: 'Total Employees',    value: empStore.employees.filter(e => e.active).length, iconKey: 'employees', color: '#1a3a5c', bg: '#e8f0fe', to: '/employees' },
  { label: 'Birthday Celebrants',value: empStore.birthdayCelebrantsThisMonth.length,     iconKey: 'birthday',  color: '#c0392b', bg: '#fdecea', to: '/employees/birthdays', sub: 'This Month' },
  { label: 'Pending Leaves',     value: leaveStore.leaveRecords.filter(l => l.status === 'Pending').length, iconKey: 'leave', color: '#e67e22', bg: '#fef3e2', to: '/leave' },
  { label: 'DTR Pending',        value: dtrStore.dtrRecords.filter(d => d.status === 'Pending').length,     iconKey: 'dtr',   color: '#8e44ad', bg: '#f5eef8', to: '/dtr' },
  { label: 'Turning 65 This Year',value: empStore.turning65ThisYear.length, iconKey: 'employees', color: '#2980b9', bg: '#ebf5fb', to: '/employees/birthdays' },
])

const quickLinks = [
  { label: 'Add Employee',    iconKey: 'employees', to: '/employees/new', color: '#1a3a5c' },
  { label: 'DTR Transmittal', iconKey: 'dtr',       to: '/dtr',           color: '#8e44ad' },
  { label: 'Leave Request',   iconKey: 'leave',     to: '/leave',         color: '#e67e22' },
  { label: 'AI Scanning',     iconKey: 'ai',        to: '/ai-scanning',   color: '#2980b9' },
  { label: 'Schedule',        iconKey: 'calendar',  to: '/schedule',      color: '#c0392b' },
  { label: 'Trainings',       iconKey: 'trainings', to: '/trainings',     color: '#1a6b3c' },
]

// ── Birthday ticker for stat card ─────────────────────────────────────────────
const bdayTickerNames = computed(() =>
  empStore.birthdayCelebrantsThisMonth
    .map(e => `${e.firstName} ${e.lastName}`)
    .join('  •  ')
)

// ── Upcoming birthday celebrant (next by date in this month) ──────────────────
const upcomingBday = computed(() => {
  const today = new Date()
  const list  = empStore.birthdayCelebrantsThisMonth
  if (!list.length) return null

  // Sort by day of month, pick the first one on or after today
  const sorted = [...list].sort((a, b) => {
    const da = new Date(a.birthDate).getDate()
    const db = new Date(b.birthDate).getDate()
    return da - db
  })

  const upcoming = sorted.find(e => new Date(e.birthDate).getDate() >= today.getDate())
  return upcoming ?? sorted[0] // fallback to first if all have passed
})

const currentMonthName = new Date().toLocaleString('en-PH', { month: 'long' })
</script>

<template>
  <div class="dashboard">
    <div class="welcome-banner">
      <div class="welcome-text">
        <h2>Welcome to GEAMH HRIS</h2>
        <p>General Emilio Aguinaldo Memorial Hospital — Human Resource Information System</p>
      </div>
      <div class="hospital-seal" v-html="svgIcons.hospital"></div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="stat-card"
        :class="{ 'stat-card-bday': stat.iconKey === 'birthday' }"
        :style="{ background: stat.bg, borderLeft: `4px solid ${stat.color}` }"
        @click="router.push(stat.to)"
      >
        <div class="stat-icon" :style="{ color: stat.color }" v-html="svgIcons[stat.iconKey]"></div>
        <div class="stat-info">
          <div class="stat-value" :style="{ color: stat.color }">{{ stat.value }}</div>
          <div class="stat-label">{{ stat.label }}</div>
          <div v-if="stat.sub" class="stat-sub">{{ stat.sub }}</div>
          <!-- Scrolling names ticker for birthday card -->
          <div v-if="stat.iconKey === 'birthday' && bdayTickerNames" class="bday-ticker-wrap">
            <div class="bday-ticker">
              <span class="bday-ticker-text">{{ bdayTickerNames }}&nbsp;&nbsp;&nbsp;&nbsp;</span>
              <span class="bday-ticker-text" aria-hidden="true">{{ bdayTickerNames }}&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cards — 2 columns -->
    <div class="dashboard-grid">

      <!-- Quick Actions -->
      <div class="card">
        <h3 class="card-title">
          <span class="title-icon" v-html="svgIcons.check"></span>
          Quick Actions
        </h3>
        <div class="quick-links">
          <button
            v-for="link in quickLinks"
            :key="link.to"
            class="quick-link-btn"
            :style="{ borderColor: link.color, color: link.color }"
            @click="router.push(link.to)"
          >
            <span class="ql-icon" v-html="svgIcons[link.iconKey]"></span>
            <span>{{ link.label }}</span>
          </button>
        </div>
      </div>

      <!-- Upcoming Birthday Celebrant -->
      <div class="card">
        <h3 class="card-title">
          <span class="title-icon" v-html="svgIcons.birthday"></span>
          Upcoming Birthday — {{ currentMonthName }}
        </h3>

        <div v-if="!upcomingBday" class="empty-state">
          No birthday celebrants this month.
        </div>

        <div v-else class="upcoming-bday-card">
          <div class="upcoming-avatar">
            {{ upcomingBday.firstName?.[0] }}{{ upcomingBday.lastName?.[0] }}
          </div>
          <div class="upcoming-info">
            <strong>{{ upcomingBday.lastName }}, {{ upcomingBday.firstName }}</strong>
            <span class="upcoming-position">{{ upcomingBday.position || '—' }}</span>
            <span class="upcoming-dept">{{ upcomingBday.department || '—' }}</span>
            <span class="upcoming-date">
              <span class="bday-icon" v-html="svgIcons.birthday"></span>
              {{ upcomingBday.birthDate
                  ? new Date(upcomingBday.birthDate).toLocaleDateString('en-PH', { month: 'long', day: 'numeric' })
                  : '—' }}
            </span>
          </div>
        </div>

        <div v-if="empStore.birthdayCelebrantsThisMonth.length > 1" class="more-celebrants">
          +{{ empStore.birthdayCelebrantsThisMonth.length - 1 }} more celebrant(s) this month
        </div>

        <router-link to="/employees/birthdays" class="view-all-link">View all →</router-link>
      </div>

    </div>
  </div>
</template>

<style scoped>
.dashboard { padding: 24px; }
.welcome-banner {
  background: linear-gradient(135deg, #1a6b3c 0%, #27ae60 100%);
  color: #fff; border-radius: 12px; padding: 24px 32px;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px;
}
.welcome-text h2 { margin: 0 0 6px; font-size: 22px; }
.welcome-text p  { margin: 0; opacity: 0.8; font-size: 13px; }
.hospital-seal { width: 56px; height: 56px; opacity: 0.9; }
.hospital-seal :deep(svg) { width: 100%; height: 100%; fill: #fff; }

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  border-radius: 10px; padding: 16px;
  display: flex; align-items: center; gap: 14px;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.stat-icon { width: 36px; height: 36px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.stat-icon :deep(svg) { width: 100%; height: 100%; }
.stat-value { font-size: 28px; font-weight: 800; line-height: 1; }
.stat-label { font-size: 12px; color: #555; margin-top: 2px; }
.stat-sub   { font-size: 10px; color: #888; }

/* Birthday ticker */
.bday-ticker-wrap {
  overflow: hidden;
  width: 100%;
  max-width: 160px;
  margin-top: 5px;
}
.bday-ticker {
  display: flex;
  width: max-content;
  animation: ticker-scroll 3s linear infinite;
}
.bday-ticker-text {
  white-space: nowrap;
  font-size: 10px;
  color: #c0392b;
  font-weight: 600;
  flex-shrink: 0;
}
@keyframes ticker-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* Cards — 2 equal columns */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
.card {
  background: #fff; border-radius: 12px; padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
}
.card-title {
  font-size: 15px; font-weight: 700; color: #1a6b3c;
  margin: 0 0 16px; padding-bottom: 10px;
  border-bottom: 2px solid #f0f4f8;
  display: flex; align-items: center; gap: 8px;
}
.title-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.title-icon :deep(svg) { width: 100%; height: 100%; fill: #1a6b3c; }

/* Quick links */
.quick-links { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.quick-link-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 12px; border: 2px solid; border-radius: 8px;
  background: #fff; cursor: pointer; font-size: 13px; font-weight: 600;
  transition: all 0.2s;
}
.quick-link-btn:hover { opacity: 0.8; transform: scale(1.02); }
.ql-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ql-icon :deep(svg) { width: 100%; height: 100%; }

/* Upcoming birthday card */
.upcoming-bday-card {
  display: flex; align-items: center; gap: 16px;
  background: linear-gradient(135deg, #fffbea, #fff8f0);
  border: 1px solid #fde8a0; border-radius: 12px;
  padding: 16px 18px; margin-bottom: 10px;
}
.upcoming-avatar {
  width: 56px; height: 56px; border-radius: 50%;
  background: linear-gradient(135deg, #ffd700, #ffb300);
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 20px; color: #1a3a5c; flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
}
.upcoming-info { display: flex; flex-direction: column; gap: 3px; }
.upcoming-info strong { font-size: 16px; color: #1a1a2e; font-weight: 700; }
.upcoming-position { font-size: 12px; color: #555; }
.upcoming-dept { font-size: 11px; color: #888; }
.upcoming-date { display: flex; align-items: center; gap: 4px; color: #c0392b; font-weight: 600; font-size: 13px; margin-top: 2px; }
.bday-icon { width: 13px; height: 13px; display: inline-flex; align-items: center; justify-content: center; }
.bday-icon :deep(svg) { width: 100%; height: 100%; fill: #c0392b; }
.more-celebrants { font-size: 11px; color: #888; text-align: center; margin-bottom: 4px; }
.view-all-link { display: block; text-align: right; margin-top: 12px; font-size: 12px; color: #1a6b3c; text-decoration: none; font-weight: 600; }
.empty-state { color: #aaa; font-size: 13px; text-align: center; padding: 20px; }

/* Responsive */
@media (max-width: 1280px) {
  .stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .dashboard-grid { grid-template-columns: 1fr; }
}
</style>
