<template>
  <div class="schedule-form">
    <!-- Employee Selection -->
    <div class="form-section">
      <div class="form-group">
        <label>Employee</label>
        <div class="emp-combobox">
          <input
            v-model="empSearch"
            class="emp-search-input"
            placeholder="Type name or employee no..."
            @input="onEmpSearchInput"
            @focus="empDropOpen = true"
            @blur="onEmpBlur"
            autocomplete="off"
          />
          <div v-if="empDropOpen && filteredEmps.length" class="emp-dropdown">
            <div
              v-for="emp in filteredEmps"
              :key="emp.id"
              class="emp-option"
              @mousedown.prevent="selectEmployee(emp)"
            >
              <span class="emp-opt-no">{{ emp.employeeNo }}</span>
              <span class="emp-opt-name">{{ emp.lastName }}, {{ emp.firstName }}</span>
              <span class="emp-opt-dept">{{ emp.department }}</span>
            </div>
          </div>
          <div v-if="empDropOpen && !filteredEmps.length" class="emp-dropdown">
            <div class="emp-option-empty">No employees found.</div>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Employee Name</label>
          <input v-model="localForm.employeeName" readonly class="readonly-input" />
        </div>
        <div class="form-group">
          <label>Department</label>
          <input v-model="localForm.department" readonly class="readonly-input" />
        </div>
      </div>
    </div>

    <!-- Calendar and Clock Side by Side -->
    <div class="calendar-clock-container">
      <!-- Calendar Section -->
      <div class="calendar-section">
        <div class="section-header">
          <h4>Select Dates</h4>
          <div class="cal-actions">
            <button type="button" class="btn-sm" @click="selectAllMonth">All Month</button>
            <button type="button" class="btn-sm danger" @click="clearFormDates">Clear</button>
          </div>
        </div>
        <div class="month-calendar">
          <div class="cal-nav">
            <button type="button" class="nav-btn" @click="formCalPrev">‹</button>
            <span class="month-label">{{ formCalLabel }}</span>
            <button type="button" class="nav-btn" @click="formCalNext">›</button>
          </div>
          <div class="cal-grid">
            <div v-for="h in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="h" class="cal-dow">{{ h }}</div>
            <template v-for="(cell, idx) in formCalGrid" :key="idx">
              <div
                v-if="cell"
                class="cal-day"
                :class="{
                  'selected': isDateSelected(cell),
                  'today': isToday(cell),
                  'weekend': cell.getDay() === 0 || cell.getDay() === 6,
                  'has-schedule': daySchedules[toDateKey(cell)]
                }"
                @click="toggleFormDate(cell)"
              >
                <span class="day-num">{{ cell.getDate() }}</span>
                <span v-if="daySchedules[toDateKey(cell)]" class="day-shift">
                  {{ daySchedules[toDateKey(cell)].shiftCode }}
                </span>
              </div>
              <div v-else class="cal-day empty"></div>
            </template>
          </div>
        </div>
      </div>

      <!-- Clock/Time Configuration Section -->
      <div class="clock-section">
        <div class="section-header">
          <h4>Configure Time & Shift</h4>
        </div>
        
        <div v-if="localForm.selectedDates.length === 0" class="no-dates-msg">
          ← Select dates from calendar first
        </div>

        <div v-else class="time-config-list">
          <div 
            v-for="date in sortedSelectedDates" 
            :key="date"
            class="date-config-item"
            :class="{ active: activeDate === date }"
            @click="activeDate = date"
          >
            <div class="date-label">
              <span class="date-text">{{ formatDateLabel(date) }}</span>
              <button type="button" class="btn-remove" @click.stop="removeDate(date)">×</button>
            </div>
            
            <div v-if="activeDate === date" class="time-shift-config">
              <!-- Time Selection -->
              <div class="time-row">
                <div class="time-input-group">
                  <label>Start</label>
                  <input 
                    type="time" 
                    v-model="daySchedules[date].startTime"
                    @change="onTimeChange(date)"
                  />
                </div>
                <div class="time-input-group">
                  <label>End</label>
                  <input 
                    type="time" 
                    v-model="daySchedules[date].endTime"
                    @change="onTimeChange(date)"
                  />
                </div>
              </div>

              <!-- Shift Selection -->
              <div class="shift-row">
                <label>Shift</label>
                <div class="shift-buttons">
                  <button
                    v-for="shift in availableShifts"
                    :key="shift.code"
                    type="button"
                    class="shift-btn"
                    :class="{ active: daySchedules[date].shiftCode === shift.code }"
                    :style="getShiftStyle(shift)"
                    @click="selectShiftForDate(date, shift)"
                  >
                    {{ shift.code }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="localForm.selectedDates.length > 0" class="bulk-actions">
          <button type="button" class="btn-bulk" @click="applyToAll">
            Apply to All Dates
          </button>
        </div>
      </div>
    </div>

    <!-- Remarks -->
    <div class="form-section">
      <div class="form-group">
        <label>Remarks (Optional)</label>
        <textarea v-model="localForm.remarks" rows="2" placeholder="Add any notes..."></textarea>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useEmployeeStore } from '@/stores/employees'
