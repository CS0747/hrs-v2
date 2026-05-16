<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useEmployeeStore } from '@/stores/employees'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route  = useRoute()
const store  = useEmployeeStore()
const auth   = useAuthStore()

// Section Admin cannot add or edit employees — redirect back
onMounted(() => {
  if (!auth.canEdit('employees')) {
    router.replace('/employees')
  }
})

const isEdit = ref(false)
const errors = ref({})
const submitting = ref(false)
const loadingForm = ref(false)
const showConfirmModal = ref(false)

const svgIcons = {
  edit:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  add:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  save:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  check: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  back:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>`,
  info:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`,
}

const form = ref({
  employeeNo: '',
  lastName: '',
  firstName: '',
  middleName: '',
  position: '',
  department: '',
  employmentStatus: 'Permanent',
  dateHired: '',
  birthDate: '',
  gender: 'Male',
  civilStatus: 'Single',
  address: '',
  contactNo: '',
  email: '',
  salary: 0,
  sgStep: '',
  tin: '',
  sss: '',
  philhealth: '',
  pagibig: '',
  active: true,
})

onMounted(async () => {
  if (route.params.id) {
    isEdit.value = true
    loadingForm.value = true
    try {
      // Load employee for edit – first try the Pinia store, then fall back to API (camelCase)
      const emp = store.getById(route.params.id)
      if (emp) {
        Object.assign(form.value, { ...emp })
      } else {
        const res = await fetch(`http://localhost/hrs-v2/server/api/employees.php?id=${route.params.id}`)
        if (!res.ok) throw new Error('Failed to load employee')
        const r = await res.json()
        Object.assign(form.value, {
          employeeNo:       r.employeeNo       ?? '',
          lastName:         r.lastName         ?? '',
          firstName:        r.firstName        ?? '',
          middleName:       r.middleName       ?? '',
          position:         r.position         ?? '',
          designation:      r.designation      ?? '',
          department:       r.department       ?? '',
          employmentStatus: r.employmentStatus ?? 'Permanent',
          dateHired:        r.dateHired        ?? '',
          birthDate:        r.birthDate        ?? '',
          gender:           r.gender           ?? 'Male',
          civilStatus:      r.civilStatus      ?? 'Single',
          address:          r.address          ?? '',
          contactNo:        r.contactNo        ?? '',
          email:            r.email            ?? '',
          salary:           Number(r.salary)   || 0,
          sgStep:           r.sgStep           ?? '',
          tin:              r.tin              ?? '',
          sss:              r.sss              ?? '',
          philhealth:       r.philhealth       ?? '',
          pagibig:          r.pagibig          ?? '',
          active:           r.active == 1,
        })
      }
      const res = await fetch(`http://localhost/hrs-v2/server/api/employees.php?id=${route.params.id}`)
      if (!res.ok) throw new Error('Failed to load employee')
      const r = await res.json()
      Object.assign(form.value, {
        employeeNo:       r.employee_no       ?? '',
        lastName:         r.last_name         ?? '',
        firstName:        r.first_name        ?? '',
        middleName:       r.middle_name       ?? '',
        position:         r.position          ?? '',
        designation:      r.designation       ?? '',
        department:       r.department        ?? '',
        employmentStatus: r.employment_status ?? 'Permanent',
        dateHired:        r.date_hired        ?? '',
        birthDate:        r.birth_date        ?? '',
        gender:           r.gender            ?? 'Male',
        civilStatus:      r.civil_status      ?? 'Single',
        address:          r.address           ?? '',
        contactNo:        r.contact_no        ?? '',
        email:            r.email             ?? '',
        salary:           Number(r.salary)    || 0,
        sgStep:           r.sg_step           ?? '',
        tin:              r.tin_number        ?? '',
        sss:              r.sss_gsis_number   ?? '',
        philhealth:       r.phil_number       ?? '',
        pagibig:          r.pi_number         ?? '',
        active:           r.active == 1,
      })
    } catch (e) {
      // Fallback to store if API call fails
      const emp = store.getById(route.params.id)
      if (emp) Object.assign(form.value, emp)
    } finally {
      loadingForm.value = false
    }
  }
})

// ── Input restriction handlers ──────────────────────────────────────────────

