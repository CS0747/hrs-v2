<script setup>
import { ref, computed } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useEmployeeStore } from '@/stores/employees'
import { useAuthStore } from '@/stores/auth'
import { usePermissions } from '@/composables/usePermissions'
import { useNotificationStore } from '@/stores/notifications'
import { onMounted } from 'vue'
import { printSchedules, printIndividualSchedule, printDepartmentSchedule, printTransmittalReport } from '@/utils/print'
import ScheduleForm from '@/components/schedule/ScheduleForm.vue'
import MonitoringDashboard from '@/components/schedule/MonitoringDashboard.vue'
import ShiftLegend from '@/components/schedule/ShiftLegend.vue'

const store    = useScheduleStore()
const empStore = useEmployeeStore()
const auth     = useAuthStore()
const { hasPermission, loadPermissions } = usePermissions()
const notificationStore = useNotificationStore()

onMounted(async () => {
  await loadPermissions()
})

// ── Icons ────────────────────────────────────────────────────────────────────
const svgIcons = {
  search:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  save:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  warn:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
}

// ── Constants ────────────────────────────────────────────────────────────────
const ALL_DAYS     = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MAX_WORK_DAYS = 6

const SHIFT_TIMES = {
  Morning:   '07:00 AM - 03:00 PM',
  Afternoon: '03:00 PM - 11:00 PM',
  Night:     '11:00 PM - 07:00 AM',
  Split:     '06:00 AM - 10:00 AM / 02:00 PM - 06:00 PM',
  Flexible:  'Flexible',
}

// ── Form state ───────────────────────────────────────────────────────────────
function blankForm() {
  const defaultDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  return {
    employeeNo:    '',
    employeeName:  '',
    department:    '',
    shift:         'Morning',
    shiftTime:     '07:00 AM - 03:00 PM',
    days:          [...defaultDays],
    effectiveDate: '',
    endDate:       '',
    restDay:       ALL_DAYS.filter(d => !defaultDays.includes(d)).join(', '),
    selectedDates: [],
  }
}

const form     = ref(blankForm())
const showForm = ref(false)
const editId   = ref(null)
const saving   = ref(false)
const showConfirm = ref(false)

// ── Delete modal state ───────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

// ── Toolbar filters ──────────────────────────────────────────────────────────
const search      = ref('')
const filterDept  = ref('')
const filterShift = ref('')

// ── Employee combobox ────────────────────────────────────────────────────────
const empSearch   = ref('')
const empDropOpen = ref(false)

const filteredEmps = computed(() => {
  const q = empSearch.value.toLowerCase().trim()
  if (!q) return empStore.employees.slice(0, 50)
  return empStore.employees.filter(e =>
    e.lastName.toLowerCase().includes(q) ||
    e.firstName.toLowerCase().includes(q) ||
    e.employeeNo.toLowerCase().includes(q)
  ).slice(0, 50)
})

function selectEmployee(emp) {
  form.value.employeeNo   = emp.employeeNo
  form.value.employeeName = `${emp.lastName}, ${emp.firstName}`
  form.value.department   = emp.department
  empSearch.value   = `${emp.employeeNo} — ${emp.lastName}, ${emp.firstName}`
  empDropOpen.value = false
}

function onEmpSearchInput() {
  empDropOpen.value       = true
  form.value.employeeNo   = ''
  form.value.employeeName = ''
  form.value.department   = ''
}

function onEmpBlur() {
  setTimeout(() => { empDropOpen.value = false }, 180)
}

// ── Days logic ───────────────────────────────────────────────────────────────
const restDays = computed(() =>
  ALL_DAYS.filter(d => !form.value.days.includes(d))
)

function onDayChange() {
  form.value.restDay = restDays.value.join(', ')
}

function isDayDisabled(day) {
  return form.value.days.length >= MAX_WORK_DAYS && !form.value.days.includes(day)
}

function onShiftChange() {
  form.value.shiftTime = SHIFT_TIMES[form.value.shift] || ''
}

// ── Open modals ──────────────────────────────────────────────────────────────
function openAdd() {
  editId.value      = null
  form.value        = blankForm()
  empSearch.value   = ''
  empDropOpen.value = false
  showForm.value    = true
}

function openEdit(s) {
  editId.value = s.id
  form.value   = {
    employeeNo:    s.employeeNo,
    employeeName:  s.employeeName,
    department:    s.department,
    shift:         s.shift,
    shiftTime:     s.shiftTime,
    days:          [...(s.days ?? [])],
    effectiveDate: s.effectiveDate,
    endDate:       s.endDate,
    restDay:       ALL_DAYS.filter(d => !(s.days ?? []).includes(d)).join(', '),
  }
  empSearch.value   = s.employeeNo ? `${s.employeeNo} — ${s.employeeName}` : ''
  empDropOpen.value = false
  showForm.value    = true
}

// ── Save ─────────────────────────────────────────────────────────────────────
async function save() {
  if (saving.value) return
  // Show confirmation only for updates
  if (editId.value) { showConfirm.value = true; return }
  await doSave()
}

async function doSave() {
  showConfirm.value = false
  saving.value = true
  try {
    if (editId.value) {
      await store.updateSchedule(editId.value, { ...form.value })
    } else {
      // Check if using new format with day schedules
      if (form.value.daySchedules && Object.keys(form.value.daySchedules).length > 0) {
        // Create individual schedules for each date with its own time and shift
        const promises = []
        for (const [dateKey, dayConfig] of Object.entries(form.value.daySchedules)) {
          promises.push(store.addSchedule({
            employeeNo: form.value.employeeNo,
            employeeName: form.value.employeeName,
            department: form.value.department,
            scheduleDate: dateKey,
            startTime: dayConfig.startTime,
            endTime: dayConfig.endTime,
            shiftCode: dayConfig.shiftCode,
            shiftName: dayConfig.shiftCode === 'OFF' ? 'Off Duty' : 
                       dayConfig.shiftCode === '85' ? 'Standard' :
                       dayConfig.shiftCode === '62' ? 'Morning' :
                       dayConfig.shiftCode === '210' ? 'Evening' :
                       dayConfig.shiftCode === '106' ? 'Night' : 'Custom',
            remarks: form.value.remarks || ''
          }))
        }
        await Promise.all(promises)
      } else {
        // Legacy format or single schedule
        await store.addSchedule({ ...form.value })
      }
    }
    showForm.value = false
    notificationStore.success('Schedule(s) saved successfully')
  } catch (e) {
    notificationStore.error('Failed to save schedule: ' + e.message)
  } finally {
    saving.value = false
  }
}

