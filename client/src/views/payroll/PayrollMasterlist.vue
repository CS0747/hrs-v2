<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePayrollStore } from '@/stores/payroll'
import { useRouter } from 'vue-router'
import AppModal from '@/components/AppModal.vue'
import { printPayrollRecords } from '@/utils/print'

const store = usePayrollStore()
const router = useRouter()

// Fetch payroll records on component mount
onMounted(() => {
  store.fetchRecords()
})

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  add: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
}

const search = ref('')
const filterPeriod = ref('2026-04')
const filterStatus = ref('')
const sortBy = ref('employeeName')
const sortDir = ref('asc')

function toggleSort(col) {
  if (sortBy.value === col) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortBy.value = col; sortDir.value = 'asc' }
}

const filtered = computed(() => {
  let list = store.payrollRecords.filter(r => {
    const q = search.value.toLowerCase()
    const matchSearch = !q ||
      r.employeeName.toLowerCase().includes(q) ||
      r.employeeNo.toLowerCase().includes(q)
    const matchPeriod = !filterPeriod.value || r.period === filterPeriod.value
    const matchStatus = !filterStatus.value || r.status === filterStatus.value
    return matchSearch && matchPeriod && matchStatus
  })

  return [...list].sort((a, b) => {
    let va = a[sortBy.value] ?? ''
    let vb = b[sortBy.value] ?? ''
    if (typeof va === 'string') va = va.toLowerCase()
    if (typeof vb === 'string') vb = vb.toLowerCase()
    if (va < vb) return sortDir.value === 'asc' ? -1 : 1
    if (va > vb) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })
})

const totals = computed(() => ({
  gross: filtered.value.reduce((s, r) => s + r.grossPay, 0),
  deductions: filtered.value.reduce((s, r) => s + r.totalDeductions, 0),
  net: filtered.value.reduce((s, r) => s + r.netPay, 0),
}))

function statusClass(s) {
  return s === 'Released' ? 'badge-green' : s === 'Pending' ? 'badge-orange' : 'badge-gray'
}

function deleteRecord(id) {
  deleteTarget.value = store.payrollRecords.find(r => r.id === id)
  showDeleteModal.value = true
}
function confirmDelete() {
  if (deleteTarget.value) store.deleteRecord(deleteTarget.value.id)
  showDeleteModal.value = false
  deleteTarget.value = null
}

const showDeleteModal = ref(false)
const deleteTarget    = ref(null)

function sortIcon(col) {
  if (sortBy.value !== col) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}
</script>