import { useLegendStore } from '@/stores/legend'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  editMode: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const empStore = useEmployeeStore()
const legendStore = useLegendStore()

// Local form state
const localForm = ref({
  employeeNo: '',
  employeeName: '',
  department: '',
  selectedDates: [],
  remarks: ''
})

// Day schedules - stores time and shift for each date
const daySchedules = ref({})
const activeDate = ref(null)

// Employee combobox
const empSearch = ref('')
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
  localForm.value.employeeNo = emp.employeeNo
  localForm.value.employeeName = `${emp.lastName}, ${emp.firstName}`
  localForm.value.department = emp.department
  empSearch.value = `${emp.employeeNo} — ${emp.lastName}, ${emp.firstName}`
  empDropOpen.value = false
  
  // Load department-specific legends
  legendStore.fetchLegends(emp.department)
}

function onEmpSearchInput() {
  empDropOpen.value = true
  localForm.value.employeeNo = ''
  localForm.value.employeeName = ''
  localForm.value.department = ''
}

function onEmpBlur() {
  setTimeout(() => { empDropOpen.value = false }, 180)
}

// Available shifts based on department
const availableShifts = computed(() => {
  return legendStore.getLegendsForDepartment(localForm.value.department)
})

// Form calendar
const formCalMonth = ref(new Date())