// ── Delete ────────────────────────────────────────────────────────────────────
function promptDelete(s) {
  deleteTarget.value  = s
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  deleteTarget.value  = null
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await store.deleteSchedule(deleteTarget.value.id)
  } catch (e) {
    notificationStore.error('Failed to delete: ' + e.message)
  } finally {
    cancelDelete()
  }
}

// ── Approval workflow ─────────────────────────────────────────────────────────
// Schedules need approval before they can be edited
// Status: Pending → Approved / Rejected
const filterApproval = ref('')

function approveSchedule(s) {
  store.updateSchedule(s.id, { ...s, approvalStatus: 'Approved', approvedBy: 'HR AMELA', approvedAt: auth.nowTimestamp() })
}
function rejectSchedule(s) {
  store.updateSchedule(s.id, { ...s, approvalStatus: 'Rejected' })
}

// Only allow editing approved schedules
function canEditSchedule(s) {
  return !s.approvalStatus || s.approvalStatus === 'Approved'
}

function approvalBadge(s) {
  if (!s.approvalStatus || s.approvalStatus === 'Pending') return 'badge-orange'
  if (s.approvalStatus === 'Approved') return 'badge-green'
  return 'badge-red'
}
// ── Filtered table list ───────────────────────────────────────────────────────
const filtered = computed(() =>
  store.schedules.filter(s => {
    const q = search.value.toLowerCase()
    const matchSearch = !q || s.employeeName.toLowerCase().includes(q) || s.employeeNo.toLowerCase().includes(q)
    const matchDept     = !filterDept.value     || s.department === filterDept.value
    const matchShift    = !filterShift.value    || s.shift === filterShift.value
    const matchApproval = !filterApproval.value || (s.approvalStatus || 'Pending') === filterApproval.value
    return matchSearch && matchDept && matchShift && matchApproval
  })
)

function shiftColor(shift) {
  const map = {
    Morning: 'badge-yellow', Afternoon: 'badge-orange',
    Night: 'badge-blue', Split: 'badge-purple', Flexible: 'badge-gray',
  }
  return map[shift] || 'badge-gray'
}

// ── Calendar ──────────────────────────────────────────────────────────────────
const calendarAnchor = ref(new Date()) // any date in the current week

// Day-name → index (Mon=0 … Sun=6)
const DAY_INDEX = { Mon: 0, Tue: 1, Wed: 2, Thu: 3, Fri: 4, Sat: 5, Sun: 6 }

// Shift colour palette for calendar blocks
const SHIFT_BLOCK_STYLE = {
  Morning:   { bg: '#e8f5e9', border: '#66bb6a', text: '#2e7d32' },
  Afternoon: { bg: '#fff3e0', border: '#ffa726', text: '#e65100' },
  Night:     { bg: '#e3f2fd', border: '#42a5f5', text: '#0d47a1' },
  Split:     { bg: '#f3e5f5', border: '#ab47bc', text: '#6a1b9a' },
  Flexible:  { bg: '#f5f5f5', border: '#bdbdbd', text: '#424242' },
}

// Shift dot colours for the legend
const SHIFT_DOT_COLOR = {
  Morning: '#f9a825', Afternoon: '#ef6c00', Night: '#1565c0',
  Split: '#7b1fa2', Flexible: '#757575',
}

// Grid starts at 07:00; each hour = 60px
const GRID_START_HOUR = 7
const HOUR_PX = 60

// Shift time → { startH, endH } in 24-h
const SHIFT_HOURS = {
  Morning:   { startH: 7,  endH: 15 },
  Afternoon: { startH: 15, endH: 23 },
  Night:     { startH: 23, endH: 31 }, // 31 = 07:00 next day (clamped to grid end)
  Split:     { startH: 6,  endH: 18 },
  Flexible:  { startH: 8,  endH: 12 },
}

// Hours shown in the time gutter: 07:00 – 22:00
const TIME_ROWS = Array.from({ length: 16 }, (_, i) => {
  const h = GRID_START_HOUR + i
  const hh = h % 24
  const ampm = hh < 12 ? 'AM' : 'PM'
  const disp = hh === 0 ? 12 : hh > 12 ? hh - 12 : hh
  return { label: `${String(disp).padStart(2, '0')}:00 ${ampm}`, hour: h }
})

// Monday of the week that contains `calendarAnchor`
const weekStart = computed(() => {
  const d = new Date(calendarAnchor.value)
  const dow = d.getDay() // 0=Sun
  const diff = dow === 0 ? -6 : 1 - dow // shift to Monday
  d.setDate(d.getDate() + diff)
  d.setHours(0, 0, 0, 0)
  return d
})

// Array of 7 Date objects for Mon–Sun of the current week
const weekDays = computed(() => {
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart.value)
    d.setDate(d.getDate() + i)
    return d
  })
})

// Header label e.g. "May 12 – 18, 2026"
const weekLabel = computed(() => {
  const days = weekDays.value
  const opts = { month: 'short', day: 'numeric' }
  const start = days[0].toLocaleDateString('en-US', opts)
  const endDay = days[6].getDate()
  const year = days[6].getFullYear()
  return `${start} – ${endDay}, ${year}`
})

