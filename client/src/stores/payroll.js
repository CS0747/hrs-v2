import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'

const API_URL = 'http://localhost/hrs/server/api/payroll.php'

export const usePayrollStore = defineStore('payroll', () => {
  const payrollRecords = ref([])
  const loading = ref(false)
  const error = ref(null)

  const payPeriods = ['2026-04', '2026-03', '2026-02', '2026-01',
    '2025-12', '2025-11', '2025-10',
  ]

  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  // Fetch all payroll records from database
  async function fetchRecords() {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(API_URL)
      if (!response.ok) throw new Error('Failed to fetch payroll records')
      const data = await response.json()

      // Map database fields to camelCase
      payrollRecords.value = data.map(record => ({
        id: record.id,
        employeeId: record.employee_id,
        employeeNo: record.employee_no,
        employeeName: record.employee_name,
        position: record.position,
        department: record.department,
        period: record.period,
        periodLabel: record.period_label,
        basicSalary: parseFloat(record.basic_salary),
        pera: parseFloat(record.pera),
        rata: parseFloat(record.rata),
        overtime: parseFloat(record.overtime),
        nightDiff: parseFloat(record.night_diff),
        grossPay: parseFloat(record.gross_pay),
        withholdingTax: parseFloat(record.withholding_tax),
        gsis: parseFloat(record.gsis),
        philhealth: parseFloat(record.philhealth),
        pagibig: parseFloat(record.pagibig),
        totalDeductions: parseFloat(record.total_deductions),
        netPay: parseFloat(record.net_pay),
        status: record.status,
        remarks: record.remarks || ''
      }))
    } catch (err) {
      error.value = err.message
      console.error('Error fetching payroll records:', err)
    } finally {
      loading.value = false
    }
  }

  // Add new payroll record to database
  async function addRecord(record) {
    loading.value = true
    error.value = null
    try {
      const payload = {
        employee_id: record.employeeId || null,
        employee_no: record.employeeNo,
        employee_name: record.employeeName,
        position: record.position || '',
        department: record.department || '',
        period: record.period,
        period_label: record.periodLabel || record.period,
        basic_salary: record.basicSalary,
        pera: record.pera || 0,
        rata: record.rata || 0,
        overtime: record.overtime || 0,
        night_diff: record.nightDiff || 0,
        gross_pay: record.grossPay,
        withholding_tax: record.withholdingTax || 0,
        gsis: record.gsis || 0,
        philhealth: record.philhealth || 0,
        pagibig: record.pagibig || 0,
        total_deductions: record.totalDeductions,
        net_pay: record.netPay,
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
        throw new Error(errorData.error || 'Failed to add payroll record')
      }

      const result = await response.json()

      // Refresh the list to get the new record with ID
      await fetchRecords()

      trackCreate('Payroll', payload, record.employeeName)
      return result
    } catch (err) {
      error.value = err.message
      console.error('Error adding payroll record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update existing payroll record in database
  async function updateRecord(id, data) {
    loading.value = true
    error.value = null
    try {
      const payload = {
        employee_id: data.employeeId || null,
        employee_no: data.employeeNo,
        employee_name: data.employeeName,
        position: data.position || '',
        department: data.department || '',
        period: data.period,
        period_label: data.periodLabel || data.period,
        basic_salary: data.basicSalary,
        pera: data.pera || 0,
        rata: data.rata || 0,
        overtime: data.overtime || 0,
        night_diff: data.nightDiff || 0,
        gross_pay: data.grossPay,
        withholding_tax: data.withholdingTax || 0,
        gsis: data.gsis || 0,
        philhealth: data.philhealth || 0,
        pagibig: data.pagibig || 0,
        total_deductions: data.totalDeductions,
        net_pay: data.netPay,
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
        throw new Error(errorData.error || 'Failed to update payroll record')
      }

      // Update local state
      const idx = payrollRecords.value.findIndex(r => r.id === id)
      if (idx !== -1) {
        const old = { ...payrollRecords.value[idx] }
        payrollRecords.value[idx] = { ...old, ...data, id }
        trackUpdate('Payroll', old, payrollRecords.value[idx], data.employeeName ?? old.employeeName)
      }

      return await response.json()
    } catch (err) {
      error.value = err.message
      console.error('Error updating payroll record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete payroll record from database
  async function deleteRecord(id) {
    loading.value = true
    error.value = null
    try {
      const rec = payrollRecords.value.find(r => r.id === id)

      const response = await fetch(`${API_URL}?id=${id}`, {
        method: 'DELETE'
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Failed to delete payroll record')
      }

      // Remove from local state
      payrollRecords.value = payrollRecords.value.filter(r => r.id !== id)

      if (rec) trackDelete('Payroll', rec, rec.employeeName)

      return await response.json()
    } catch (err) {
      error.value = err.message
      console.error('Error deleting payroll record:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function getById(id) {
    return payrollRecords.value.find(r => r.id === Number(id))
  }

  function computeDeductions(basic) {
    const gsis = Math.round(basic * 0.09)
    const philhealth = Math.round(basic * 0.02)
    const pagibig = 100
    return { gsis, philhealth, pagibig, total: gsis + philhealth + pagibig }
  }

  return {
    payrollRecords,
    payPeriods,
    loading,
    error,
    fetchRecords,
    addRecord,
    updateRecord,
    deleteRecord,
    getById,
    computeDeductions,
  }
})