const formCalGrid = computed(() => {
  const year = formCalMonth.value.getFullYear()
  const month = formCalMonth.value.getMonth()
  const first = new Date(year, month, 1)
  const last = new Date(year, month + 1, 0)
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
  return localForm.value.selectedDates.includes(toDateKey(date))
}

function isToday(date) {
  const t = new Date()
  return date.getFullYear() === t.getFullYear() &&
         date.getMonth() === t.getMonth() &&
         date.getDate() === t.getDate()
}

function toggleFormDate(date) {
  if (!date) return
  const key = toDateKey(date)
  const idx = localForm.value.selectedDates.indexOf(key)
  
  if (idx === -1) {
    // Add date
    localForm.value.selectedDates.push(key)
    // Initialize schedule for this date
    daySchedules.value[key] = {
      startTime: '08:00',
      endTime: '17:00',
      shiftCode: '85'
    }
    // Set as active
    activeDate.value = key
  } else {
    // Remove date
    localForm.value.selectedDates.splice(idx, 1)
    delete daySchedules.value[key]
    // Set active to first remaining date
    if (localForm.value.selectedDates.length > 0) {
      activeDate.value = localForm.value.selectedDates[0]
    } else {
      activeDate.value = null
    }
  }
}

function removeDate(dateKey) {
  const idx = localForm.value.selectedDates.indexOf(dateKey)
  if (idx !== -1) {
    localForm.value.selectedDates.splice(idx, 1)
    delete daySchedules.value[dateKey]
    if (activeDate.value === dateKey) {
      activeDate.value = localForm.value.selectedDates[0] || null
    }
  }
}

function clearFormDates() {
  localForm.value.selectedDates = []
  daySchedules.value = {}
  activeDate.value = null
}

function selectAllMonth() {
  const year = formCalMonth.value.getFullYear()
  const month = formCalMonth.value.getMonth()
  const last = new Date(year, month + 1, 0).getDate()
  for (let d = 1; d <= last; d++) {
    const key = toDateKey(new Date(year, month, d))
    if (!localForm.value.selectedDates.includes(key)) {
      localForm.value.selectedDates.push(key)
      daySchedules.value[key] = {
        startTime: '08:00',
        endTime: '17:00',
        shiftCode: '85'
      }
    }
  }
  if (localForm.value.selectedDates.length > 0) {
    activeDate.value = localForm.value.selectedDates[0]
  }
}

// Sorted selected dates
const sortedSelectedDates = computed(() => {
  return [...localForm.value.selectedDates].sort()
})

// Format date label
function formatDateLabel(dateKey) {
  const date = new Date(dateKey + 'T00:00:00')
  return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

// Select shift for specific date
function selectShiftForDate(dateKey, shift) {
  if (daySchedules.value[dateKey]) {
    daySchedules.value[dateKey].shiftCode = shift.code
  }
}

// Time change handler
function onTimeChange(dateKey) {
  // Auto-detect shift based on time
  const schedule = daySchedules.value[dateKey]
  if (!schedule) return
  
  // Try to match with available shifts
  const matchedShift = availableShifts.value.find(shift => {
    const timeRange = shift.timeRange.toLowerCase()
    const start = schedule.startTime
    const end = schedule.endTime
    
    // Simple matching logic
    if (start === '06:00' && end === '14:00') return shift.code === '62'
    if (start === '14:00' && end === '22:00') return shift.code === '210'
    if (start === '22:00' && end === '06:00') return shift.code === '106'
    if (start === '08:00' && end === '17:00') return shift.code === '85'
    if (start === '06:00' && end === '22:00') return shift.code === '610'
    if (start === '14:00' && end === '06:00') return shift.code === '26'
    
    return false
  })
  
  if (matchedShift) {
    schedule.shiftCode = matchedShift.code
  }
}

// Get shift style
function getShiftStyle(shift) {
  if (shift.code === 'OFF') {
    return {
      border: `2px solid ${shift.colorPrimary}`,
      background: 'transparent',
      color: shift.colorPrimary
    }
  }
  return {
    background: shift.colorPrimary,
    color: '#fff'
  }
}

// Apply current date's config to all dates
function applyToAll() {
  if (!activeDate.value || !daySchedules.value[activeDate.value]) return
  
  const template = daySchedules.value[activeDate.value]
  localForm.value.selectedDates.forEach(dateKey => {
    daySchedules.value[dateKey] = {
      startTime: template.startTime,
      endTime: template.endTime,
      shiftCode: template.shiftCode
    }
  })
}

// Watch for changes and emit
watch([localForm, daySchedules], () => {
  emit('update:modelValue', {
    ...localForm.value,
    daySchedules: daySchedules.value
  })
}, { deep: true })

// Initialize from props
onMounted(() => {
  if (props.modelValue) {
    Object.assign(localForm.value, props.modelValue)
    if (props.modelValue.daySchedules) {
      daySchedules.value = { ...props.modelValue.daySchedules }
    }
  }
  
  // Load legends
  legendStore.fetchLegends()
})
</script>

<style scoped>
.schedule-form {
  width: 100%;
}

.form-section {
  margin-bottom: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group label {
  font-size: 12px;
  font-weight: 600;
  color: #555;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  font-family: inherit;
}

.form-group textarea {
  resize: vertical;
}

.readonly-input {
  background: #f8f9fa !important;
  color: #555;
  cursor: default;
}

/* Employee combobox */
.emp-combobox {
  position: relative;
}

.emp-search-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
}

.emp-search-input:focus {
  border-color: #1a3a5c;
}

.emp-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  z-index: 9999;
  max-height: 220px;
  overflow-y: auto;
}

.emp-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f5f5f5;
}

.emp-option:last-child {
  border-bottom: none;
}

.emp-option:hover {
  background: #f0f9f4;
}

.emp-opt-no {
  font-family: monospace;
  font-size: 11px;
  color: #888;
  flex-shrink: 0;
  min-width: 90px;
}

.emp-opt-name {
  font-size: 13px;
  font-weight: 600;
  color: #1a1a2e;
  flex: 1;
}

.emp-opt-dept {
  font-size: 11px;
  color: #aaa;
  flex-shrink: 0;
}

.emp-option-empty {
  padding: 12px;
  text-align: center;
  color: #aaa;
  font-size: 13px;
}

