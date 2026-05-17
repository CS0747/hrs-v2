import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useVersionHistory } from '@/composables/useVersionHistory'
import { API_ENDPOINTS } from '@/config/api'

export const useEmployeeStore = defineStore('employees', () => {
  const employees = ref([])
  const { trackCreate, trackUpdate, trackDelete } = useVersionHistory()

  // Departments loaded from DB via fetchDepartments() below

  const positions = [
    'Medical Officer I', 'Medical Officer II', 'Medical Officer III', 'Medical Officer IV',
    'Nurse I', 'Nurse II', 'Nurse III', 'Nurse IV',
    'Medical Technologist I', 'Medical Technologist II',
    'Radiologic Technologist I', 'Radiologic Technologist II',
    'Pharmacist I', 'Pharmacist II',
    'Administrative Aide IV', 'Administrative Aide VI',
    'Administrative Officer I', 'Administrative Officer II',
    'Accountant I', 'Accountant II',
    'Utility Worker I', 'Security Guard I',
  ]

  const employmentStatuses = ['Permanent', 'Casual', 'Contractual', 'Job Order', 'Co-terminus', 'Part Time']

  const API = API_ENDPOINTS.EMPLOYEES
  const DEPT_API = API_ENDPOINTS.DEPARTMENTS
  const loading = ref(false)
  const error = ref(null)

  // Departments — loaded from DB, fallback to empty until API responds
  const departments = ref([])

  async function fetchDepartments() {
    try {
      const res = await fetch(DEPT_API)
      if (!res.ok) throw new Error('Failed to fetch departments')
      const rows = await res.json()
      if (Array.isArray(rows) && rows.length > 0) {
        departments.value = rows.map(r => r.name)
      }
    } catch (e) {
      console.error('Departments API error:', e.message)
    }
  }

  // Load all employees from DB � replaces in-memory list
  async function fetchEmployees() {
    try {
      loading.value = true
      const res = await fetch(API)
      if (!res.ok) throw new Error('Failed to fetch')
      const rows = await res.json()
      if (Array.isArray(rows) && rows.length > 0) {
        // Map snake_case DB columns back to camelCase for the frontend
        employees.value = rows.map(r => ({
          id: r.id,
          employeeNo: r.employeeNo,
          lastName: r.lastName,
          firstName: r.firstName,
          middleName: r.middleName ?? '',
          position: r.position ?? '',
          designation: r.designation ?? '',
          department: r.department ?? '',
          employmentStatus: r.employmentStatus ?? 'Casual',
          dateHired: r.dateHired ?? '',
          birthDate: r.birthDate ?? '',
          age: r.age ?? 0,
          gender: r.gender ?? '',
          civilStatus: r.civilStatus ?? '',
          address: r.address ?? '',
          contactNo: r.contactNo ?? '',
          email: r.email ?? '',
          salary: Number(r.salary) || 0,
          sgStep: r.sgStep ?? '',
          tin: r.tin ?? '',
          sss: r.sss ?? '',
          philhealth: r.philhealth ?? '',
          pagibig: r.pagibig ?? '',
          active: r.active == 1,
        }))
      }
    } catch (e) {
      error.value = e.message
      console.warn('employees API unavailable, using local data:', e.message)
    } finally {
      loading.value = false
    }
  }

  // Call on store init
  fetchEmployees()
  fetchDepartments()

  async function addEmployee(emp) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(emp),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Insert failed')
    await fetchEmployees()
    const created = employees.value.find(e => e.employeeNo === emp.employeeNo) ?? { ...emp, id: json.id }
    trackCreate('Employee', created, `${emp.lastName}, ${emp.firstName}`)
  }

  async function updateEmployee(id, data) {
    const oldRecord = employees.value.find(e => e.id === id)
    const res = await fetch(`${API}?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Update failed')
    await fetchEmployees()
    const newRecord = employees.value.find(e => e.id === id) ?? { ...data, id }
    trackUpdate('Employee', oldRecord, newRecord, `${data.lastName ?? oldRecord?.lastName}, ${data.firstName ?? oldRecord?.firstName}`)
  }

  async function deleteEmployee(id) {
    const record = employees.value.find(e => e.id === id)
    try {
      const res = await fetch(`${API}?id=${id}`, { method: 'DELETE' })
      const json = await res.json()
      if (!res.ok) throw new Error(json.error || 'Delete failed')
      if (record) trackDelete('Employee', record, `${record.lastName}, ${record.firstName}`)
    } catch (e) {
      console.error('deleteEmployee error:', e.message)
    }
    employees.value = employees.value.filter(e => e.id !== id)
  }

  function getById(id) {
    return employees.value.find(e => e.id === Number(id))
  }

  const currentMonth = new Date().getMonth() + 1

  const birthdayCelebrantsThisMonth = computed(() =>
    employees.value.filter(e => {
      const month = new Date(e.birthDate).getMonth() + 1
      return month === currentMonth && e.active
    })
  )

  const turning65ThisYear = computed(() => {
    const year = new Date().getFullYear()
    return employees.value.filter(e => {
      const birthYear = new Date(e.birthDate).getFullYear()
      return year - birthYear === 65 && e.active
    })
  })

  return {
    employees,
    departments,
    positions,
    employmentStatuses,
    loading,
    error,
    fetchEmployees,
    fetchDepartments,
    addEmployee,
    updateEmployee,
    deleteEmployee,
    getById,
    birthdayCelebrantsThisMonth,
    turning65ThisYear,
  }
})




