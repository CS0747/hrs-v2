<template>
  <div class="monitoring-dashboard">
    <div class="dashboard-header">
      <h3>Schedule Monitoring</h3>
      <div class="filter-bar">
        <input
          v-model="localFilters.search"
          @input="emitFilters"
          type="text"
          placeholder="Search employee or department..."
          class="search-input"
        />
        <button v-if="hasActiveFilters" class="btn-clear" @click="clearFilters">
          Clear
        </button>
      </div>
    </div>

    <!-- Department Groups -->
    <div class="departments-container">
      <div 
        v-for="(employees, department) in groupedEmployees" 
        :key="department"
        class="department-section"
      >
        <div class="department-header">
          <div class="dept-info">
            <h4>{{ department }}</h4>
            <span class="emp-count">{{ employees.length }} employee{{ employees.length !== 1 ? 's' : '' }}</span>
          </div>
          <div class="dept-actions">
            <button class="btn-preview-dept" @click="previewDepartment(department, employees)">
              👁 Preview
            </button>
            <button class="btn-print-dept" @click="printDepartment(department, employees)">
              🖨 Print
            </button>
          </div>
        </div>

        <table class="emp-table">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th>Employee Name</th>
              <th style="width: 200px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(emp, index) in employees" :key="emp.employeeNo">
              <td>{{ index + 1 }}</td>
              <td>
                <div class="emp-name-cell">
                  <span class="emp-name">{{ emp.employeeName }}</span>
                  <span class="emp-no">{{ emp.employeeNo }}</span>
                </div>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="btn-action view" @click="viewSchedule(emp)">
                    👁 View
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="Object.keys(groupedEmployees).length === 0" class="empty-state">
      <div class="empty-icon">📅</div>
      <p class="empty-message">No employees found</p>
      <p class="empty-hint">Try adjusting your search</p>
    </div>

    <!-- View Schedule Modal -->
    <div v-if="showScheduleModal" class="modal-overlay" @click.self="showScheduleModal = false">
      <div class="schedule-modal">
        <div class="modal-header">
          <div>
            <h3>{{ selectedEmployee?.employeeName }}</h3>
            <p class="emp-details">{{ selectedEmployee?.employeeNo }} • {{ selectedEmployee?.department }}</p>
          </div>
          <button class="close-btn" @click="showScheduleModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="schedule-grid">
            <div 
              v-for="schedule in employeeSchedules" 
              :key="schedule.id"
              class="schedule-card"
            >
              <div class="schedule-date">{{ formatDate(schedule.scheduleDate) }}</div>
              <div class="schedule-shift">
                <span 
                  v-if="schedule.shiftCode === 'OFF' || schedule.shiftCode === 'O'"
                  class="shift-badge off-badge"
                >
                  <div class="off-circle"></div>
                </span>
                <span 
                  v-else-if="schedule.shiftCode === '610'"
                  class="shift-badge multi-color"
                >
                  <span style="color: #2196F3; font-weight: bold;">6</span><span style="color: #4CAF50; font-weight: bold;">10</span>
                </span>
                <span 
                  v-else-if="schedule.shiftCode === '26'"
                  class="shift-badge multi-color"
                >
                  <span style="color: #4CAF50; font-weight: bold;">2</span><span style="color: #F44336; font-weight: bold;">6</span>
                </span>
                <span 
                  v-else
                  class="shift-badge" 
                  :style="{ background: getShiftColor(schedule.shiftCode, schedule.department) }"
                >
                  {{ schedule.shiftCode }}
                </span>
                <span class="shift-time">{{ schedule.startTime }} - {{ schedule.endTime }}</span>
              </div>
              <div v-if="schedule.remarks" class="schedule-remarks">{{ schedule.remarks }}</div>
            </div>
          </div>
          <div v-if="employeeSchedules.length === 0" class="no-schedules">
            No schedules found for this employee
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreviewModal" class="modal-overlay preview-overlay" @click.self="showPreviewModal = false">
      <div class="preview-modal">
        <div class="modal-header">
          <h3>Print Preview - {{ previewData.department }}</h3>
          <div class="preview-actions">
            <button class="btn-print" @click="printPreview">🖨 Print</button>
            <button class="close-btn" @click="showPreviewModal = false">×</button>
          </div>
        </div>
        <div class="preview-body" v-html="previewData.html"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useLegendStore } from '@/stores/legend'
