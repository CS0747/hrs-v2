import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API = `${import.meta.env.VITE_API_BASE_URL}/dtr.php`

export const useDTRStore = defineStore('dtr', () => {
  const dtrRecords = ref([])
  const loading = ref(false)
  const error = ref(null)

  const transmittalTypes = ['Main', 'Thea']
  const statuses = ['Pending', 'Submitted', 'Received', 'Verified', 'Returned']

  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  // ── Map DB row → camelCase ──────────────────────────────────────────────────
  function mapRow(r) {
    return {
      id: r.id,
      employeeNo: r.employee_no ?? '',
      employeeName: r.employee_name ?? '',
      department: r.department ?? '',
      period: r.period ?? '',
      transmittalType: r.transmittal_type ?? 'Main',
      submittedBy: r.submitted_by ?? '',
      dateSubmitted: r.date_submitted ?? '',
      dateReceived: r.date_received ?? '',
      verifiedBy: r.verified_by ?? '',
      verificationDate: r.verification_date ?? '',
      status: r.status ?? 'Pending',
      remarks: r.remarks ?? '',
      signatories: [],
      signedBy: [],
    }
  }

  // ── Fetch all ───────────────────────────────────────────────────────────────
  async function fetchRecords() {
    loading.value = true
    try {
      const res = await fetch(API)
      if (!res.ok) throw new Error('Failed to fetch DTR records')
      const rows = await res.json()
      dtrRecords.value = Array.isArray(rows) ? rows.map(mapRow) : []
    } catch (e) {
      error.value = e.message
      console.warn('DTR API unavailable:', e.message)
    } finally {
      loading.value = false
    }
  }

  // ── Add ─────────────────────────────────────────────────────────────────────
  async function addRecord(record) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(record),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Insert failed')
    await fetchRecords()
    const created = dtrRecords.value.find(r => r.id === json.id) ?? { ...record, id: json.id }
    trackCreate('DTR', created, created.employeeName)
  }

  // ── Update ──────────────────────────────────────────────────────────────────
  async function updateRecord(id, data) {
    const old = dtrRecords.value.find(r => r.id === id)
    const res = await fetch(`${API}?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Update failed')
    await fetchRecords()
    const updated = dtrRecords.value.find(r => r.id === id) ?? { ...data, id }
    trackUpdate('DTR', old, updated, updated.employeeName)
  }

  // ── Delete ──────────────────────────────────────────────────────────────────
  async function deleteRecord(id, processedBy = 'System') {
    const rec = dtrRecords.value.find(r => r.id === id)
    const res = await fetch(`${API}?id=${id}&processed_by=${encodeURIComponent(processedBy)}`, {
      method: 'DELETE',
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Delete failed')
    if (rec) trackDelete('DTR', rec, rec.employeeName)
    dtrRecords.value = dtrRecords.value.filter(r => r.id !== id)
  }

  // Init
  fetchRecords()

  return {
    dtrRecords, loading, error,
    transmittalTypes, statuses,
    fetchRecords, addRecord, updateRecord, deleteRecord,
  }
})