<template>
  <div class="page">
    <!-- Loading State -->
    <div v-if="store.loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading payroll records...</p>
    </div>

    <!-- Error State -->
    <div v-if="store.error" class="error-banner">
      <strong>⚠️ Error:</strong> {{ store.error }}
      <button class="btn-retry" @click="store.fetchRecords()">Retry</button>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
        <AppSelect
          v-model="filterPeriod"
          :options="[{ label: 'All Periods', value: '' }, ...store.payPeriods.map(p => ({ label: p, value: p }))]"
          placeholder="All Periods"
        />
        <AppSelect
          v-model="filterStatus"
          :options="[{ label: 'All Status', value: '' }, { label: 'Released', value: 'Released' }, { label: 'Pending', value: 'Pending' }, { label: 'On Hold', value: 'On Hold' }]"
          placeholder="All Status"
        />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} record(s)</span>
        <button class="btn btn-secondary" @click="printPayrollRecords(filtered, { Period: filterPeriod, Status: filterStatus })">
          🖨 Print
        </button>
        <button class="btn btn-primary" @click="router.push('/payroll/new')">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Record
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-row">
      <div class="summary-card green">
        <div class="sum-label">Total Gross Pay</div>
        <div class="sum-value">₱{{ totals.gross.toLocaleString() }}</div>
      </div>
      <div class="summary-card red">
        <div class="sum-label">Total Deductions</div>
        <div class="sum-value">₱{{ totals.deductions.toLocaleString() }}</div>
      </div>
      <div class="summary-card blue">
        <div class="sum-label">Total Net Pay</div>
        <div class="sum-value">₱{{ totals.net.toLocaleString() }}</div>
      </div>
      <div class="summary-card gray">
        <div class="sum-label">Employees</div>
        <div class="sum-value">{{ filtered.length }}</div>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th @click="toggleSort('employeeNo')" class="sortable">Emp No {{ sortIcon('employeeNo') }}</th>
            <th @click="toggleSort('employeeName')" class="sortable">Name {{ sortIcon('employeeName') }}</th>
            <th>Position / Dept</th>
            <th @click="toggleSort('basicSalary')" class="sortable">Basic {{ sortIcon('basicSalary') }}</th>
            <th>PERA</th>
            <th>OT / ND</th>
            <th @click="toggleSort('grossPay')" class="sortable">Gross {{ sortIcon('grossPay') }}</th>
            <th>Deductions</th>
            <th @click="toggleSort('netPay')" class="sortable">Net Pay {{ sortIcon('netPay') }}</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="11" class="empty-row">No payroll records found.</td>
          </tr>
          <tr v-for="r in filtered" :key="r.id">
            <td><span class="emp-no">{{ r.employeeNo }}</span></td>
            <td>
              <strong>{{ r.employeeName }}</strong>
              <div class="sub-text">{{ r.periodLabel }}</div>
            </td>
            <td>
              <div>{{ r.position }}</div>
              <div class="sub-text">{{ r.department }}</div>
            </td>
            <td>₱{{ r.basicSalary.toLocaleString() }}</td>
            <td>₱{{ r.pera.toLocaleString() }}</td>
            <td>
              <div>OT: ₱{{ r.overtime.toLocaleString() }}</div>
              <div class="sub-text">ND: ₱{{ r.nightDiff.toLocaleString() }}</div>
            </td>
            <td class="amount-cell">₱{{ r.grossPay.toLocaleString() }}</td>
            <td>
              <div class="deduction-breakdown">
                <span>Tax: ₱{{ r.withholdingTax.toLocaleString() }}</span>
                <span>GSIS: ₱{{ r.gsis.toLocaleString() }}</span>
                <span>PhilH: ₱{{ r.philhealth.toLocaleString() }}</span>
                <span>PagIBIG: ₱{{ r.pagibig.toLocaleString() }}</span>
              </div>
            </td>
            <td class="net-pay-cell">₱{{ r.netPay.toLocaleString() }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td>
              <div class="action-btns">
                <button class="btn-icon" @click="router.push(`/payroll/${r.id}/edit`)">
                  <span class="icon-svg" v-html="svgIcons.edit"></span>
                </button>
                <button class="btn-icon danger" @click="deleteRecord(r.id)">
                  <span class="icon-svg" v-html="svgIcons.delete"></span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <AppModal
      v-if="showDeleteModal"
      type="delete"
      title="Delete Payroll Record"
      message="Are you sure you want to delete this payroll record?"
      :detail="deleteTarget?.employeeName + ' — ' + deleteTarget?.periodLabel"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>
<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.search-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 10px;
  color: #aaa;
  pointer-events: none;
}
.search-input {
  padding: 8px 14px 8px 34px; border: 1px solid #ddd; border-radius: 8px;
  font-size: 13px; width: 240px; outline: none;
}
.filter-select {
  padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
  font-size: 13px; outline: none; background: #fff;
}
.record-count { font-size: 13px; color: #888; }
.btn {
  padding: 8px 16px; border-radius: 8px; border: none;
  cursor: pointer; font-size: 13px; font-weight: 600;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-primary { background: #1a3a5c; color: #fff; }
.btn-primary:hover { background: #2980b9; }
.summary-row {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px; margin-bottom: 20px;
}
.summary-card {
  border-radius: 10px; padding: 16px; text-align: center;
}
.summary-card.green { background: #eafaf1; border: 1px solid #a9dfbf; }
.summary-card.red { background: #fdecea; border: 1px solid #f5b7b1; }
.summary-card.blue { background: #ebf5fb; border: 1px solid #a9cce3; }
.summary-card.gray { background: #f4f4f4; border: 1px solid #ddd; }
.sum-label { font-size: 11px; color: #666; margin-bottom: 4px; }
.sum-value { font-size: 20px; font-weight: 800; color: #1a3a5c; }
.table-wrapper { overflow-x: auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table th { padding: 11px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table th.sortable { cursor: pointer; }
.data-table th.sortable:hover { background: #2980b9; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.emp-no { font-family: monospace; font-size: 11px; color: #888; }
.sub-text { font-size: 11px; color: #888; }
.amount-cell { font-weight: 700; color: #27ae60; }
.net-pay-cell { font-weight: 800; color: #1a3a5c; font-size: 13px; }
.deduction-breakdown { display: flex; flex-direction: column; gap: 1px; font-size: 11px; color: #666; }
.badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-green { background: #eafaf1; color: #27ae60; }
.badge-orange { background: #fef3e2; color: #e67e22; }
.badge-gray { background: #f4f4f4; color: #666; }
.action-btns { display: flex; gap: 4px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 3px; border-radius: 4px; display: inline-flex; align-items: center; }
.btn-icon:hover { background: #f0f4f8; }
.btn-icon.danger:hover { background: #fdecea; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }
.loading-container {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px; gap: 16px;
}
.spinner {
  width: 40px; height: 40px; border: 4px solid #f0f4f8;
  border-top-color: #1a3a5c; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.error-banner {
  background: #fdecea; border: 1px solid #f5b7b1;
  color: #c0392b; padding: 14px 18px; border-radius: 8px;
  margin-bottom: 16px; display: flex; align-items: center;
  justify-content: space-between; gap: 12px;
}
.btn-retry {
  padding: 6px 14px; background: #c0392b; color: #fff;
  border: none; border-radius: 6px; cursor: pointer;
  font-size: 12px; font-weight: 600;
}
.btn-retry:hover { background: #a93226; }
</style>