import { useAuthStore } from '@/stores/auth'
import { useScheduleStore } from '@/stores/schedule'
import { useEmployeeStore } from '@/stores/employees'

const props = defineProps({
  schedules: {
    type: Array,
    required: true
  },
  departments: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['schedule-selected'])

const legendStore = useLegendStore()
const auth = useAuthStore()
const scheduleStore = useScheduleStore()
const empStore = useEmployeeStore()

// Refresh data on mount to ensure latest information
onMounted(async () => {
  // Refresh current user session to get latest position/department
  await auth.refreshCurrentUser()
  
  await Promise.all([
    scheduleStore.fetchSchedules(),
    empStore.fetchEmployees(),
    empStore.fetchDepartments(),
    legendStore.fetchLegends()
  ])
})

// Local filters
const localFilters = ref({
  search: ''
})

// Modal state
const showScheduleModal = ref(false)
const selectedEmployee = ref(null)
const showPreviewModal = ref(false)
const previewData = ref({ department: '', html: '' })

// Computed: Unique employees from schedules (filtered by user's department)
const allEmployees = computed(() => {
  const empMap = new Map()
  const userDepartment = auth.currentUser?.department
  const isSuperAdmin = auth.currentUser?.role === 'Super Admin'
  
  props.schedules.forEach(schedule => {
    // Super Admin can see all departments, others only see their own department
    if (!isSuperAdmin && schedule.department !== userDepartment) {
      return
    }
    
    if (!empMap.has(schedule.employeeNo)) {
      empMap.set(schedule.employeeNo, {
        employeeNo: schedule.employeeNo,
        employeeName: schedule.employeeName,
        department: schedule.department || 'No Department'
      })
    }
  })
  return Array.from(empMap.values())
})

// Computed: Group employees by department
const groupedEmployees = computed(() => {
  let employees = allEmployees.value

  // Apply search filter
  if (localFilters.value.search) {
    const q = localFilters.value.search.toLowerCase()
    employees = employees.filter(e =>
      e.employeeName.toLowerCase().includes(q) ||
      e.employeeNo.toLowerCase().includes(q) ||
      e.department.toLowerCase().includes(q)
    )
  }

  // Group by department
  const grouped = {}
  employees.forEach(emp => {
    const dept = emp.department
    if (!grouped[dept]) {
      grouped[dept] = []
    }
    grouped[dept].push(emp)
  })

  // Sort departments and employees within each department
  const sorted = {}
  Object.keys(grouped).sort().forEach(dept => {
    sorted[dept] = grouped[dept].sort((a, b) => a.employeeName.localeCompare(b.employeeName))
  })

  return sorted
})

// Computed: Employee schedules for modal (filtered by user's department)
const employeeSchedules = computed(() => {
  if (!selectedEmployee.value) return []
  
  const userDepartment = auth.currentUser?.department
  const isSuperAdmin = auth.currentUser?.role === 'Super Admin'
  
  let schedules = props.schedules.filter(s => s.employeeNo === selectedEmployee.value.employeeNo)
  
  // Apply department filter for non-Super Admin users
  if (!isSuperAdmin) {
    schedules = schedules.filter(s => s.department === userDepartment)
  }
  
  return schedules.sort((a, b) => new Date(a.scheduleDate) - new Date(b.scheduleDate))
})

const hasActiveFilters = computed(() => localFilters.value.search)

// Methods
function viewSchedule(emp) {
  selectedEmployee.value = emp
  showScheduleModal.value = true
}

function generateDepartmentHTML(department, employees) {
  const userDepartment = auth.currentUser?.department
  const isSuperAdmin = auth.currentUser?.role === 'Super Admin'
  
  // Filter schedules by user's department (unless Super Admin)
  let deptSchedules = props.schedules.filter(s => 
    employees.some(emp => emp.employeeNo === s.employeeNo)
  )
  
  // Apply department filter for non-Super Admin users
  if (!isSuperAdmin) {
    deptSchedules = deptSchedules.filter(s => s.department === userDepartment)
  }

  if (deptSchedules.length === 0) {
    return null
  }

  // Get logged-in user info for "Prepared by" signatory
  const currentUser = JSON.parse(sessionStorage.getItem('hris_user') || '{}')
  
  // Format name: Convert "Last Name, First Name" to "FIRST NAME LAST NAME"
  let preparedBy = currentUser.name || 'Unknown User'
  if (preparedBy.includes(',')) {
    const [lastName, firstName] = preparedBy.split(',').map(s => s.trim())
    preparedBy = `${firstName} ${lastName}`.toUpperCase()
  } else {
    preparedBy = preparedBy.toUpperCase()
  }
  
  const preparedByPosition = currentUser.position || 'Position Not Set'

  // Group schedules by month
  const byMonth = {}
  deptSchedules.forEach(schedule => {
    const date = new Date(schedule.scheduleDate)
    const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    if (!byMonth[monthKey]) {
      byMonth[monthKey] = []
    }
    byMonth[monthKey].push(schedule)
  })

  // Get the most recent month only (to avoid multiple pages)
  const sortedMonths = Object.keys(byMonth).sort().reverse()
  const latestMonth = sortedMonths[0]
  const monthSchedules = byMonth[latestMonth]
  
  const [year, month] = latestMonth.split('-')
  const monthName = new Date(year, parseInt(month) - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
  
  // Build calendar grid
  const firstDay = new Date(year, parseInt(month) - 1, 1)
  const lastDay = new Date(year, parseInt(month), 0)
  const daysInMonth = lastDay.getDate()
  
  // Build table rows for each employee
  let tableRows = ''
  employees.forEach(emp => {
    const empSchedules = monthSchedules.filter(s => s.employeeNo === emp.employeeNo)
    
    // Create schedule map by date
    const scheduleMap = {}
    empSchedules.forEach(s => {
      const day = new Date(s.scheduleDate).getDate()
      scheduleMap[day] = s
    })
    
    // Count working days
    let workingDays = 0
    for (let day = 1; day <= daysInMonth; day++) {
      const schedule = scheduleMap[day]
      if (schedule && schedule.shiftCode && schedule.shiftCode !== 'O' && schedule.shiftCode !== 'OFF') {
        workingDays++
      }
    }
    
    tableRows += `<tr><td style="padding:8px; border:1px solid #000; font-weight:bold; text-align:left;">${emp.employeeName}</td>`
    
    for (let day = 1; day <= 31; day++) {
      if (day <= daysInMonth) {
        const schedule = scheduleMap[day]
        const shiftCode = schedule ? (schedule.shiftCode || 'O') : 'O'
        
        // Handle OFF as simple circle outline
        if (shiftCode === 'O' || shiftCode === 'OFF') {
          tableRows += `<td style="padding:8px; border:1px solid #000; text-align:center;">
            <div style="width:18px; height:18px; border:2px solid #F44336; border-radius:50%; display:inline-block;"></div>
          </td>`
        } else if (shiftCode === 'H') {
          // H: Holiday (Red)
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold; color:#F44336; background:#FFE5E5;">${shiftCode}</td>`
        } else if (shiftCode === '610') {
          // 610: 6=Blue, 10=Green
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold;"><span style="color:#2196F3;">6</span><span style="color:#4CAF50;">10</span></td>`
        } else if (shiftCode === '26') {
          // 26: 2=Green, 6=Red
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold;"><span style="color:#4CAF50;">2</span><span style="color:#F44336;">6</span></td>`
        } else if (shiftCode === '62') {
          // 62: Blue
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold; color:#2196F3;">${shiftCode}</td>`
        } else if (shiftCode === '210') {
          // 210: Green
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold; color:#4CAF50;">${shiftCode}</td>`
        } else if (shiftCode === '106') {
          // 106: Red
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold; color:#F44336;">${shiftCode}</td>`
        } else if (shiftCode === '85') {
          // 85: Black
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold; color:#000000;">${shiftCode}</td>`
        } else {
          // Other codes: default color
          tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-size:10pt; font-weight:bold;">${shiftCode}</td>`
        }
      } else {
        tableRows += `<td style="padding:4px; border:1px solid #000;"></td>`
      }
    }
    
    tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; font-weight:bold;">${workingDays}</td></tr>`
  })
  
  // Get day names for the month
  const dayNames = []
  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(year, parseInt(month) - 1, day)
    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()
    dayNames.push(dayName)
  }
  
  const html = `
    <div class="print-page">
      <div class="header">
        <h2>GENERAL EMILIO AGUINALDO MEMORIAL HOSPITAL</h2>
        <h3>Korea Philippines Friendship Hospital</h3>
        <h3>Trece Martires City, Cavite</h3>
        <h3>${department}</h3>
        <h3>${monthName.toUpperCase()}</h3>
      </div>
      <table>
        <thead>
          <tr>
            <th rowspan="2" style="min-width:150px;">NAME OF EMPLOYEE</th>
            <th colspan="31">DAYS</th>
            <th rowspan="2">NO. OF<br>DAYS</th>
          </tr>
          <tr>
            ${Array.from({length: 31}, (_, i) => `<th>${i + 1}</th>`).join('')}
          </tr>
          <tr>
            <th></th>
            ${dayNames.map(d => `<th style="font-size:7pt;">${d}</th>`).join('')}
            ${Array.from({length: 31 - daysInMonth}, () => '<th></th>').join('')}
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${tableRows}
        </tbody>
      </table>
      <div class="legend">
        <strong>LEGEND:</strong>
        <span class="legend-item">
          <div style="width:14px; height:14px; border:2px solid #F44336; border-radius:50%; display:inline-block; vertical-align:middle; margin-right:6px;"></div>
          <strong>OFF DUTY</strong>
        </span>
        <span class="legend-item"><strong style="color:#F44336;">H</strong> - HOLIDAY (Red)</span>
        <span class="legend-item"><strong style="color:#000000;">85</strong> - 8:00 AM TO 5:00 PM (Black)</span>
        ${department.toLowerCase().includes('nursing') || department.toLowerCase().includes('medical') || department.toLowerCase().includes('med') ? `
        <span class="legend-item"><strong style="color:#2196F3;">62</strong> - 6:00 AM TO 2:00 PM (Blue)</span>
        <span class="legend-item"><strong style="color:#4CAF50;">210</strong> - 2:00 PM TO 10:00 PM (Green)</span>
        <span class="legend-item"><strong style="color:#F44336;">106</strong> - 10:00 PM TO 6:00 AM (Red)</span>
        <span class="legend-item"><strong><span style="color:#2196F3;">6</span><span style="color:#4CAF50;">10</span></strong> - 6:00 AM TO 10:00 PM (6=Blue, 10=Green)</span>
        <span class="legend-item"><strong><span style="color:#4CAF50;">2</span><span style="color:#F44336;">6</span></strong> - 2:00 PM TO 6:00 AM (2=Green, 6=Red)</span>
        ` : ''}
      </div>
      <div class="signatures">
        <div class="sig-block">
          <div>Prepared by:</div>
          <div class="sig-line"></div>
          <div><strong>${preparedBy}</strong></div>
          <div>${preparedByPosition}</div>
        </div>
        <div class="sig-block">
          <div>Approved by:</div>
          <div class="sig-line"></div>
          <div><strong>ALLAN MIRANDILLA</strong></div>
          <div>Administrative Officer 1</div>
          <div>OIC-Personnel Section</div>
        </div>
        <div class="sig-block">
          <div>Noted by:</div>
          <div class="sig-line"></div>
          <div><strong>NONIE JOHN L. DALISAY, MD, FPOGS, MBA</strong></div>
          <div>Provincial Health Officer II</div>
        </div>
      </div>
    </div>
  `
  
  return html
}

function previewDepartment(department, employees) {
  const html = generateDepartmentHTML(department, employees)
  
  if (!html) {
    alert('No schedules found for this department')
    return
  }
  
  previewData.value = {
    department,
    html: `
      <style>
        .print-page { font-family: Arial, sans-serif; font-size: 9pt; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 3px 0; font-size: 12pt; }
        .header h3 { margin: 3px 0; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a3a5c; color: #fff; padding: 4px; border: 1px solid #000; font-size: 8pt; }
        td { padding: 3px; border: 1px solid #000; text-align: center; font-size: 8pt; }
        .legend { margin-top: 15px; font-size: 8pt; }
        .legend-item { display: inline-block; margin-right: 12px; }
        .signatures { margin-top: 30px; display: flex; justify-content: space-around; font-size: 9pt; }
        .sig-block { text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 5px; min-width: 200px; }
      </style>
      ${html}
    `
  }
  
  showPreviewModal.value = true
}

function printDepartment(department, employees) {
  const html = generateDepartmentHTML(department, employees)
  
  if (!html) {
    alert('No schedules found for this department')
    return
  }
  
  const fullHTML = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Schedule - ${department}</title>
      <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 9pt; margin: 0; padding: 0; }
        .print-page { page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 3px 0; font-size: 12pt; }
        .header h3 { margin: 3px 0; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a3a5c; color: #fff; padding: 4px; border: 1px solid #000; font-size: 8pt; }
        td { padding: 3px; border: 1px solid #000; text-align: center; font-size: 8pt; }
        .legend { margin-top: 15px; font-size: 8pt; }
        .legend-item { display: inline-block; margin-right: 12px; }
        .signatures { margin-top: 30px; display: flex; justify-content: space-around; font-size: 9pt; }
        .sig-block { text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 5px; min-width: 200px; }
      </style>
    </head>
    <body>
      ${html}
    </body>
    </html>
  `
  
  const printWindow = window.open('', '_blank')
  printWindow.document.write(fullHTML)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => printWindow.print(), 250)
}

function printPreview() {
  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Schedule - ${previewData.value.department}</title>
      <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 9pt; margin: 0; padding: 0; }
        .print-page { page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 3px 0; font-size: 12pt; }
        .header h3 { margin: 3px 0; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a3a5c; color: #fff; padding: 4px; border: 1px solid #000; font-size: 8pt; }
        td { padding: 3px; border: 1px solid #000; text-align: center; font-size: 8pt; }
        .legend { margin-top: 15px; font-size: 8pt; }
        .legend-item { display: inline-block; margin-right: 12px; }
        .signatures { margin-top: 30px; display: flex; justify-content: space-around; font-size: 9pt; }
        .sig-block { text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 5px; min-width: 200px; }
      </style>
    </head>
    <body>
      ${previewData.value.html}
    </body>
    </html>
  `)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => printWindow.print(), 250)
}

