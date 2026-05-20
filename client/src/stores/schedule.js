import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'
import { useLegendStore } from './legend'

const API = `${import.meta.env.VITE_API_BASE_URL}/schedule.php`

export const useScheduleStore = defineStore('schedule', () => {
  const schedules = ref([])
  const loading = ref(false)
  const error = ref(null)
  const shifts = ['Morning', 'Afternoon', 'Night', 'Split', 'Flexible']
  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()
  const legendStore = useLegendStore()

  // ── Map DB row (snake_case) → camelCase ────────────────────────────────────
  function mapRow(r) {
    return {
      id: r.id,
      employeeId: r.employee_id,
      employeeNo: r.employee_no,
      employeeName: r.employee_name,
      department: r.department ?? '',

      // New format fields
      scheduleDate: r.schedule_date ?? null,
      startTime: r.start_time ?? null,
      endTime: r.end_time ?? null,
      shiftCode: r.shift_code ?? null,
      shiftName: r.shift_name ?? null,
      status: r.status ?? 'Pending',
      submittedDate: r.submitted_date ?? null,
      lastUpdated: r.last_updated ?? null,
      remarks: r.remarks ?? null,

      // Legacy format fields
      shift: r.shift ?? 'Morning',
      shiftTime: r.shift_time ?? '',
      days: Array.isArray(r.days) ? r.days : [],
      effectiveDate: r.effective_date ?? '',
      endDate: r.end_date ?? '',
      restDay: r.rest_day ?? '',
    }
  }

  // Computed: Group schedules by department
  const schedulesByDepartment = computed(() => {
    const grouped = {}
    schedules.value.forEach(schedule => {
      const dept = schedule.department || 'Unknown'
      if (!grouped[dept]) {
        grouped[dept] = []
      }
      grouped[dept].push(schedule)
    })
    return grouped
  })

  // Computed: Group schedules by date
  const schedulesByDate = computed(() => {
    const grouped = {}
    schedules.value.forEach(schedule => {
      if (schedule.scheduleDate) {
        const date = schedule.scheduleDate
        if (!grouped[date]) {
          grouped[date] = []
        }
        grouped[date].push(schedule)
      }
    })
    return grouped
  })

  // ── Fetch all ──────────────────────────────────────────────────────────────
  async function fetchSchedules() {
    loading.value = true
    try {
      // Get user ID from session storage for authentication
      const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
      const headers = {}
      if (user?.id) {
        headers['X-User-ID'] = String(user.id)
      }

      const res = await fetch(API, { headers })
      if (!res.ok) throw new Error('Failed to fetch schedules')
      const rows = await res.json()
      schedules.value = Array.isArray(rows) ? rows.map(mapRow) : []
    } catch (e) {
      error.value = e.message
      console.warn('Schedule API unavailable:', e.message)
    } finally {
      loading.value = false
    }
  }

  // ── Add ────────────────────────────────────────────────────────────────────
  async function addSchedule(s) {
    // Get user ID from session storage for authentication
    const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
    const headers = {
      'Content-Type': 'application/json'
    }
    if (user?.id) {
      headers['X-User-ID'] = String(user.id)
    }

    const res = await fetch(API, {
      method: 'POST',
      headers,
      body: JSON.stringify(s),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Insert failed')
    await fetchSchedules()
    const created = schedules.value.find(r => r.employeeNo === (s.employee_no ?? s.employeeNo)) ?? { ...s, id: json.id }
    trackCreate('Schedule', created, created.employeeName ?? s.employee_name ?? '')
  }

  // ── Add Schedule with Multiple Dates (Bulk Assignment) ────────────────────
  async function addScheduleWithDates(scheduleData, specificDates) {
    const payload = {
      ...scheduleData,
      specificDates
    }

    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Bulk insert failed')

    await fetchSchedules()

    // Track creation for audit
    const employeeName = scheduleData.employeeName || scheduleData.employee_name || ''
    trackCreate('Schedule', {
      ...scheduleData,
      dates: specificDates,
      count: json.count
    }, employeeName)

    return json
  }

  // ── Get Schedules by Date Range ────────────────────────────────────────────
  async function getSchedulesByDateRange(startDate, endDate) {
    loading.value = true
    try {
      // Fetch all schedules and filter by date range
      await fetchSchedules()

      return schedules.value.filter(schedule => {
        if (!schedule.scheduleDate) return false
        const date = new Date(schedule.scheduleDate)
        const start = new Date(startDate)
        const end = new Date(endDate)
        return date >= start && date <= end
      })
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Get Schedules for Specific Date ────────────────────────────────────────
  function getSchedulesForDate(date, employeeId = null) {
    const dateStr = typeof date === 'string' ? date : date.toISOString().split('T')[0]

    let filtered = schedules.value.filter(s => s.scheduleDate === dateStr)

    if (employeeId) {
      filtered = filtered.filter(s => s.employeeId === employeeId)
    }

    return filtered
  }

  // ── Get Schedules by Department ────────────────────────────────────────────
  async function getSchedulesByDepartment(department) {
    loading.value = true
    try {
      // Get user ID from session storage for authentication
      const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
      const headers = {}
      if (user?.id) {
        headers['X-User-ID'] = String(user.id)
      }

      const res = await fetch(`${API}?dept=${encodeURIComponent(department)}`, { headers })
      if (!res.ok) throw new Error('Failed to fetch schedules')
      const rows = await res.json()
      return Array.isArray(rows) ? rows.map(mapRow) : []
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Get Schedules by Employee ──────────────────────────────────────────────
  async function getSchedulesByEmployee(employeeNo) {
    loading.value = true
    try {
      // Get user ID from session storage for authentication
      const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
      const headers = {}
      if (user?.id) {
        headers['X-User-ID'] = String(user.id)
      }

      const res = await fetch(`${API}?emp=${encodeURIComponent(employeeNo)}`, { headers })
      if (!res.ok) throw new Error('Failed to fetch schedules')
      const rows = await res.json()
      return Array.isArray(rows) ? rows.map(mapRow) : []
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Get Shift Color from Legend ────────────────────────────────────────────
  function getShiftColor(shiftCode, department = null) {
    return legendStore.getColorForShift(shiftCode, department)
  }

  // ── Get Shift Display Info ─────────────────────────────────────────────────
  function getShiftDisplay(shiftCode, department = null) {
    return legendStore.formatShiftDisplay(shiftCode, department)
  }

  // ── Check if Date has Schedules ────────────────────────────────────────────
  function hasScheduleOnDate(date, employeeId = null) {
    const schedules = getSchedulesForDate(date, employeeId)
    return schedules.length > 0
  }

  // ── Get Schedule Status Summary ────────────────────────────────────────────
  function getStatusSummary(department = null) {
    let filtered = schedules.value

    if (department) {
      filtered = filtered.filter(s => s.department === department)
    }

    const summary = {
      total: filtered.length,
      submitted: 0,
      pending: 0,
      missing: 0
    }

    filtered.forEach(schedule => {
      const status = schedule.status?.toLowerCase() || 'pending'
      if (status === 'submitted') summary.submitted++
      else if (status === 'pending') summary.pending++
      else if (status === 'missing') summary.missing++
    })

    return summary
  }

  // ── Update ─────────────────────────────────────────────────────────────────
  async function updateSchedule(id, data) {
    const old = schedules.value.find(r => r.id === id)
    // Get user ID from session storage for authentication
    const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
    const headers = {
      'Content-Type': 'application/json'
    }
    if (user?.id) {
      headers['X-User-ID'] = String(user.id)
    }

    const res = await fetch(`${API}?id=${id}`, {
      method: 'PUT',
      headers,
      body: JSON.stringify(data),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Update failed')
    await fetchSchedules()
    const updated = schedules.value.find(r => r.id === id) ?? { ...data, id }
    trackUpdate('Schedule', old, updated, updated.employeeName ?? '')
  }

  // ── Delete ─────────────────────────────────────────────────────────────────
  async function deleteSchedule(id) {
    const rec = schedules.value.find(r => r.id === id)
    // Get user ID from session storage for authentication
    const user = JSON.parse(sessionStorage.getItem('hris_user') || 'null')
    const headers = {}
    if (user?.id) {
      headers['X-User-ID'] = String(user.id)
    }

    const res = await fetch(`${API}?id=${id}`, {
      method: 'DELETE',
      headers
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Delete failed')
    if (rec) trackDelete('Schedule', rec, rec.employeeName ?? '')
    await fetchSchedules()
  }

  // Init
  fetchSchedules()
  legendStore.fetchLegends() // Load legends on init

  return {
    // State
    schedules,
    shifts,
    loading,
    error,

    // Computed
    schedulesByDepartment,
    schedulesByDate,

    // Actions
    fetchSchedules,
    addSchedule,
    addScheduleWithDates,
    updateSchedule,
    deleteSchedule,
    getSchedulesByDateRange,
    getSchedulesForDate,
    getSchedulesByDepartment,
    getSchedulesByEmployee,
    getShiftColor,
    getShiftDisplay,
    hasScheduleOnDate,
    getStatusSummary
  }
})
