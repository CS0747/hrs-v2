<template>
  <div class="monitoring-dashboard">
    <div class="dashboard-header">
      <h3>Schedule Monitoring</h3>
      <div class="filter-bar">
        <select v-model="localFilters.department" @change="emitFilters">
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
        </select>
        <input
          v-model="localFilters.search"
          @input="emitFilters"
          type="text"
          placeholder="Search employee..."
          class="search-input"
        />
        <button v-if="hasActiveFilters" class="btn-clear" @click="clearFilters">
          Clear
        </button>
      </div>
    </div>

    <!-- Employee List -->
    <div class="employee-list">
      <table class="emp-table">
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th style="width: 200px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(emp, index) in filteredEmployees" :key="emp.employeeNo">
            <td>{{ index + 1 }}</td>
            <td>
              <div class="emp-name-cell">
                <span class="emp-name">{{ emp.employeeName }}</span>
                <span class="emp-no">{{ emp.employeeNo }}</span>
              </div>
            </td>
            <td>{{ emp.department }}</td>
            <td>
              <div class="action-buttons">
                <button class="btn-action view" @click="viewSchedule(emp)">
                  👁 View Schedule
                </button>
                <button class="btn-action print" @click="printSchedule(emp)">
                  🖨 Print
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredEmployees.length === 0" class="empty-state">
        <div class="empty-icon">📅</div>
        <p class="empty-message">No employees found</p>
        <p class="empty-hint">Try adjusting your filters</p>
      </div>
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useLegendStore } from '@/stores/legend'
import { printIndividualSchedule } from '@/utils/print'

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

// Local filters
const localFilters = ref({
  department: '',
  search: ''
})

// Modal state
const showScheduleModal = ref(false)
const selectedEmployee = ref(null)

// Computed: Unique employees from schedules
const allEmployees = computed(() => {
  const empMap = new Map()
  props.schedules.forEach(schedule => {
    if (!empMap.has(schedule.employeeNo)) {
      empMap.set(schedule.employeeNo, {
        employeeNo: schedule.employeeNo,
        employeeName: schedule.employeeName,
        department: schedule.department
      })
    }
  })
  return Array.from(empMap.values())
})

// Computed: Filtered employees
const filteredEmployees = computed(() => {
  let result = allEmployees.value

  if (localFilters.value.department) {
    result = result.filter(e => e.department === localFilters.value.department)
  }

  if (localFilters.value.search) {
    const q = localFilters.value.search.toLowerCase()
    result = result.filter(e =>
      e.employeeName.toLowerCase().includes(q) ||
      e.employeeNo.toLowerCase().includes(q)
    )
  }

  return result.sort((a, b) => a.employeeName.localeCompare(b.employeeName))
})

// Computed: Employee schedules for modal
const employeeSchedules = computed(() => {
  if (!selectedEmployee.value) return []
  return props.schedules
    .filter(s => s.employeeNo === selectedEmployee.value.employeeNo)
    .sort((a, b) => new Date(a.scheduleDate) - new Date(b.scheduleDate))
})

const hasActiveFilters = computed(() =>
  localFilters.value.department || localFilters.value.search
)

// Methods
function viewSchedule(emp) {
  selectedEmployee.value = emp
  showScheduleModal.value = true
}

function printSchedule(emp) {
  const empSchedules = props.schedules.filter(s => s.employeeNo === emp.employeeNo)
  
  if (empSchedules.length === 0) {
    alert('No schedules found for this employee')
    return
  }

  // Print using the format from the image
  printEmployeeScheduleTable(emp, empSchedules)
}

