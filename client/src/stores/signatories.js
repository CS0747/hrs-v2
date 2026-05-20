import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API_URL = `${import.meta.env.VITE_API_BASE_URL}/signatories.php`

export const useSignatoryStore = defineStore('signatories', () => {
    const signatories = ref([])
    const loading = ref(false)
    const error = ref(null)

    const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

    // Fetch all signatories from database
    async function fetchRecords() {
        loading.value = true
        error.value = null
        try {
            const response = await fetch(API_URL)
            if (!response.ok) throw new Error('Failed to fetch signatories')
            const data = await response.json()

            // Map database fields to camelCase
            signatories.value = data.map(record => ({
                id: record.id,
                name: record.name,
                position: record.position,
                role: record.role,
                department: record.department,
                order: parseInt(record.signing_order),
                active: Boolean(parseInt(record.active))
            }))
        } catch (err) {
            error.value = err.message
            console.error('Error fetching signatories:', err)
        } finally {
            loading.value = false
        }
    }

    // Add new signatory to database
    async function addRecord(record) {
        loading.value = true
        error.value = null
        try {
            const payload = {
                name: record.name,
                position: record.position,
                role: record.role,
                department: record.department,
                signing_order: record.order,
                active: record.active ? 1 : 0
            }

            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to add signatory')
            }

            const result = await response.json()

            // Refresh the list to get the new record with ID
            await fetchRecords()

            trackCreate('Signatory', payload, record.name)
            return result
        } catch (err) {
            error.value = err.message
            console.error('Error adding signatory:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Update existing signatory in database
    async function updateRecord(id, data) {
        loading.value = true
        error.value = null
        try {
            const payload = {
                name: data.name,
                position: data.position,
                role: data.role,
                department: data.department,
                signing_order: data.order,
                active: data.active ? 1 : 0
            }

            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to update signatory')
            }

            // Update local state
            const idx = signatories.value.findIndex(r => r.id === id)
            if (idx !== -1) {
                const old = { ...signatories.value[idx] }
                signatories.value[idx] = { ...old, ...data, id }
                trackUpdate('Signatory', old, signatories.value[idx], data.name ?? old.name)
            }

            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error updating signatory:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Delete signatory from database
    async function deleteRecord(id) {
        loading.value = true
        error.value = null
        try {
            const rec = signatories.value.find(r => r.id === id)

            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'DELETE'
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to delete signatory')
            }

            // Remove from local state
            signatories.value = signatories.value.filter(r => r.id !== id)

            if (rec) trackDelete('Signatory', rec, rec.name)

            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error deleting signatory:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Toggle active status
    async function toggleActive(id) {
        const signatory = signatories.value.find(s => s.id === id)
        if (signatory) {
            const updatedData = { ...signatory, active: !signatory.active }
            await updateRecord(id, updatedData)
        }
    }

    return {
        signatories,
        loading,
        error,
        fetchRecords,
        addRecord,
        updateRecord,
        deleteRecord,
        toggleActive
    }
})
