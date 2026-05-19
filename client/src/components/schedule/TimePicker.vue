<template>
  <div class="time-picker">
    <div class="time-picker-label">{{ label }}</div>
    
    <div class="time-inputs">
      <!-- Start Time -->
      <div class="time-input-group">
        <label class="input-label">Start Time</label>
        <div class="time-input-wrapper">
          <select 
            v-model="startHour" 
            @change="updateTime"
            class="time-select"
            data-testid="start-hour"
          >
            <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
          </select>
          <span class="time-separator">:</span>
          <select 
            v-model="startMinute" 
            @change="updateTime"
            class="time-select"
            data-testid="start-minute"
          >
            <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
          </select>
          <select 
            v-model="startPeriod" 
            @change="updateTime"
            class="time-select period"
            data-testid="start-period"
          >
            <option value="AM">AM</option>
            <option value="PM">PM</option>
          </select>
        </div>
      </div>

      <!-- End Time -->
      <div class="time-input-group">
        <label class="input-label">End Time</label>
        <div class="time-input-wrapper">
          <select 
            v-model="endHour" 
            @change="updateTime"
            class="time-select"
            data-testid="end-hour"
          >
            <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
          </select>
          <span class="time-separator">:</span>
          <select 
            v-model="endMinute" 
            @change="updateTime"
            class="time-select"
            data-testid="end-minute"
          >
            <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
          </select>
          <select 
            v-model="endPeriod" 
            @change="updateTime"
            class="time-select period"
            data-testid="end-period"
          >
            <option value="AM">AM</option>
            <option value="PM">PM</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Validation Error -->
    <div v-if="validationError" class="error-message" data-testid="error-message">
      {{ validationError }}
    </div>

    <!-- Suggested Shifts -->
    <div v-if="suggestedShift" class="suggested-shift">
      <span class="suggestion-icon">💡</span>
      Suggested shift: <strong>{{ suggestedShift.name }}</strong> ({{ suggestedShift.code }})
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ start: '08:00', end: '17:00' })
  },
  format: {
    type: String,
    default: '12h',
    validator: (value) => ['12h', '24h'].includes(value)
  },
  suggestions: {
    type: Array,
    default: () => ['06:00', '08:00', '14:00', '22:00']
  },
  label: {
    type: String,
    default: 'Shift Time'
  }
})

const emit = defineEmits(['update:modelValue', 'shift-detected', 'validation-error'])

// Time options
const hours = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
const minutes = ['00', '15', '30', '45']

// Time state
const startHour = ref('08')
const startMinute = ref('00')
const startPeriod = ref('AM')
const endHour = ref('05')
const endMinute = ref('00')
const endPeriod = ref('PM')

// Validation
const validationError = ref(null)

// Convert 12-hour to 24-hour format
function to24Hour(hour, minute, period) {
  let h = parseInt(hour)
  if (period === 'PM' && h !== 12) h += 12
  if (period === 'AM' && h === 12) h = 0
  return `${String(h).padStart(2, '0')}:${minute}`
}

// Convert 24-hour to 12-hour format
function to12Hour(time24) {
  const [hour, minute] = time24.split(':')
  let h = parseInt(hour)
  const period = h >= 12 ? 'PM' : 'AM'
  if (h > 12) h -= 12
  if (h === 0) h = 12
  return {
    hour: String(h).padStart(2, '0'),
    minute,
    period
  }
}

// Computed: Start time in 24-hour format
const startTime24 = computed(() => {
  return to24Hour(startHour.value, startMinute.value, startPeriod.value)
})

// Computed: End time in 24-hour format
const endTime24 = computed(() => {
  return to24Hour(endHour.value, endMinute.value, endPeriod.value)
})

// Computed: Formatted display times
const startTimeDisplay = computed(() => {
  return `${startHour.value}:${startMinute.value} ${startPeriod.value}`
})

const endTimeDisplay = computed(() => {
  return `${endHour.value}:${endMinute.value} ${endPeriod.value}`
})

// Validate time range
function validateTimeRange() {
  const start = startTime24.value
  const end = endTime24.value
  
  if (end <= start) {
    validationError.value = 'End time must be after start time'
    emit('validation-error', validationError.value)
    return false
  }
  
  validationError.value = null
  emit('validation-error', null)
  return true
}

// Detect shift based on time range
const suggestedShift = computed(() => {
  const start = startTime24.value
  const end = endTime24.value
  
  // Common shift patterns
  const shifts = [
    { code: '62', name: 'Morning Shift', start: '06:00', end: '14:00' },
    { code: '210', name: 'Evening Shift', start: '14:00', end: '22:00' },
    { code: '106', name: 'Night Shift', start: '22:00', end: '06:00' },
    { code: '85', name: 'Standard Shift', start: '08:00', end: '17:00' },
    { code: '610', name: 'Extended Day', start: '06:00', end: '22:00' },
    { code: '26', name: 'Extended Night', start: '14:00', end: '06:00' }
  ]
  
  // Find matching shift
  for (const shift of shifts) {
    if (start === shift.start && end === shift.end) {
      return shift
    }
  }
  
  return null
})

// Watch for shift detection
watch(suggestedShift, (newShift) => {
  if (newShift) {
    emit('shift-detected', newShift.code)
  }
})

// Update time and emit
function updateTime() {
  if (validateTimeRange()) {
    emit('update:modelValue', {
      start: startTime24.value,
      end: endTime24.value,
      startDisplay: startTimeDisplay.value,
      endDisplay: endTimeDisplay.value
    })
  }
}

// Initialize from modelValue
onMounted(() => {
  if (props.modelValue.start) {
    const start = to12Hour(props.modelValue.start)
    startHour.value = start.hour
    startMinute.value = start.minute
    startPeriod.value = start.period
  }
  
  if (props.modelValue.end) {
    const end = to12Hour(props.modelValue.end)
    endHour.value = end.hour
    endMinute.value = end.minute
    endPeriod.value = end.period
  }
  
  validateTimeRange()
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue.start) {
    const start = to12Hour(newValue.start)
    startHour.value = start.hour
    startMinute.value = start.minute
    startPeriod.value = start.period
  }
  
  if (newValue.end) {
    const end = to12Hour(newValue.end)
    endHour.value = end.hour
    endMinute.value = end.minute
    endPeriod.value = end.period
  }
}, { deep: true })
</script>

<style scoped>
.time-picker {
  width: 100%;
}

.time-picker-label {
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.time-inputs {
  display: flex;
  gap: 20px;
  margin-bottom: 8px;
}

.time-input-group {
  flex: 1;
}

.input-label {
  display: block;
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 4px;
}

.time-input-wrapper {
  display: flex;
  align-items: center;
  gap: 4px;
}

.time-select {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  background: white;
  cursor: pointer;
  transition: border-color 0.2s;
}

.time-select:hover {
  border-color: #999;
}

.time-select:focus {
  outline: none;
  border-color: #4CAF50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
}

.time-select.period {
  width: 70px;
}

.time-separator {
  font-weight: bold;
  color: #666;
  font-size: 1.2rem;
}

.error-message {
  color: #f44336;
  font-size: 0.875rem;
  margin-top: 4px;
  padding: 8px;
  background: #ffebee;
  border-radius: 4px;
  border-left: 3px solid #f44336;
}

.suggested-shift {
  margin-top: 8px;
  padding: 8px 12px;
  background: #e3f2fd;
  border-radius: 4px;
  border-left: 3px solid #2196F3;
  font-size: 0.875rem;
  color: #1976D2;
}

.suggestion-icon {
  margin-right: 4px;
}

@media (max-width: 768px) {
  .time-inputs {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