function printEmployeeScheduleTable(emp, schedules) {
  // Group schedules by month
  const byMonth = {}
  schedules.forEach(schedule => {
    const date = new Date(schedule.scheduleDate)
    const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    if (!byMonth[monthKey]) {
      byMonth[monthKey] = []
    }
    byMonth[monthKey].push(schedule)
  })

  // Generate HTML for each month
  Object.entries(byMonth).forEach(([monthKey, monthSchedules]) => {
    const [year, month] = monthKey.split('-')
    const monthName = new Date(year, parseInt(month) - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    
    // Build calendar grid
    const firstDay = new Date(year, parseInt(month) - 1, 1)
    const lastDay = new Date(year, parseInt(month), 0)
    const daysInMonth = lastDay.getDate()
    
    // Create schedule map by date
    const scheduleMap = {}
    monthSchedules.forEach(s => {
      const day = new Date(s.scheduleDate).getDate()
      scheduleMap[day] = s
    })
    
    // Build table rows
    let tableRows = ''
    tableRows += `
      <tr>
        <td style="padding:8px; border:1px solid #000; font-weight:bold;">${emp.employeeName}</td>
    `
    
    for (let day = 1; day <= 31; day++) {
      if (day <= daysInMonth) {
        const schedule = scheduleMap[day]
        const shiftCode = schedule ? schedule.shiftCode || '85' : 'O'
        const bgColor = schedule ? getShiftColorHex(schedule.shiftCode, emp.department) : '#fff'
        tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center; background:${bgColor}; font-weight:bold;">${shiftCode}</td>`
      } else {
        tableRows += `<td style="padding:4px; border:1px solid #000;"></td>`
      }
    }
    
    tableRows += `<td style="padding:4px; border:1px solid #000; text-align:center;">20</td></tr>`
    
    const html = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Schedule - ${emp.employeeName}</title>
        <style>
          @page { size: A4 landscape; margin: 15mm; }
          body { font-family: Arial, sans-serif; font-size: 10pt; }
          .header { text-align: center; margin-bottom: 20px; }
          .header h2 { margin: 5px 0; }
          table { width: 100%; border-collapse: collapse; }
          th { background: #f0f0f0; padding: 6px; border: 1px solid #000; font-size: 9pt; }
          td { padding: 4px; border: 1px solid #000; text-align: center; font-size: 9pt; }
          .legend { margin-top: 20px; font-size: 9pt; }
          .legend-item { display: inline-block; margin-right: 15px; }
        </style>
      </head>
      <body>
        <div class="header">
          <h2>GENERAL EMILIO AGUINALDO MEMORIAL HOSPITAL</h2>
          <h3>${emp.department}</h3>
          <h3>${monthName}</h3>
        </div>
        <table>
          <thead>
            <tr>
              <th rowspan="2">NAME OF EMPLOYEE</th>
              <th colspan="31">DAYS</th>
              <th rowspan="2">NO. OF DAYS</th>
            </tr>
            <tr>
              ${Array.from({length: 31}, (_, i) => `<th>${i + 1}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>
        <div class="legend">
          <strong>LEGEND:</strong>
          <span class="legend-item">O = OFF</span>
          <span class="legend-item">85 = 8:00 - 5:00</span>
          <span class="legend-item">62 = 6:00 AM TO 2:00 PM</span>
          <span class="legend-item">210 = 2:00 PM TO 10:00 PM</span>
          <span class="legend-item">106 = 10:00 PM TO 6:00 AM</span>
        </div>
      </body>
      </html>
    `
    
    const printWindow = window.open('', '_blank')
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => printWindow.print(), 250)
  })
}

function getShiftColor(shiftCode, department) {
  const colors = legendStore.getColorForShift(shiftCode, department)
  return colors.primary
}

function getShiftColorHex(shiftCode, department) {
  if (!shiftCode || shiftCode === 'O' || shiftCode === 'OFF') return '#fff'
  const colors = legendStore.getColorForShift(shiftCode, department)
  return colors.primary
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
    department: '',
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
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 16px;
}

.dashboard-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1a3a5c;
}

.filter-bar {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filter-bar select,
.filter-bar .search-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
}

.search-input {
  min-width: 250px;
}

.btn-clear {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: #f8f9fa;
  color: #666;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear:hover {
  background: #e9ecef;
  border-color: #999;
}

/* Employee List Table */
.employee-list {
  margin-top: 20px;
}

.emp-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.emp-table thead {
  background: #1a3a5c;
  color: #fff;
}

.emp-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  border: 1px solid #fff;
}

.emp-table td {
  padding: 12px;
  border-bottom: 1px solid #f0f4f8;
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
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid;
  font-size: 12px;
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

.btn-action.print {
  background: #f0f9f4;
  border-color: #27ae60;
  color: #27ae60;
}

.btn-action.print:hover {
  background: #27ae60;
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
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #f0f4f8;
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
}

.close-btn:hover {
  background: #f0f4f8;
}

.modal-body {
  padding: 20px;
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
</style>