function getShiftColor(shiftCode, department) {
  const colors = legendStore.getColorForShift(shiftCode, department)
  return colors.primary
}

function getShiftColorHex(shiftCode, department) {
  if (!shiftCode || shiftCode === 'O' || shiftCode === 'OFF') return '#fff'
  
  // Get color from legend store
  const colors = legendStore.getColorForShift(shiftCode, department)
  if (colors && colors.primary) {
    return colors.primary
  }
  
  // Fallback color map based on common shifts
  const colorMap = {
    '85': '#000000',    // Black (standard shift)
    '62': '#2196F3',    // Blue (morning)
    '210': '#4CAF50',   // Green (afternoon)
    '106': '#F44336',   // Red (night)
    '610': '#2196F3',   // Blue (primary for 610)
    '26': '#4CAF50'     // Green (primary for 26)
  }
  
  return colorMap[shiftCode] || '#757575'
}

/**
 * Render shift code with multi-color digits for split shifts
 * For 610: 6 = Blue, 10 = Green
 * For 26: 2 = Green, 6 = Red
 */
function renderMultiColorShiftCode(shiftCode, department) {
  if (!shiftCode || shiftCode === 'O' || shiftCode === 'OFF') {
    return '<div style="width:18px; height:18px; border:2px solid #F44336; border-radius:50%; display:inline-block;"></div>'
  }
  
  // Special handling for multi-color codes
  if (shiftCode === '610') {
    // 6 = Blue, 10 = Green
    return `<span style="color:#2196F3; font-weight:bold;">6</span><span style="color:#4CAF50; font-weight:bold;">10</span>`
  } else if (shiftCode === '26') {
    // 2 = Green, 6 = Red
    return `<span style="color:#4CAF50; font-weight:bold;">2</span><span style="color:#F44336; font-weight:bold;">6</span>`
  } else {
    // Single color codes
    const color = getShiftColorHex(shiftCode, department)
    return `<span style="color:${color}; font-weight:bold;">${shiftCode}</span>`
  }
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function emitFilters() {
  // Emit filter changes if needed
}

function clearFilters() {
  localFilters.value = {
    search: ''
  }
}
</script>

<style scoped>
.monitoring-dashboard {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 2px solid #e9ecef;
}

.dashboard-header h3 {
  margin: 0;
  font-size: 20px;
  color: #1a3a5c;
  font-weight: 700;
}

.filter-bar {
  display: flex;
  gap: 12px;
  align-items: center;
}

.search-input {
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  min-width: 300px;
  transition: all 0.2s;
}

.search-input:focus {
  border-color: #1a3a5c;
  box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.1);
}

