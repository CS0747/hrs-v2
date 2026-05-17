import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'
import { API_ENDPOINTS } from '@/config/api'

const API = API_ENDPOINTS.SCHEDULE

export const useScheduleStore = defineStore('schedule', () => {
  const schedules = ref([])
  const loading = ref(false)
  const error = ref(null)
  const shifts = ['Morning', 'Afternoon', 'Night', 'Split', 'Flexible']
  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  // ── Map DB row (snake_case) → camelCase ────────────────────────────────────
  function mapRow(r) {
    return {
      id: r.id,
      employeeNo: r.employee_no,
      employeeName: r.employee_name,
      department: r.department ?? '',
      shift: r.shift ?? 'Morning',
      shiftTime: r.shift_time ?? '',
      days: Array.isArray(r.days) ? r.days : [],
      effectiveDate: r.effective_date ?? '',
      endDate: r.end_date ?? '',
      restDay: r.rest_day ?? '',
    }
  }

  // ── Fetch all ──────────────────────────────────────────────────────────────
  async function fetchSchedules() {
    loading.value = true
    try {
      const res = await fetch(API)
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
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(s),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Insert failed')
    await fetchSchedules()
    const created = schedules.value.find(r => r.employeeNo === (s.employee_no ?? s.employeeNo)) ?? { ...s, id: json.id }
    trackCreate('Schedule', created, created.employeeName ?? s.employee_name ?? '')
  }

  // ── Update ─────────────────────────────────────────────────────────────────
  async function updateSchedule(id, data) {
    const old = schedules.value.find(r => r.id === id)
    const res = await fetch(`${API}?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
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
    const res = await fetch(`${API}?id=${id}`, { method: 'DELETE' })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Delete failed')
    if (rec) trackDelete('Schedule', rec, rec.employeeName ?? '')
    await fetchSchedules()
  }

  // Init
  fetchSchedules()

  return { schedules, shifts, loading, error, fetchSchedules, addSchedule, updateSchedule, deleteSchedule }
})
