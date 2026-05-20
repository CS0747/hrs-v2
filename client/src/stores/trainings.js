import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API = `${import.meta.env.VITE_API_BASE_URL}/trainings.php`

export const useTrainingsStore = defineStore('trainings', () => {
  const trainings = ref([])
  const loading = ref(false)
  const error = ref(null)
  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  const categories = ['Medical', 'Nursing', 'Administrative', 'Technical', 'Leadership', 'Safety', 'Other']
  const statuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled']

  // ── Map DB row (snake_case) → camelCase ──────────────────────────────────
  function mapRow(r) {
    return {
      id: r.id,
      title: r.title,
      category: r.category ?? 'Medical',
      instructor: r.instructor ?? '',
      venue: r.venue ?? '',
      dateFrom: r.date_from ?? '',
      dateTo: r.date_to ?? '',
      duration: Number(r.duration) || 1,
      maxParticipants: Number(r.max_participants) || 30,
      enrolled: Number(r.enrolled) || 0,
      status: r.status ?? 'Upcoming',
      description: r.description ?? '',
    }
  }

  // ── Fetch all ─────────────────────────────────────────────────────────────
  async function fetchTrainings() {
    loading.value = true
    try {
      const res = await fetch(API)
      if (!res.ok) throw new Error('Failed to fetch trainings')
      const rows = await res.json()
      trainings.value = Array.isArray(rows) ? rows.map(mapRow) : []
    } catch (e) {
      error.value = e.message
      console.warn('Trainings API unavailable:', e.message)
    } finally {
      loading.value = false
    }
  }

  // ── Add ───────────────────────────────────────────────────────────────────
  async function addTraining(t) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(t),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Insert failed')
    await fetchTrainings()
    const created = trainings.value.find(r => r.title === t.title) ?? { ...t, id: json.id }
    trackCreate('Training', created, created.title)
  }

  // ── Update ────────────────────────────────────────────────────────────────
  async function updateTraining(id, data) {
    const old = trainings.value.find(r => r.id === id)
    const res = await fetch(`${API}?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Update failed')
    await fetchTrainings()
    const updated = trainings.value.find(r => r.id === id) ?? { ...data, id }
    trackUpdate('Training', old, updated, updated.title ?? '')
  }

  // ── Delete ────────────────────────────────────────────────────────────────
  async function deleteTraining(id) {
    const rec = trainings.value.find(r => r.id === id)
    const res = await fetch(`${API}?id=${id}`, { method: 'DELETE' })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Delete failed')
    if (rec) trackDelete('Training', rec, rec.title ?? '')
    await fetchTrainings()
  }

  // Init
  fetchTrainings()

  return {
    trainings,
    categories,
    statuses,
    loading,
    error,
    fetchTrainings,
    addTraining,
    updateTraining,
    deleteTraining,
  }
})
