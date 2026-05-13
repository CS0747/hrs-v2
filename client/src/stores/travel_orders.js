import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API_URL = 'http://localhost/hrs-v2/server/api/travel_orders.php'

export const useTravelOrderStore = defineStore('travelOrders', () => {
    const travelOrders = ref([])
    const loading = ref(false)
    const error = ref(null)

    const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

    // Fetch all travel orders from database
    async function fetchRecords() {
        loading.value = true
        error.value = null
        try {
            const response = await fetch(API_URL)
            if (!response.ok) throw new Error('Failed to fetch travel orders')
            const data = await response.json()

            // Map database fields to camelCase
            travelOrders.value = data.map(record => ({
                id: record.id,
                employeeId: record.employee_id,
                employeeNo: record.employee_no,
                employeeName: record.employee_name,
                department: record.department,
                destination: record.destination,
                purpose: record.purpose,
                dateFrom: record.date_from,
                dateTo: record.date_to,
                days: parseInt(record.days),
                transport: record.transport,
                approvedBy: record.approved_by || '',
                status: record.status,
                remarks: record.remarks || ''
            }))
        } catch (err) {
            error.value = err.message
            console.error('Error fetching travel orders:', err)
        } finally {
            loading.value = false
        }
    }

    // Add new travel order to database
    async function addRecord(record) {
        loading.value = true
        error.value = null
        try {
            const payload = {
                employee_id: record.employeeId || null,
                employee_no: record.employeeNo,
                employee_name: record.employeeName,
                department: record.department || '',
                destination: record.destination,
                purpose: record.purpose || '',
                date_from: record.dateFrom,
                date_to: record.dateTo,
                days: record.days,
                transport: record.transport || 'Public Transport',
                approved_by: record.approvedBy || null,
                status: record.status || 'Pending',
                remarks: record.remarks || ''
            }

            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to add travel order')
            }

            const result = await response.json()

            // Refresh the list to get the new record with ID
            await fetchRecords()

            trackCreate('Travel Order', payload, record.employeeName)
            return result
        } catch (err) {
            error.value = err.message
            console.error('Error adding travel order:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Update existing travel order in database
    async function updateRecord(id, data) {
        loading.value = true
        error.value = null
        try {
            const payload = {
                employee_id: data.employeeId || null,
                employee_no: data.employeeNo,
                employee_name: data.employeeName,
                department: data.department || '',
                destination: data.destination,
                purpose: data.purpose || '',
                date_from: data.dateFrom,
                date_to: data.dateTo,
                days: data.days,
                transport: data.transport || 'Public Transport',
                approved_by: data.approvedBy || null,
                status: data.status || 'Pending',
                remarks: data.remarks || ''
            }

            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to update travel order')
            }

            // Update local state
            const idx = travelOrders.value.findIndex(r => r.id === id)
            if (idx !== -1) {
                const old = { ...travelOrders.value[idx] }
                travelOrders.value[idx] = { ...old, ...data, id }
                trackUpdate('Travel Order', old, travelOrders.value[idx], data.employeeName ?? old.employeeName)
            }

            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error updating travel order:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Delete travel order from database
    async function deleteRecord(id) {
        loading.value = true
        error.value = null
        try {
            const rec = travelOrders.value.find(r => r.id === id)

            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'DELETE'
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.error || 'Failed to delete travel order')
            }

            // Remove from local state
            travelOrders.value = travelOrders.value.filter(r => r.id !== id)

            if (rec) trackDelete('Travel Order', rec, rec.employeeName)

            return await response.json()
        } catch (err) {
            error.value = err.message
            console.error('Error deleting travel order:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        travelOrders,
        loading,
        error,
        fetchRecords,
        addRecord,
        updateRecord,
        deleteRecord
    }
})