.btn-clear {
  padding: 10px 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #f8f9fa;
  color: #666;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear:hover {
  background: #e9ecef;
  border-color: #999;
}

/* Department Sections */
.departments-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.department-section {
  border: 1px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.department-header {
  background: linear-gradient(135deg, #1a3a5c 0%, #2c5282 100%);
  color: #fff;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dept-info h4 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 700;
}

.emp-count {
  font-size: 13px;
  opacity: 0.9;
}

.dept-actions {
  display: flex;
  gap: 10px;
}

.btn-preview-dept {
  background: #3498db;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-preview-dept:hover {
  background: #2980b9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-print-dept {
  background: #27ae60;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-print-dept:hover {
  background: #229954;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

/* Employee Table */
.emp-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.emp-table thead {
  background: #f8f9fa;
}

.emp-table th {
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #555;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e9ecef;
}

.emp-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f0f0f0;
}

.emp-table tbody tr:hover {
  background: #f8fbff;
}

.emp-name-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.emp-name {
  font-weight: 600;
  color: #1a3a5c;
  font-size: 14px;
}

.emp-no {
  font-size: 11px;
  color: #888;
  font-family: monospace;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 8px 16px;
  border-radius: 6px;
  border: 1px solid;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action.view {
  background: #e8f0fe;
  border-color: #1a3a5c;
  color: #1a3a5c;
}

.btn-action.view:hover {
  background: #1a3a5c;
  color: #fff;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-message {
  font-size: 16px;
  font-weight: 600;
  color: #666;
  margin: 0 0 8px 0;
}

.empty-hint {
  font-size: 13px;
  color: #999;
  margin: 0;
}

/* Schedule Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.schedule-modal {
  background: #fff;
  border-radius: 12px;
  width: 800px;
  max-width: 95vw;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1a3a5c;
}

.emp-details {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: #666;
}

.close-btn {
  background: none;
  border: none;
  font-size: 32px;
  line-height: 1;
  color: #888;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f0f4f8;
  color: #333;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.schedule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 12px;
}

.schedule-card {
  background: #f8f9fc;
  border: 1px solid #e2e6ef;
  border-radius: 8px;
  padding: 12px;
}

.schedule-date {
  font-size: 14px;
  font-weight: 700;
  color: #1a3a5c;
  margin-bottom: 8px;
}

.schedule-shift {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.shift-badge {
  padding: 4px 10px;
  border-radius: 4px;
  font-weight: 700;
  font-size: 12px;
  color: #fff;
}

.shift-badge.multi-color {
  background: transparent;
  padding: 4px 6px;
  border: 1px solid #ddd;
  color: inherit;
}

.shift-badge.off-badge {
  background: transparent;
  padding: 4px;
}

.off-circle {
  width: 18px;
  height: 18px;
  border: 2px solid #F44336;
  border-radius: 50%;
  display: inline-block;
}

.shift-time {
  font-size: 12px;
  color: #666;
}

.schedule-remarks {
  font-size: 11px;
  color: #888;
  font-style: italic;
  margin-top: 6px;
}

.no-schedules {
  text-align: center;
  padding: 40px;
  color: #888;
  font-size: 14px;
}

/* Preview Modal */
.preview-overlay {
  z-index: 10000;
}

.preview-modal {
  background: #fff;
  border-radius: 12px;
  width: 95vw;
  max-width: 1400px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.preview-modal .modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
}

.preview-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn-print {
  background: #27ae60;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-print:hover {
  background: #229954;
  transform: translateY(-1px);
}

.preview-body {
  flex: 1;
  overflow: auto;
  padding: 20px;
  background: #f8f9fa;
}
</style>
