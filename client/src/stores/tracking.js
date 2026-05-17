import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'
import { API_ENDPOINTS } from '@/config/api'

const API_URL = API_ENDPOINTS.TRACKING

export const useTrackingStore = defineStore('tracking', () => {
    const trackingRecords = ref([])
    const loading = ref(false)
    const error = ref(null)

    const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

    // Fetch all tracking records from database
    async function fetchRecords() {
        loading.value = true
        error.value = null
        try {
            const response = await fetch(API_URL)
            if (!response.ok) throw new Error('Failed to fetch tracking records')
            const data = await response.json()

            trackingRecords.value = data.map(record => ({
                id: record.id,
                docType: record.doc_type,
                docNo: record.doc_no,
                from: record.from_office,
                to: record.to_office,
                direction: record.direction ?? 'incoming',
                dateForwarded: record.date_forwarded || '',
                dateReceived: record.date_received || '',
                receivedBy: record.received_by || '',
                status: record.status,
                remarks: record.remarks || '',
            }))
        } catch (err) {
            error.value = err.message
            console.error('Error fetching tracking records:', err)
        } finally {
            loading.value = false
        }
    }

    function toPayload(record) {
        return {
            doc_type: record.docType,
            doc_no: record.docNo,
            from_office: record.from,
            to_office: record.to,
            direction: record.direction ?? 'incoming',
            date_forwarded: record.dateForwarded || null,
            date_received: record.dateReceived || null,
            received_by: record.receivedBy || '',
            status: record.status || 'Pending',
            remarks: record.remarks || '',
        }
    }

    async function addRecord(record) {
        loading.value = true
        error.value = null
        try {
            const payload = toPayload(record)
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
            if (!response.ok) {
                const err = await response.json()
                throw new Error(err.error || 'Failed to add tracking record')
            }
            const result = await response.json()
            await fetchRecords()
            trackCreate('Document Tracking', payload, record.docNo)
            return result
        } catch (err) {
            error.value = err.message
            console.error('Error adding tracking record:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    async function updateRecord(id, data) {
        loading.value = true
        error.value = null
        try {
            const payload = toPayload(data)
            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
            if (!response.ok) {
                const err = await response.json()
                throw new Error(err.error || 'Failed to update tracking record')
            }
            const idx = trackingRecords.value.findIndex(r => r.id === id)
            if (idx !== -1) {
                const old = { ...trackingRecords.value[idx] }
                trackingRecords.value[idx] = { ...old, ...data, id }
                trackUpdate('Document Tracking', old, trackingRecords.value[idx], data.docNo ?? old.docNo)
            }
            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error updating tracking record:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    async function deleteRecord(id) {
        loading.value = true
        error.value = null
        try {
            const rec = trackingRecords.value.find(r => r.id === id)
            const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' })
            if (!response.ok) {
                const err = await response.json()
                throw new Error(err.error || 'Failed to delete tracking record')
            }
            trackingRecords.value = trackingRecords.value.filter(r => r.id !== id)
            if (rec) trackDelete('Document Tracking', rec, rec.docNo)
            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error deleting tracking record:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        trackingRecords,
        loading,
        error,
        fetchRecords,
        addRecord,
        updateRecord,
        deleteRecord,
    }
})
