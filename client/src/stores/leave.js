import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API_URL = 'http://localhost/hrs-v2/server/api/leave.php'

export const useLeaveStore = defineStore('leave', () => {
  const leaveRecords = ref([])
  const loading = ref(false)
  const error = ref(null)

  const leaveTypes = [
    'Vacation Leave',
    'Sick Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Special Leave',
    'Emergency Leave',
    'Forced Leave',
    'Study Leave',
    'Rehabilitation Leave',
    'Terminal Leave'
  ]

  const statuses = ['Pending', 'Approved', 'Disapproved', 'Cancelled']

  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  // Fetch all leave records from database
  async function fetchRecords() {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(API_URL)
      if (!response.ok) throw new Error('Failed to fetch leave records')
      const data = await response.json()

      // Data is already mapped to camelCase by backend
      leaveRecords.value = data.map(record => ({
        id: record.id,
        employeeId: record.employeeId,
        employeeNo: record.employeeNo,
        employeeName: record.employeeName,
        department: record.department,
        leaveType: record.leaveType,
        dateFrom: record.dateFrom,
        dateTo: record.dateTo,
        days: record.days,
        reason: record.reason,
        status: record.status,
        approvedBy: record.approvedBy,
        dateApproved: record.dateApproved,
        remarks: record.remarks
      }))
    } catch (err) {
      error.value = err.message
      console.error('Error fetching leave records:', err)
    } finally {
      loading.value = false
    }
  }

  // Add new leave record to database
  async function addRecord(record) {
    loading.value = true
    error.value = null
    try {
      const payload = {
        employee_id: record.employeeId || null,
        employee_no: record.employeeNo,
        employee_name: record.employeeName,
        department: record.department || '',
        leave_type: record.leaveType,
        date_from: record.dateFrom,
        date_to: record.dateTo,
        days: record.days,
        reason: record.reason || '',
        status: record.status || 'Pending',
        approved_by: record.approvedBy || null,
        date_approved: record.dateApproved || null,
        remarks: record.remarks || ''
      }

      const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Failed to add leave record')
      }

      const result = await response.json()

      // Refresh the list to get the new record with ID
      await fetchRecords()

      trackCreate('Leave', payload, record.employeeName)
      return result
    } catch (err) {
      error.value = err.message
      console.error('Error adding leave record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update existing leave record in database
  async function updateRecord(id, data) {
    loading.value = true
    error.value = null
    try {
      const payload = {
        employee_id: data.employeeId || null,
        employee_no: data.employeeNo,
        employee_name: data.employeeName,
        department: data.department || '',
        leave_type: data.leaveType,
        date_from: data.dateFrom,
        date_to: data.dateTo,
        days: data.days,
        reason: data.reason || '',
        status: data.status || 'Pending',
        approved_by: data.approvedBy || null,
        date_approved: data.dateApproved || null,
        remarks: data.remarks || ''
      }

      const response = await fetch(`${API_URL}?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Failed to update leave record')
      }

      // Update local state
      const idx = leaveRecords.value.findIndex(r => r.id === id)
      if (idx !== -1) {
        const old = { ...leaveRecords.value[idx] }
        leaveRecords.value[idx] = { ...old, ...data, id }
        trackUpdate('Leave', old, leaveRecords.value[idx], data.employeeName ?? old.employeeName)
      }

      return await response.json()
    } catch (err) {
      error.value = err.message
      console.error('Error updating leave record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete leave record from database
  async function deleteRecord(id) {
    loading.value = true
    error.value = null
    try {
      const rec = leaveRecords.value.find(r => r.id === id)

      const response = await fetch(`${API_URL}?id=${id}`, {
        method: 'DELETE'
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Failed to delete leave record')
      }

      // Remove from local state
      leaveRecords.value = leaveRecords.value.filter(r => r.id !== id)

      if (rec) trackDelete('Leave', rec, rec.employeeName)

      return await response.json()
    } catch (err) {
      error.value = err.message
      console.error('Error deleting leave record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    leaveRecords,
    leaveTypes,
    statuses,
    loading,
    error,
    fetchRecords,
    addRecord,
    updateRecord,
    deleteRecord
  }
})
