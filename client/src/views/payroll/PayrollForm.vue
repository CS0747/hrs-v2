<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePayrollStore } from '@/stores/payroll'
import { useEmployeeStore } from '@/stores/employees'

const router = useRouter()
const route = useRoute()
const store = usePayrollStore()
const empStore = useEmployeeStore()

const svgIcons = {
  edit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  add: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  save: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  check: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  back: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>`,
}

const isEdit = ref(false)
const saving = ref(false)
const form = ref({
  employeeNo: '',
  employeeName: '',
  position: '',
  department: '',
  period: '2026-04',
  periodLabel: 'April 2026',
  basicSalary: 0,
  pera: 2000,
  rata: 0,
  overtime: 0,
  nightDiff: 0,
  grossPay: 0,
  withholdingTax: 0,
  gsis: 0,
  philhealth: 0,
  pagibig: 100,
  totalDeductions: 0,
  netPay: 0,
  status: 'Pending',
  remarks: '',
})

onMounted(async () => {
  // Fetch employees if not already loaded
  if (empStore.employees.length === 0) {
    await empStore.fetchEmployees()
  }
  
  if (route.params.id) {
    isEdit.value = true
    const rec = store.getById(route.params.id)
    if (rec) Object.assign(form.value, rec)
  }
})

function onEmployeeSelect() {
  const emp = empStore.employees.find(e => e.employeeNo === form.value.employeeNo)
  if (emp) {
    form.value.employeeName = `${emp.lastName}, ${emp.firstName} ${emp.middleName ? emp.middleName[0] + '.' : ''}`
    form.value.position = emp.position
    form.value.department = emp.department
    form.value.basicSalary = emp.salary
    computeAll()
  }
}

function computeAll() {
  const basic = Number(form.value.basicSalary)
  const deductions = store.computeDeductions(basic)
  form.value.gsis = deductions.gsis
  form.value.philhealth = deductions.philhealth
  form.value.pagibig = deductions.pagibig
  form.value.grossPay = basic + Number(form.value.pera) + Number(form.value.rata) +
    Number(form.value.overtime) + Number(form.value.nightDiff)
  form.value.totalDeductions = Number(form.value.withholdingTax) + deductions.total
  form.value.netPay = form.value.grossPay - form.value.totalDeductions
}

watch(() => [form.value.basicSalary, form.value.pera, form.value.rata,
  form.value.overtime, form.value.nightDiff, form.value.withholdingTax], computeAll)

async function submit() {
  saving.value = true
  try {
    if (isEdit.value) {
      await store.updateRecord(Number(route.params.id), form.value)
    } else {
      await store.addRecord(form.value)
    }
    router.push('/payroll')
  } catch (err) {
    alert('Error saving payroll record: ' + err.message)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="form-card">
      <div class="form-header">
        <h2>
          <span class="icon-svg" v-html="isEdit ? svgIcons.edit : svgIcons.add"></span>
          {{ isEdit ? 'Edit Payroll Record' : 'Add Payroll Record' }}
        </h2>
        <button class="btn btn-secondary" @click="router.push('/payroll')">
          <span class="icon-svg" v-html="svgIcons.back"></span> Back
        </button>
      </div>

      <form @submit.prevent="submit">
        <div class="section-title">👤 Employee</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Employee No.</label>
            <AppSelect
              v-model="form.employeeNo"
              :options="[{ label: '-- Select Employee --', value: '' }, ...empStore.employees.map(e => ({ label: e.employeeNo + ' — ' + e.lastName + ', ' + e.firstName, value: e.employeeNo }))]"
              placeholder="-- Select Employee --"
              @update:modelValue="onEmployeeSelect"
            />
          </div>
          <div class="form-group">
            <label>Employee Name</label>
            <input v-model="form.employeeName" readonly />
          </div>
          <div class="form-group">
            <label>Position</label>
            <input v-model="form.position" readonly />
          </div>
          <div class="form-group">
            <label>Department</label>
            <input v-model="form.department" readonly />
          </div>
          <div class="form-group">
            <label>Pay Period</label>
            <AppSelect v-model="form.period" :options="store.payPeriods" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <AppSelect v-model="form.status" :options="['Pending', 'Released', 'On Hold']" />
          </div>
        </div>

        <div class="section-title">💰 Earnings</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Basic Salary (₱)</label>
            <input v-model.number="form.basicSalary" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group">
            <label>PERA (₱)</label>
            <input v-model.number="form.pera" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group">
            <label>RATA (₱)</label>
            <input v-model.number="form.rata" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group">
            <label>Overtime Pay (₱)</label>
            <input v-model.number="form.overtime" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group">
            <label>Night Differential (₱)</label>
            <input v-model.number="form.nightDiff" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group highlight">
            <label>Gross Pay (₱)</label>
            <input v-model.number="form.grossPay" readonly class="computed" />
          </div>
        </div>

        <div class="section-title">➖ Deductions</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Withholding Tax (₱)</label>
            <input v-model.number="form.withholdingTax" type="number" min="0" @input="computeAll" />
          </div>
          <div class="form-group">
            <label>GSIS (₱)</label>
            <input v-model.number="form.gsis" readonly class="computed" />
          </div>
          <div class="form-group">
            <label>PhilHealth (₱)</label>
            <input v-model.number="form.philhealth" readonly class="computed" />
          </div>
          <div class="form-group">
            <label>Pag-IBIG (₱)</label>
            <input v-model.number="form.pagibig" readonly class="computed" />
          </div>
          <div class="form-group highlight">
            <label>Total Deductions (₱)</label>
            <input v-model.number="form.totalDeductions" readonly class="computed" />
          </div>
          <div class="form-group highlight net">
            <label>NET PAY (₱)</label>
            <input v-model.number="form.netPay" readonly class="computed net-input" />
          </div>
        </div>

        <div class="section-title">📝 Remarks</div>
        <div class="form-group full">
          <textarea v-model="form.remarks" rows="2" placeholder="Optional remarks..."></textarea>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="router.push('/payroll')" :disabled="saving">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="saving" class="spinner-small"></span>
            <span v-else class="icon-svg" v-html="isEdit ? svgIcons.save : svgIcons.check"></span>
            {{ saving ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Record') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.form-card {
  background: #fff; border-radius: 12px; padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07); max-width: 900px;
}
.form-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;
}
.form-header h2 { margin: 0; font-size: 20px; color: #1a3a5c; display: flex; align-items: center; gap: 8px; }
.section-title {
  font-size: 14px; font-weight: 700; color: #1a3a5c;
  background: #f0f4f8; padding: 8px 14px; border-radius: 6px;
  margin: 20px 0 14px; border-left: 4px solid #1a3a5c;
}
.form-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;
}
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select, .form-group textarea {
  padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;
  font-size: 13px; outline: none; background: #fff;
}
.form-group input:focus, .form-group select:focus { border-color: #1a3a5c; }
.computed { background: #f0f4f8 !important; font-weight: 700; color: #1a3a5c; }
.net-input { background: #eafaf1 !important; color: #27ae60 !important; font-size: 16px !important; }
.form-actions {
  display: flex; justify-content: flex-end; gap: 12px;
  margin-top: 24px; padding-top: 16px; border-top: 1px solid #f0f4f8;
}
.btn {
  padding: 10px 20px; border-radius: 8px; border: none;
  cursor: pointer; font-size: 13px; font-weight: 600;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-primary { background: #1a3a5c; color: #fff; }
.btn-primary:hover { background: #2980b9; }
.btn-primary:disabled { background: #ccc; cursor: not-allowed; }
.btn-secondary { background: #f0f4f8; color: #1a3a5c; border: 1px solid #ddd; }
.btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
.spinner-small {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