/* Calendar and Clock Side by Side */
.calendar-clock-container {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.calendar-section,
.clock-section {
  background: #f8f9fc;
  border: 1px solid #e2e6ef;
  border-radius: 8px;
  padding: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.section-header h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #1a3a5c;
}

.cal-actions {
  display: flex;
  gap: 6px;
}

.btn-sm {
  padding: 4px 10px;
  border-radius: 5px;
  border: 1px solid #ddd;
  background: #fff;
  color: #1a3a5c;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-sm:hover {
  background: #e0e8f0;
}

.btn-sm.danger {
  color: #c0392b;
  border-color: #f5b7b1;
  background: #fdecea;
}

.btn-sm.danger:hover {
  background: #fad4d1;
}

/* Month Calendar */
.month-calendar {
  background: #fff;
  border-radius: 6px;
  padding: 12px;
}

.cal-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.nav-btn {
  background: none;
  border: 1px solid #ddd;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  font-size: 18px;
  cursor: pointer;
  color: #555;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn:hover {
  background: #f0f4f8;
}

.month-label {
  font-size: 13px;
  font-weight: 700;
  color: #1a3a5c;
}

.cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.cal-dow {
  font-size: 10px;
  font-weight: 700;
  color: #aaa;
  text-align: center;
  padding: 4px 0;
}

.cal-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  color: #444;
  transition: all 0.12s;
  border: 2px solid transparent;
  background: #fff;
  position: relative;
}

.cal-day:hover:not(.empty) {
  background: #e8f0fe;
  color: #1a3a5c;
}

.cal-day.empty {
  cursor: default;
  background: transparent;
}

.cal-day.today {
  border-color: #1a3a5c;
  font-weight: 700;
}

.cal-day.weekend {
  color: #999;
}

.cal-day.selected {
  background: #1a3a5c !important;
  color: #fff !important;
  border-color: #1a3a5c;
  font-weight: 700;
}

.cal-day.has-schedule .day-shift {
  position: absolute;
  bottom: 2px;
  font-size: 8px;
  font-weight: 700;
  background: rgba(255,255,255,0.3);
  padding: 1px 3px;
  border-radius: 2px;
}

.day-num {
  font-size: 12px;
}

/* Clock Section */
.no-dates-msg {
  text-align: center;
  color: #888;
  font-size: 13px;
  padding: 40px 20px;
}

.time-config-list {
  max-height: 400px;
  overflow-y: auto;
}

.date-config-item {
  background: #fff;
  border-radius: 6px;
  margin-bottom: 8px;
  border: 2px solid transparent;
  transition: border-color 0.15s;
}

.date-config-item.active {
  border-color: #1a3a5c;
}

.date-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  cursor: pointer;
  font-weight: 600;
  color: #1a3a5c;
  font-size: 13px;
}

.date-label:hover {
  background: #f8f9fa;
}

.btn-remove {
  background: #fdecea;
  border: none;
  border-radius: 4px;
  width: 24px;
  height: 24px;
  font-size: 18px;
  line-height: 1;
  color: #e74c3c;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove:hover {
  background: #e74c3c;
  color: #fff;
}

.time-shift-config {
  padding: 12px;
  border-top: 1px solid #f0f4f8;
}

.time-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.time-input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.time-input-group label {
  font-size: 11px;
  font-weight: 600;
  color: #666;
}

.time-input-group input[type="time"] {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
  outline: none;
}

.time-input-group input[type="time"]:focus {
  border-color: #1a3a5c;
}

.shift-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.shift-row label {
  font-size: 11px;
  font-weight: 600;
  color: #666;
}

.shift-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.shift-btn {
  padding: 6px 12px;
  border-radius: 4px;
  border: 2px solid transparent;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s;
}

.shift-btn:hover {
  opacity: 0.8;
}

.shift-btn.active {
  border-color: #fff;
  box-shadow: 0 0 0 2px #1a3a5c;
}

.bulk-actions {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e2e6ef;
}

.btn-bulk {
  width: 100%;
  padding: 8px 16px;
  border-radius: 6px;
  border: 1px solid #1a3a5c;
  background: #1a3a5c;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-bulk:hover {
  background: #2980b9;
}
</style>