function prevWeek() {
  const d = new Date(calendarAnchor.value)
  d.setDate(d.getDate() - 7)
  calendarAnchor.value = d
}
function nextWeek() {
  const d = new Date(calendarAnchor.value)
  d.setDate(d.getDate() + 7)
  calendarAnchor.value = d
}

function isToday(date) {
  const t = new Date()
  return date.getFullYear() === t.getFullYear() &&
         date.getMonth()    === t.getMonth()    &&
         date.getDate()     === t.getDate()
}

// Navigate mini-calendar click to the week containing that date
function miniCalClick(date) {
  calendarAnchor.value = new Date(date)
}

// ── Mini-month calendar ───────────────────────────────────────────────────────
const miniMonth = computed(() => {
  const anchor = calendarAnchor.value
  const year  = anchor.getFullYear()
  const month = anchor.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay  = new Date(year, month + 1, 0)

  // Pad to Monday-start grid
  let startDow = firstDay.getDay() // 0=Sun
  startDow = startDow === 0 ? 6 : startDow - 1 // Mon=0

  const cells = []
  // Leading empty cells
  for (let i = 0; i < startDow; i++) cells.push(null)
  // Day cells
  for (let d = 1; d <= lastDay.getDate(); d++) {
    cells.push(new Date(year, month, d))
  }
  // Trailing empty cells to complete last row
  while (cells.length % 7 !== 0) cells.push(null)

  const monthName = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
  return { cells, monthName, year, month }
})

function isMiniSelected(date) {
  if (!date) return false
  const ws = weekStart.value
  const we = weekDays.value[6]
  return date >= ws && date <= we
}

// ── Schedule blocks per column ────────────────────────────────────────────────
// Returns schedules active on a given weekday index (0=Mon) — current user only, one entry
function schedulesForColumn(colIndex) {
  const colDate  = weekDays.value[colIndex]
  const dayName  = ALL_DAYS[colIndex]
  const userName = auth.currentUser?.name ?? ''
  return store.schedules.filter(s => {
    if (s.employeeName !== userName) return false
    if (!(s.days ?? []).includes(dayName)) return false
    if (s.effectiveDate) {
      const eff = new Date(s.effectiveDate); eff.setHours(0, 0, 0, 0)
      if (colDate < eff) return false
    }
    if (s.endDate) {
      const end = new Date(s.endDate); end.setHours(23, 59, 59, 999)
      if (colDate > end) return false
    }
    return true
  })
}

// Compute top/height for a block in the time grid
function blockStyle(s) {
  const sh = SHIFT_HOURS[s.shift] || SHIFT_HOURS.Flexible
  const palette = SHIFT_BLOCK_STYLE[s.shift] || SHIFT_BLOCK_STYLE.Flexible

  const startOffset = Math.max(sh.startH - GRID_START_HOUR, 0)
  const endClamped  = Math.min(sh.endH, GRID_START_HOUR + TIME_ROWS.length)
  const duration    = Math.max(endClamped - sh.startH, 1)

  return {
    top:    `${startOffset * HOUR_PX}px`,
    height: `${duration * HOUR_PX - 4}px`,
    background: palette.bg,
    borderLeft: `3px solid ${palette.border}`,
    color: palette.text,
  }
}

// ── Calendar view mode: week | month ─────────────────────────────────────────
const calView = ref('week') // 'week' | 'month'

// Month view — all days in the current month
const monthDays = computed(() => {
  const anchor = calendarAnchor.value
  const year   = anchor.getFullYear()
  const month  = anchor.getMonth()
  const first  = new Date(year, month, 1)
  const last   = new Date(year, month + 1, 0)

  // Pad to Monday-start
  let startDow = first.getDay()
  startDow = startDow === 0 ? 6 : startDow - 1

  const cells = []
  for (let i = 0; i < startDow; i++) cells.push(null)
  for (let d = 1; d <= last.getDate(); d++) cells.push(new Date(year, month, d))
  while (cells.length % 7 !== 0) cells.push(null)
  return cells
})