function onlyLetters(e) {
  const char = e.key
  if (!/^[a-zA-ZÀ-ÿ\s\-\.'ñÑ]$/.test(char) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(char)) {
    e.preventDefault()
  }
}

function onlyNumbers(e) {
  if (!/^\d$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}

function numbersAndHyphens(e) {
  if (!/^[\d\-]$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}

function alphanumericHyphen(e) {
  if (!/^[a-zA-Z0-9\-]$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}

function onlyPhone(e) {
  if (!/^\d$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
  if (form.value.contactNo.length >= 11 && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}

// ── Validation ───────────────────────────────────────────────────────────────
function validate() {
  errors.value = {}
  if (!form.value.employeeNo) errors.value.employeeNo = 'Required'
  if (!form.value.lastName) errors.value.lastName = 'Required'
  if (!form.value.firstName) errors.value.firstName = 'Required'
  if (form.value.contactNo && !/^09\d{9}$/.test(form.value.contactNo)) {
    errors.value.contactNo = 'Must be 11 digits starting with 09'
  }
  if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Invalid email format'
  }
  if (form.value.tin && !/^\d{3}-\d{3}-\d{3}$/.test(form.value.tin)) {
    errors.value.tin = 'Format: XXX-XXX-XXX'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (submitting.value) return
  if (!validate()) {
    alert('Please fix the errors before saving.')
    return
  }
  // For edit mode, show confirmation modal first
  if (isEdit.value) {
    showConfirmModal.value = true
    return
  }
  await doSave()
}

function cancelConfirm() {
  showConfirmModal.value = false
}

async function doSave() {
  showConfirmModal.value = false
  submitting.value = true
  try {
    if (isEdit.value) {
      await store.updateEmployee(Number(route.params.id), form.value)
      auth.addLog('Employee Updated', 'Employee', `Employee ${form.value.lastName}, ${form.value.firstName} updated.`)
    } else {
      await store.addEmployee(form.value)
      auth.addLog('Employee Added', 'Employee', `New employee ${form.value.lastName}, ${form.value.firstName} added.`)
    }
    router.push('/employees')
  } catch (e) {
    alert('Failed to save employee: ' + e.message)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="form-card">
      <div class="form-header">
        <h2>
          <span class="icon-svg" v-html="isEdit ? svgIcons.edit : svgIcons.add"></span>
          {{ isEdit ? 'Edit Employee' : 'Add New Employee' }}
        </h2>
        <button class="btn btn-secondary" @click="router.push('/employees')">
          <span class="icon-svg" v-html="svgIcons.back"></span> Back
        </button>
      </div>

      <form @submit.prevent="submit" novalidate>
        <!-- Loading overlay for edit mode -->
        <div v-if="loadingForm" class="form-loading">Loading employee data...</div>
        <template v-else>
        <!-- Personal Information -->
        <div class="section-title">👤 Personal Information</div>
        <div class="form-grid">

          <div class="form-group">
            <label>Employee No. <span class="req">*</span></label>
            <input
              v-model="form.employeeNo"
              placeholder="GEAMH-XXX"
              @keydown="alphanumericHyphen"
              maxlength="15"
              :class="{ error: errors.employeeNo }"
            />
            <span v-if="errors.employeeNo" class="err-msg">{{ errors.employeeNo }}</span>
            <span class="hint">Letters, numbers, hyphens only</span>
          </div>

          <div class="form-group">
            <label>Last Name <span class="req">*</span></label>
            <input
              v-model="form.lastName"
              @keydown="onlyLetters"
              maxlength="50"
              :class="{ error: errors.lastName }"
            />
            <span v-if="errors.lastName" class="err-msg">{{ errors.lastName }}</span>
            <span class="hint">Letters only</span>
          </div>

          <div class="form-group">
            <label>First Name <span class="req">*</span></label>
            <input
              v-model="form.firstName"
              @keydown="onlyLetters"
              maxlength="50"
              :class="{ error: errors.firstName }"
            />
            <span v-if="errors.firstName" class="err-msg">{{ errors.firstName }}</span>
            <span class="hint">Letters only</span>
          </div>

          <div class="form-group">
            <label>Middle Name</label>
            <input
              v-model="form.middleName"
              @keydown="onlyLetters"
              maxlength="50"
            />
            <span class="hint">Letters only</span>
          </div>

          <div class="form-group">
            <label>Birth Date</label>
            <input v-model="form.birthDate" type="date" />
          </div>

          <div class="form-group">
            <label>Gender</label>
            <AppSelect v-model="form.gender" :options="['Male', 'Female']" />
          </div>

          <div class="form-group">
            <label>Civil Status</label>
            <AppSelect v-model="form.civilStatus" :options="['Single', 'Married', 'Widowed', 'Separated']" />
          </div>

          <div class="form-group full">
            <label>Address</label>
            <input v-model="form.address" maxlength="200" placeholder="Street, Barangay, City, Province" />
          </div>

          <div class="form-group">
            <label>Contact No.</label>
            <input
              v-model="form.contactNo"
              placeholder="09XXXXXXXXX"
              @keydown="onlyPhone"
              maxlength="11"
              :class="{ error: errors.contactNo }"
            />
            <span v-if="errors.contactNo" class="err-msg">{{ errors.contactNo }}</span>
            <span class="hint">11 digits, starts with 09</span>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="name@geamh.gov.ph"
              maxlength="100"
              :class="{ error: errors.email }"
            />
            <span v-if="errors.email" class="err-msg">{{ errors.email }}</span>
          </div>

        </div>

        <!-- Employment Information -->
        <div class="section-title">💼 Employment Information</div>
        <div class="form-grid">

          <div class="form-group">
            <label>Position</label>
            <AppSelect
              v-model="form.position"
              :options="[{ label: '-- Select --', value: '' }, ...store.positions.map(p => ({ label: p, value: p }))]"
              placeholder="-- Select --"
            />
          </div>

          <div class="form-group">
            <label>Department</label>
            <AppSelect
              v-model="form.department"
              :options="[{ label: '-- Select --', value: '' }, ...store.departments.map(d => ({ label: d, value: d }))]"
              placeholder="-- Select --"
            />
          </div>

          <div class="form-group">
            <label>Employment Status</label>
            <AppSelect v-model="form.employmentStatus" :options="store.employmentStatuses" />
          </div>

          <div class="form-group">
            <label>Date Hired</label>
            <input v-model="form.dateHired" type="date" />
          </div>

          <div class="form-group">
            <label>Salary Grade / Step</label>
            <input v-model="form.sgStep" placeholder="SG-XX Step X" maxlength="20" />
          </div>

          <div class="form-group">
            <label>Monthly Salary (₱)</label>
            <input
              v-model.number="form.salary"
              type="number"
              min="0"
              max="9999999"
              @keydown="onlyNumbers"
            />
            <span class="hint">Numbers only</span>
          </div>

        </div>

        <!-- Government IDs -->
        <div class="section-title">🪪 Government IDs</div>
        <div class="form-grid">

          <div class="form-group">
            <label>TIN</label>
            <input
              v-model="form.tin"
              placeholder="XXX-XXX-XXX"
              @keydown="numbersAndHyphens"
              maxlength="15"
              :class="{ error: errors.tin }"
            />
            <span v-if="errors.tin" class="err-msg">{{ errors.tin }}</span>
            <span class="hint">Format: XXX-XXX-XXX</span>
          </div>

          <div class="form-group">
            <label>SSS / GSIS No.</label>
            <input
              v-model="form.sss"
              @keydown="numbersAndHyphens"
              maxlength="20"
              placeholder="XX-XXXXXXX-X"
            />
            <span class="hint">Numbers and hyphens only</span>
          </div>

          <div class="form-group">
            <label>PhilHealth No.</label>
            <input
              v-model="form.philhealth"
              @keydown="numbersAndHyphens"
              maxlength="20"
              placeholder="XX-XXXXXXXXX-X"
            />
            <span class="hint">Numbers and hyphens only</span>
          </div>

          <div class="form-group">
            <label>Pag-IBIG No.</label>
            <input
              v-model="form.pagibig"
              @keydown="numbersAndHyphens"
              maxlength="20"
              placeholder="XXXX-XXXX-XXXX"
            />
            <span class="hint">Numbers and hyphens only</span>
          </div>

        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="router.push('/employees')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            <span class="icon-svg" v-html="isEdit ? svgIcons.save : svgIcons.check"></span>
            {{ submitting ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Employee') }}
          </button>
        </div>
        </template>
      </form>
    </div>
  </div>

  <!-- ── Update Confirmation Modal ─────────────────────────────────────── -->
  <Transition name="modal">
    <div v-if="showConfirmModal" class="modal-overlay" @click.self="cancelConfirm">
      <div class="modal">
        <div class="modal-icon-wrap">
          <span class="modal-icon" v-html="svgIcons.info"></span>
        </div>
        <h3 class="modal-title">Save Changes</h3>
        <p class="modal-message">Are you sure you want to update this employee's information?</p>
        <div class="modal-employee-card">
          <div class="modal-emp-avatar">
            {{ form.firstName?.[0] }}{{ form.lastName?.[0] }}
          </div>
          <div class="modal-emp-info">
            <strong>{{ form.lastName }}, {{ form.firstName }}</strong>
            <span>{{ form.position || '—' }} &bull; {{ form.department || '—' }}</span>
            <span class="modal-emp-no">{{ form.employeeNo }}</span>
          </div>
        </div>
        <p class="modal-note">All changes will be saved to the database.</p>
        <div class="modal-actions">
          <button class="btn btn-cancel" @click="cancelConfirm" :disabled="submitting">Cancel</button>
          <button class="btn btn-confirm" @click="doSave" :disabled="submitting">
            <span class="modal-icon-sm" v-html="svgIcons.save"></span>
            {{ submitting ? 'Saving...' : 'Yes, Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.form-card {
  background: #fff;
  border-radius: 12px;
  padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  max-width: 900px;
}
.form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.form-header h2 { margin: 0; font-size: 20px; color: #1a6b3c; display: flex; align-items: center; gap: 8px; }
.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #1a6b3c;
  background: #e8f5ee;
  padding: 8px 14px;
  border-radius: 6px;
  margin: 20px 0 14px;
  border-left: 4px solid #1a6b3c;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px;
}
.form-group { display: flex; flex-direction: column; gap: 3px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  background: #fff;
  transition: border-color 0.2s;
}
.form-group input:focus, .form-group select:focus { border-color: #1a6b3c; }
.form-group input.error { border-color: #c0392b; background: #fff8f8; }
.hint { font-size: 10px; color: #aaa; }
.err-msg { font-size: 11px; color: #c0392b; font-weight: 600; }
.req { color: #c0392b; }
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f0f4f8;
}
.btn {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.btn-primary { background: #1a6b3c; color: #fff; }
.btn-primary:hover { background: #27ae60; }
.btn-primary:disabled { background: #a0c4b0; cursor: not-allowed; }
.btn-secondary { background: #f0f4f8; color: #1a6b3c; border: 1px solid #ddd; }
.form-loading {
  text-align: center;
  padding: 40px;
  color: #888;
  font-size: 14px;
}

/* ── Update Confirmation Modal ── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}
.modal {
  background: #fff;
  border-radius: 16px;
  padding: 32px 28px 24px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
}
.modal-icon-wrap {
  width: 56px; height: 56px; border-radius: 50%;
  background: #e8f5ee;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
}
.modal-icon { width: 28px; height: 28px; }
.modal-icon :deep(svg) { width: 28px; height: 28px; fill: #1a6b3c; }
.modal-icon-sm { display: inline-flex; align-items: center; width: 16px; height: 16px; }
.modal-icon-sm :deep(svg) { width: 16px; height: 16px; fill: #fff; }
.modal-title { margin: 0; font-size: 18px; font-weight: 700; color: #1a1a2e; }
.modal-message { margin: 0; font-size: 14px; color: #555; }
.modal-employee-card {
  display: flex; align-items: center; gap: 12px;
  background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 10px; padding: 12px 16px;
  width: 100%; text-align: left;
}
.modal-emp-avatar {
  width: 42px; height: 42px; border-radius: 50%;
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.modal-emp-info { display: flex; flex-direction: column; gap: 2px; }
.modal-emp-info strong { font-size: 14px; color: #1a1a2e; }
.modal-emp-info span { font-size: 12px; color: #888; }
.modal-emp-no { font-family: monospace; font-size: 11px; color: #aaa !important; }
.modal-note { margin: 0; font-size: 12px; color: #1a6b3c; font-weight: 600; }
.modal-actions {
  display: flex; gap: 10px; width: 100%; margin-top: 4px;
}
.btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #f0f4f8; color: #555;
  border: 1px solid #ddd; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.2s;
}
.btn-cancel:hover:not(:disabled) { background: #e0e8f0; }
.btn-cancel:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-confirm {
  flex: 1; padding: 10px; border-radius: 8px;
  background: #1a6b3c; color: #fff;
  border: none; font-size: 13px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center;
  justify-content: center; gap: 6px; transition: background 0.2s;
}
.btn-confirm:hover:not(:disabled) { background: #27ae60; }
.btn-confirm:disabled { background: #a0c4b0; cursor: not-allowed; }

/* ── Modal transition ── */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform: scale(0.95); opacity: 0; }
</style>