const monthLabel = computed(() => {
  const anchor = calendarAnchor.value
  return anchor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

function prevMonth() {
  const d = new Date(calendarAnchor.value)
  d.setDate(1)
  d.setMonth(d.getMonth() - 1)
  calendarAnchor.value = d
}
function nextMonth() {
  const d = new Date(calendarAnchor.value)
  d.setDate(1)
  d.setMonth(d.getMonth() + 1)
  calendarAnchor.value = d
}

// Schedules for a specific calendar date (month view) — current user only
function schedulesForDate(date) {
  if (!date) return []
  const dayName  = ALL_DAYS[date.getDay() === 0 ? 6 : date.getDay() - 1]
  const userName = auth.currentUser?.name ?? ''
  return store.schedules.filter(s => {
    if (s.employeeName !== userName) return false
    if (!(s.days ?? []).includes(dayName)) return false
    if (s.effectiveDate) {
      const eff = new Date(s.effectiveDate); eff.setHours(0,0,0,0)
      if (date < eff) return false
    }
    if (s.endDate) {
      const end = new Date(s.endDate); end.setHours(23,59,59,999)
      if (date > end) return false
    }
    return true
  })
}

// Navigate prev/next depending on current view
function prevPeriod() { calView.value === 'week' ? prevWeek() : prevMonth() }
function nextPeriod() { calView.value === 'week' ? nextWeek() : nextMonth() }

const periodLabel = computed(() => calView.value === 'week' ? weekLabel.value : monthLabel.value)

// ── Form month calendar (specific date selection) ─────────────────────────────
const formCalMonth = ref(new Date())

const formCalGrid = computed(() => {
  const year  = formCalMonth.value.getFullYear()
  const month = formCalMonth.value.getMonth()
  const first = new Date(year, month, 1)
  const last  = new Date(year, month + 1, 0)
  let startDow = first.getDay()
  startDow = startDow === 0 ? 6 : startDow - 1
  const cells = []
  for (let i = 0; i < startDow; i++) cells.push(null)
  for (let d = 1; d <= last.getDate(); d++) cells.push(new Date(year, month, d))
  while (cells.length % 7 !== 0) cells.push(null)
  return cells
})

const formCalLabel = computed(() =>
  formCalMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
)

function formCalPrev() {
  const d = new Date(formCalMonth.value)
  d.setDate(1); d.setMonth(d.getMonth() - 1)
  formCalMonth.value = d
}
function formCalNext() {
  const d = new Date(formCalMonth.value)
  d.setDate(1); d.setMonth(d.getMonth() + 1)
  formCalMonth.value = d
}

function toDateKey(date) { return date.toISOString().split('T')[0] }

function isDateSelected(date) {
  return (form.value.selectedDates ?? []).includes(toDateKey(date))
}

function toggleFormDate(date) {
  if (!date) return
  const key = toDateKey(date)
  if (!form.value.selectedDates) form.value.selectedDates = []
  const idx = form.value.selectedDates.indexOf(key)
  if (idx === -1) form.value.selectedDates.push(key)
  else form.value.selectedDates.splice(idx, 1)
}

function clearFormDates() { form.value.selectedDates = [] }

function selectAllMonth() {
  const year  = formCalMonth.value.getFullYear()
  const month = formCalMonth.value.getMonth()
  const last  = new Date(year, month + 1, 0).getDate()
  if (!form.value.selectedDates) form.value.selectedDates = []
  for (let d = 1; d <= last; d++) {
    const key = toDateKey(new Date(year, month, d))
    if (!form.value.selectedDates.includes(key)) form.value.selectedDates.push(key)
  }
}

// ── Print Functions ──────────────────────────────────────────────────────────
function printIndividual(schedule) {
  printIndividualSchedule(schedule, {
    title: 'Employee Schedule',
    includeLegend: true
  })
}

function printDepartment() {
  const dept = filterDept.value || 'All Departments'
  printDepartmentSchedule(filtered.value, {
    title: 'Department Schedule',
    department: dept,
    dateRange: '',
    includeLegend: true
  })
}

function printTransmittal() {
  // Group schedules by department for transmittal
  const deptGroups = {}
  filtered.value.forEach(schedule => {
    const dept = schedule.department || 'Unknown'
    if (!deptGroups[dept]) {
      deptGroups[dept] = {
        department: dept,
        staffCount: 0,
        submittedCount: 0,
        dateSubmitted: null,
        remarks: ''
      }
    }
    deptGroups[dept].staffCount++
    if (schedule.status === 'Submitted') {
      deptGroups[dept].submittedCount++
      if (!deptGroups[dept].dateSubmitted && schedule.submittedDate) {
        deptGroups[dept].dateSubmitted = schedule.submittedDate
      }
    }
  })

  const departments = Object.values(deptGroups)
  printTransmittalReport(departments, {
    title: 'Schedule Transmittal Report',
    periodStart: '',
    periodEnd: ''
  })
}

// ── Monitoring Dashboard ─────────────────────────────────────────────────────
const showMonitoring = ref(false)
const monitoringFilters = ref({})

function toggleMonitoring() {
  showMonitoring.value = !showMonitoring.value
}

function onMonitoringFilterChanged(filters) {
  monitoringFilters.value = filters
}

function onScheduleSelected(schedule) {
  openEdit(schedule)
}
</script>

<template>
  <div class="page">

    <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
        <AppSelect
          v-model="filterDept"
          :options="[{ label: 'All Departments', value: '' }, ...empStore.departments.map(d => ({ label: d, value: d }))]"
          placeholder="All Departments"
        />
        <AppSelect
          v-model="filterShift"
          :options="[{ label: 'All Shifts', value: '' }, ...store.shifts.map(s => ({ label: s, value: s }))]"
          placeholder="All Shifts"
        />
      </div>
      <div class="toolbar-right">
        <button class="btn btn-secondary" @click="toggleMonitoring">
          📊 {{ showMonitoring ? 'Hide' : 'Show' }} Monitoring
        </button>
        <div class="btn-group">
          <button class="btn btn-secondary" @click="printDepartment">
            🖨 Print Department
          </button>
          <button class="btn btn-secondary" @click="printTransmittal">
            📋 Print Transmittal
          </button>
        </div>
        <button v-if="hasPermission('Schedule Database', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Schedule
        </button>
      </div>
    </div>

    <!-- ── Calendar View ─────────────────────────────────────────────────── -->
    <div class="cal-layout">

      <!-- LEFT PANEL -->
      <div class="cal-left-panel">

        <!-- Mini month calendar -->
        <div class="mini-cal">
          <div class="mini-cal-header">{{ miniMonth.monthName }}</div>
          <div class="mini-cal-grid">
            <div v-for="h in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="h" class="mini-cal-dow">{{ h }}</div>
            <template v-for="(cell, idx) in miniMonth.cells" :key="idx">
              <div
                v-if="cell"
                class="mini-cal-day"
                :class="{
                  'mini-today': isToday(cell),
                  'mini-in-week': isMiniSelected(cell),
                }"
                @click="miniCalClick(cell)"
              >{{ cell.getDate() }}</div>
              <div v-else class="mini-cal-day empty"></div>
            </template>
          </div>
        </div>

        <!-- Shift legend -->
        <div class="cal-legend">
          <div class="cal-legend-title">Schedules</div>
          <div v-for="shift in ['Morning','Afternoon','Night','Split','Flexible']" :key="shift" class="cal-legend-item">
            <span class="cal-legend-dot" :style="{ background: SHIFT_DOT_COLOR[shift] }"></span>
            <span class="cal-legend-label">{{ shift }}</span>
          </div>
        </div>

      </div>

      <!-- RIGHT PANEL -->
      <div class="cal-right-panel">

        <!-- Week/Month header -->
        <div class="cal-week-header">
          <div class="cal-week-nav">
            <button class="cal-nav-btn" @click="prevPeriod">&#8249;</button>
            <span class="cal-week-label">{{ periodLabel }}</span>
            <button class="cal-nav-btn" @click="nextPeriod">&#8250;</button>
          </div>
          <!-- Week / Month toggle -->
          <div class="view-toggle">
            <button class="view-pill" :class="{ active: calView === 'week' }" @click="calView = 'week'">Week</button>
            <button class="view-pill" :class="{ active: calView === 'month' }" @click="calView = 'month'">Month</button>
          </div>
          <button v-if="hasPermission('Schedule Database', 'Add')" class="btn btn-primary cal-add-btn" @click="openAdd">
            <span class="icon-svg" v-html="svgIcons.add"></span> Add
          </button>
        </div>

        <!-- ── WEEK VIEW ── -->
        <template v-if="calView === 'week'">
          <!-- Column headers (Mon–Sun) -->
          <div class="cal-col-headers">
            <div class="cal-time-gutter-head"></div>
            <div
              v-for="(day, i) in weekDays"
              :key="i"
              class="cal-col-head"
              :class="{ 'cal-col-today': isToday(day) }"
            >
              <span class="cal-col-dow">{{ ALL_DAYS[i] }}</span>
              <span class="cal-col-date">{{ day.getDate() }}</span>
            </div>
          </div>

          <!-- Time grid -->
          <div class="cal-grid-scroll">
            <div class="cal-grid-body">
              <!-- Time gutter -->
              <div class="cal-time-gutter">
                <div v-for="row in TIME_ROWS" :key="row.hour" class="cal-time-cell">
                  {{ row.label }}
                </div>
              </div>
              <!-- Day columns -->
              <div
                v-for="(day, colIdx) in weekDays"
                :key="colIdx"
                class="cal-day-col"
                :class="{ 'cal-day-today': isToday(day) }"
              >
                <div v-for="row in TIME_ROWS" :key="row.hour" class="cal-hour-line"></div>
                <div
                  v-for="s in schedulesForColumn(colIdx)"
                  :key="s.id"
                  v-if="hasPermission('Schedule Database', 'Edit')"
                  class="cal-block"
                  :style="blockStyle(s)"
                  @click="openEdit(s)"
                  :title="`${s.employeeName} · ${s.shiftTime}`"
                >
                  <span class="cal-block-name">{{ s.employeeName }}</span>
                  <span class="cal-block-time">{{ s.shiftTime }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ── MONTH VIEW ── -->
        <template v-else>
          <!-- Day-of-week headers -->
          <div class="cal-col-headers">
            <div v-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="d" class="cal-month-dow">{{ d }}</div>
          </div>
          <!-- Month grid -->
          <div class="cal-month-grid">
            <div
              v-for="(cell, idx) in monthDays"
              :key="idx"
              class="cal-month-cell"
              :class="{ 'cal-month-today': cell && isToday(cell), 'cal-month-empty': !cell }"
            >
              <span v-if="cell" class="cal-month-date">{{ cell.getDate() }}</span>
              <div v-if="cell" class="cal-month-blocks">
                <div
                  v-for="s in schedulesForDate(cell)"
                  :key="s.id"
                  v-if="hasPermission('Schedule Database', 'Edit')"
                  class="cal-month-block"
                  :style="{ background: SHIFT_BLOCK_STYLE[s.shift]?.bg, borderLeft: `3px solid ${SHIFT_BLOCK_STYLE[s.shift]?.border}`, color: SHIFT_BLOCK_STYLE[s.shift]?.text }"
                  @click="openEdit(s)"
                  :title="`${s.employeeName} · ${s.shift}`"
                >
                  {{ s.employeeName }}
                </div>
              </div>
            </div>
          </div>
        </template>

      </div><!-- end cal-right-panel -->
    </div><!-- end cal-layout -->

    <!-- ── Monitoring Dashboard ──────────────────────────────────────────── -->
    <Transition name="slide-down">
      <MonitoringDashboard
        v-if="showMonitoring"
        :schedules="filtered"
        :departments="empStore.departments"
        :shifts="store.shifts"
        :filters="monitoringFilters"
        @filter-changed="onMonitoringFilterChanged"
        @schedule-selected="onScheduleSelected"
        style="margin-top: 20px;"
      />
    </Transition>

    <!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit Schedule' : 'Add Schedule' }}</h3>
          <button class="close-btn" @click="showForm = false">
            <span class="icon-svg" v-html="svgIcons.close"></span>
          </button>
        </div>

        <div class="modal-body">
          <ScheduleForm
            v-model="form"
            :edit-mode="!!editId"
          />
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-primary" @click="save" :disabled="saving">
            <span class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Delete Confirmation Modal ─────────────────────────────────────── -->
  <Transition name="modal">
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDelete">
      <div class="del-modal">
        <div class="del-icon-wrap">
          <span class="del-icon" v-html="svgIcons.warn"></span>
        </div>
        <h3 class="del-title">Delete Schedule</h3>
        <p class="del-message">Are you sure you want to delete this schedule?</p>

        <div class="del-card">
          <div class="del-card-row">
            <span class="del-label">Employee</span>
            <span class="del-value">{{ deleteTarget?.employeeName }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Emp No.</span>
            <span class="del-value mono">{{ deleteTarget?.employeeNo }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Department</span>
            <span class="del-value">{{ deleteTarget?.department || '—' }}</span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Shift</span>
            <span class="del-value">
              <span class="badge" :class="shiftColor(deleteTarget?.shift)">{{ deleteTarget?.shift }}</span>
            </span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Days</span>
            <span class="del-value">
              <span
                v-for="d in ALL_DAYS" :key="d"
                class="day-chip"
                :class="{ active: (deleteTarget?.days ?? []).includes(d) }"
              >{{ d }}</span>
            </span>
          </div>
          <div class="del-card-row">
            <span class="del-label">Effective</span>
            <span class="del-value">{{ deleteTarget?.effectiveDate || '—' }}</span>
          </div>
        </div>

        <p class="del-warning">This action cannot be undone.</p>

        <div class="del-actions">
          <button class="btn btn-cancel" @click="cancelDelete">Cancel</button>
          <button class="btn btn-delete" @click="confirmDelete">
            <span class="icon-svg" v-html="svgIcons.delete"></span>
            Yes, Delete
          </button>
        </div>
      </div>
    </div>
  </Transition>
  <!-- ── Update Confirmation Modal ─────────────────────────────────────── -->
  <Transition name="modal">
    <div v-if="showConfirm" class="modal-overlay" @click.self="showConfirm = false">
      <div class="confirm-modal">
        <div class="confirm-icon-wrap">
          <span class="icon-svg lg" v-html="svgIcons.save"></span>
        </div>
        <h3 class="confirm-title">Save Changes</h3>
        <p class="confirm-msg">Are you sure you want to update this schedule?</p>
        <div class="confirm-card">
          <div class="part-avatar">{{ form.employeeName?.split(',')[1]?.trim()[0] }}{{ form.employeeName?.split(',')[0]?.trim()[0] }}</div>
          <div>
            <strong>{{ form.employeeName }}</strong>
            <span>{{ form.shift }} · {{ form.shiftTime }}</span>
            <span>{{ form.department }}</span>
          </div>
        </div>
        <p class="confirm-note">All changes will be saved to the database.</p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="showConfirm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-confirm-ok" @click="doSave" :disabled="saving">
            <span class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Yes, Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }

/* Toolbar */
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-icon { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:240px; outline:none; }
.record-count { font-size:13px; color:#888; }

/* Buttons */
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:background 0.2s; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-primary:hover:not(:disabled) { background:#2980b9; }
.btn-primary:disabled { background:#a0b4c8; cursor:not-allowed; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }

/* Table */
.table-wrapper { overflow-x:auto; overflow-y:auto; max-height:60vh; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); }
.data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:12px; }
.data-table thead tr { background:#1a3a5c; color:#fff; }
.data-table thead tr th { position:sticky; top:0; z-index:2; background:#1a3a5c; }
.data-table th { padding:11px 12px; text-align:left; font-weight:600; white-space:nowrap; }
.data-table td { padding:9px 12px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
.data-table tbody tr:hover { background:#f9fafb; }
.sub-text { font-size:11px; color:#888; }
.shift-time { font-size:11px; color:#555; white-space:nowrap; }
.rest-day { font-size:11px; color:#888; }
.days-row { display:flex; gap:3px; flex-wrap:wrap; }
.day-chip { padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#f0f4f8; color:#aaa; }
.day-chip.active { background:#1a3a5c; color:#fff; }
.badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-yellow { background:#fef9e7; color:#b7950b; }
.badge-orange { background:#fef3e2; color:#e67e22; }
.badge-blue { background:#ebf5fb; color:#2980b9; }
.badge-purple { background:#f5eef8; color:#8e44ad; }
.badge-gray { background:#f4f4f4; color:#666; }
.action-btns { display:flex; gap:4px; }
.btn-icon { background:none; border:none; cursor:pointer; padding:3px; border-radius:4px; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#f0f4f8; }
.btn-icon.danger:hover { background:#fdecea; }
.btn-icon:disabled { opacity:0.3; cursor:not-allowed; }
.approval-cell { display:flex; flex-direction:column; gap:4px; }
.approval-btns { display:flex; gap:4px; }
.btn-approve { background:#eafaf1; color:#27ae60; border:1px solid #27ae60; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-approve:hover { background:#27ae60; color:#fff; }
.btn-reject  { background:#fdecea; color:#e74c3c; border:1px solid #e74c3c; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-reject:hover  { background:#e74c3c; color:#fff; }
.approved-by { font-size:10px; color:#888; }
.empty-row { text-align:center; color:#aaa; padding:40px; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal { background:#fff; border-radius:12px; width:700px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f0f4f8; }
.modal-header h3 { margin:0; color:#1a3a5c; }
.close-btn { background:none; border:none; cursor:pointer; color:#888; display:inline-flex; align-items:center; padding:4px; border-radius:4px; }
.close-btn:hover { background:#f0f4f8; }
.modal-body { padding:20px; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #f0f4f8; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group.full { grid-column:1 / -1; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input, .form-group select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.readonly-input { background:#f8f9fa !important; color:#555; cursor:default; }

/* Days picker */
.days-picker { display:flex; gap:8px; flex-wrap:wrap; margin-top:4px; }
.days-count { font-size:11px; color:#888; font-weight:400; margin-left:6px; }
.day-toggle {
  display:flex; align-items:center; gap:6px;
  padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600;
  cursor:pointer; border:2px solid #ddd; background:#f9fafb;
  color:#888; transition:all 0.15s; user-select:none;
}
.day-toggle input[type="checkbox"] { display:none; }
.day-toggle.selected { background:#1a3a5c; border-color:#1a3a5c; color:#fff; }
.day-toggle.disabled { background:#f4f4f4; border-color:#eee; color:#ccc; cursor:not-allowed; opacity:0.6; }
.day-toggle:not(.selected):not(.disabled):hover { border-color:#1a3a5c; color:#1a3a5c; background:#e8f0fe; }

/* Employee combobox */
.emp-combobox { position:relative; }
.emp-search-input { width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; box-sizing:border-box; }
.emp-search-input:focus { border-color:#1a3a5c; }
.emp-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; max-height:220px; overflow-y:auto; }
.emp-option { display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; transition:background 0.15s; border-bottom:1px solid #f5f5f5; }
.emp-option:last-child { border-bottom:none; }
.emp-option:hover { background:#f0f9f4; }
.emp-opt-no { font-family:monospace; font-size:11px; color:#888; flex-shrink:0; min-width:90px; }
.emp-opt-name { font-size:13px; font-weight:600; color:#1a1a2e; flex:1; }
.emp-opt-dept { font-size:11px; color:#aaa; flex-shrink:0; }
.emp-option-empty { padding:12px; text-align:center; color:#aaa; font-size:13px; }

/* ── Delete modal ── */
.del-modal {
  background: #fff; border-radius: 16px; padding: 32px 28px 24px;
  width: 100%; max-width: 420px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  text-align: center;
}
.del-icon-wrap {
  width: 56px; height: 56px; border-radius: 50%;
  background: #fef3e2;
  display: flex; align-items: center; justify-content: center;
}
.del-icon { width: 28px; height: 28px; color: #e67e22; }
.del-icon :deep(svg) { width: 28px; height: 28px; fill: #e67e22; }
.del-title   { margin: 0; font-size: 18px; font-weight: 700; color: #1a1a2e; }
.del-message { margin: 0; font-size: 14px; color: #555; }
.del-warning { margin: 0; font-size: 12px; color: #e74c3c; font-weight: 600; }
.del-card {
  width: 100%; background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 10px; padding: 12px 16px;
  display: flex; flex-direction: column; gap: 8px; text-align: left;
}
.del-card-row { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.del-label { font-weight: 600; color: #888; font-size: 11px; min-width: 72px; }
.del-value { color: #1a1a2e; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.del-value.mono { font-family: monospace; font-size: 12px; }
.del-actions { display: flex; gap: 10px; width: 100%; margin-top: 4px; }
.btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #f0f4f8; color: #555; border: 1px solid #ddd;
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-cancel:hover { background: #e0e8f0; }
.btn-delete {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #e74c3c; color: #fff; border: none;
  font-size: 13px; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-delete:hover { background: #c0392b; }
.btn-delete .icon-svg :deep(svg) { fill: #fff; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .del-modal, .modal-leave-active .del-modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .del-modal, .modal-leave-to .del-modal { transform: scale(0.95); opacity: 0; }

/* ── Update Confirmation modal ── */
.confirm-modal {
  background:#fff; border-radius:16px; padding:32px 28px 24px;
  width:100%; max-width:420px;
  box-shadow:0 20px 60px rgba(0,0,0,0.2);
  display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center;
}
.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#e8f0fe; display:flex; align-items:center; justify-content:center; }
.icon-svg.lg { width:28px; height:28px; }
.icon-svg.lg :deep(svg) { width:28px; height:28px; fill:#1a3a5c; }
.confirm-title { margin:0; font-size:18px; font-weight:700; color:#1a1a2e; }
.confirm-msg { margin:0; font-size:14px; color:#555; }
.confirm-card { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; width:100%; text-align:left; }
.confirm-card .part-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.confirm-card strong { display:block; font-size:14px; color:#1a1a2e; }
.confirm-card span { display:block; font-size:12px; color:#888; }
.confirm-note { margin:0; font-size:12px; color:#1a3a5c; font-weight:600; }
.confirm-actions { display:flex; gap:10px; width:100%; margin-top:4px; }
.btn-confirm-ok { flex:1; padding:10px; border-radius:8px; background:#1a3a5c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; transition:background 0.2s; }
.btn-confirm-ok:hover:not(:disabled) { background:#2980b9; }
.btn-confirm-ok:disabled { background:#a0b4c8; cursor:not-allowed; }
.btn-confirm-ok .icon-svg :deep(svg) { fill:#fff; }

/* ── View toggle ── */
.view-toggle { display:flex; border:1px solid #ddd; border-radius:8px; overflow:hidden; }
.view-pill { padding:7px 16px; font-size:13px; font-weight:600; background:#fff; color:#888; border:none; cursor:pointer; transition:background 0.15s, color 0.15s; }
.view-pill:first-child { border-right:1px solid #ddd; }
.view-pill.active { background:#1a3a5c; color:#fff; }
.view-pill:not(.active):hover { background:#f0f4f8; color:#1a3a5c; }

/* ── Calendar layout ── */
.cal-layout { display:flex; gap:16px; align-items:flex-start; min-height:600px; }

/* Left panel */
.cal-left-panel { width:220px; flex-shrink:0; display:flex; flex-direction:column; gap:16px; }

/* Mini calendar */
.mini-cal { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:14px; }
.mini-cal-header { font-size:13px; font-weight:700; color:#1a3a5c; text-align:center; margin-bottom:10px; }
.mini-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
.mini-cal-dow { font-size:10px; font-weight:700; color:#aaa; text-align:center; padding:2px 0 4px; }
.mini-cal-day { font-size:11px; text-align:center; padding:4px 2px; border-radius:50%; cursor:pointer; color:#444; transition:background 0.12s; aspect-ratio:1; display:flex; align-items:center; justify-content:center; }
.mini-cal-day:hover:not(.empty) { background:#e8f0fe; color:#1a3a5c; }
.mini-cal-day.empty { cursor:default; }
.mini-cal-day.mini-today { background:#1a3a5c; color:#fff; font-weight:700; }
.mini-cal-day.mini-in-week:not(.mini-today) { background:#f0c040; color:#1a3a5c; font-weight:700; }

/* Legend */
.cal-legend { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:14px; }
.cal-legend-title { font-size:12px; font-weight:700; color:#1a3a5c; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.5px; }
.cal-legend-item { display:flex; align-items:center; gap:8px; padding:4px 0; }
.cal-legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.cal-legend-label { font-size:12px; color:#555; }

/* Right panel */
.cal-right-panel { flex:1; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow:hidden; display:flex; flex-direction:column; }

/* Week header */
.cal-week-header { display:flex; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid #f0f4f8; flex-wrap:wrap; }
.cal-week-nav { display:flex; align-items:center; gap:8px; }
.cal-nav-btn { background:none; border:1px solid #ddd; border-radius:6px; width:28px; height:28px; font-size:18px; line-height:1; cursor:pointer; color:#555; display:flex; align-items:center; justify-content:center; transition:background 0.15s; }
.cal-nav-btn:hover { background:#f0f4f8; }
.cal-week-label { font-size:14px; font-weight:700; color:#1a3a5c; white-space:nowrap; }
.cal-week-tag { font-size:12px; font-weight:600; color:#888; background:#f0f4f8; padding:3px 10px; border-radius:12px; }
.cal-add-btn { margin-left:auto; padding:7px 14px; font-size:12px; }

/* Column headers */
.cal-col-headers { display:flex; border-bottom:1px solid #f0f4f8; }
.cal-time-gutter-head { width:72px; flex-shrink:0; }
.cal-col-head { flex:1; text-align:center; padding:8px 4px; display:flex; flex-direction:column; align-items:center; gap:2px; border-left:1px solid #f0f4f8; }
.cal-col-dow { font-size:11px; font-weight:600; color:#888; text-transform:uppercase; }
.cal-col-date { font-size:16px; font-weight:700; color:#1a3a5c; width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:50%; }
.cal-col-today .cal-col-dow { color:#fff; }
.cal-col-today .cal-col-date { background:#1a3a5c; color:#fff; }

/* Grid scroll area */
.cal-grid-scroll { overflow-y:auto; max-height:calc(100vh - 280px); }
.cal-grid-body { display:flex; position:relative; }

/* Time gutter */
.cal-time-gutter { width:72px; flex-shrink:0; }
.cal-time-cell { height:60px; display:flex; align-items:flex-start; justify-content:flex-end; padding:4px 8px 0 0; font-size:10px; color:#aaa; white-space:nowrap; box-sizing:border-box; border-bottom:1px solid #f5f5f5; }

/* Day columns */
.cal-day-col { flex:1; position:relative; border-left:1px solid #f0f4f8; min-width:0; }
.cal-day-today { background:#f8fbff; }
.cal-hour-line { height:60px; border-bottom:1px solid #f5f5f5; box-sizing:border-box; }

/* Schedule blocks */
.cal-block {
  position:absolute; left:3px; right:3px;
  border-radius:6px; padding:4px 6px;
  cursor:pointer; overflow:hidden;
  display:flex; flex-direction:column; gap:1px;
  transition:filter 0.15s, box-shadow 0.15s;
  z-index:1;
}
.cal-block:hover { filter:brightness(0.95); box-shadow:0 2px 8px rgba(0,0,0,0.15); z-index:2; }
.cal-block-name { font-size:11px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cal-block-time { font-size:10px; opacity:0.8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── Month view ── */
.cal-month-dow { flex:1; text-align:center; font-size:11px; font-weight:700; color:#888; text-transform:uppercase; padding:8px 4px; border-left:1px solid #f0f4f8; }
.cal-month-dow:first-child { border-left:none; }
.cal-col-headers { display:flex; border-bottom:1px solid #f0f4f8; }
.cal-month-grid { display:grid; grid-template-columns:repeat(7,1fr); flex:1; overflow-y:auto; }
.cal-month-cell {
  min-height:110px; border-right:1px solid #f0f4f8; border-bottom:1px solid #f0f4f8;
  padding:6px; display:flex; flex-direction:column; gap:3px; background:#fff;
}
.cal-month-cell:nth-child(7n) { border-right:none; }
.cal-month-empty { background:#fafbfc; }
.cal-month-today { background:#f0f7ff; }
.cal-month-today .cal-month-date {
  background:#1a3a5c; color:#fff; border-radius:50%;
  width:22px; height:22px; display:flex; align-items:center; justify-content:center;
}
.cal-month-date { font-size:12px; font-weight:700; color:#1a3a5c; margin-bottom:2px; }
.cal-month-blocks { display:flex; flex-direction:column; gap:2px; overflow:hidden; }
.cal-month-block {
  font-size:10px; font-weight:600; padding:2px 5px; border-radius:4px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  cursor:pointer; transition:filter 0.12s;
}
.cal-month-block:hover { filter:brightness(0.92); }

/* ── Form month calendar ── */
.form-cal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; flex-wrap:wrap; gap:6px; }
.form-cal-actions { display:flex; gap:5px; }
.fcal-action-btn {
  padding:3px 9px; border-radius:5px; border:1px solid #ddd;
  background:#f0f4f8; color:#1a3a5c; font-size:10px; font-weight:600;
  cursor:pointer; transition:background 0.15s;
}
.fcal-action-btn:hover:not(:disabled) { background:#e0e8f0; }
.fcal-action-btn.danger { color:#c0392b; border-color:#f5b7b1; background:#fdecea; }
.fcal-action-btn.danger:hover:not(:disabled) { background:#fad4d1; }
.fcal-action-btn:disabled { opacity:0.4; cursor:not-allowed; }

.form-cal { background:#f8f9fc; border:1px solid #e2e6ef; border-radius:8px; padding:8px 10px; max-width:280px; }
.form-cal-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.form-cal-month-label { font-size:12px; font-weight:700; color:#1a3a5c; }
.form-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
.form-cal-dow { font-size:9px; font-weight:700; color:#aaa; text-align:center; padding:2px 0 3px; }
.form-cal-day {
  width:28px; height:28px; display:flex; align-items:center; justify-content:center;
  font-size:11px; font-weight:500; border-radius:5px; cursor:pointer;
  color:#444; transition:background 0.12s, color 0.12s;
  border:1.5px solid transparent;
}
.form-cal-day:hover:not(.fcal-empty) { background:#e8f0fe; color:#1a3a5c; }
.fcal-empty { cursor:default; }
.fcal-today { border-color:#1a3a5c; color:#1a3a5c; font-weight:700; }
.fcal-weekend { color:#999; }
.fcal-selected { background:#1a3a5c !important; color:#fff !important; border-color:#1a3a5c; font-weight:700; }
.fcal-selected-count { margin-top:6px; font-size:10px; color:#1a6b3c; font-weight:600; text-align:center; }

/* ── Slide down transition ── */
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from { opacity: 0; transform: translateY(-20px); }
.slide-down-leave-to { opacity: 0; transform: translateY(-20px); }

/* ── Button group ── */
.btn-group { display: flex; gap: 8px; }
</style>
