<script setup>
import { ref, computed } from 'vue'
import { useDTRStore } from '@/stores/dtr'

const dtrStore = useDTRStore()

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  verify: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>`,
  check: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
}

const search = ref('')
const filterStatus = ref('')

const items = computed(() => dtrStore.dtrRecords.filter(r => {
  const q = search.value.toLowerCase()
  const matchSearch = !q || r.employeeName.toLowerCase().includes(q)
  const matchStatus = !filterStatus.value || r.status === filterStatus.value
  return matchSearch && matchStatus
}))

function verify(rec) {
  const verifier = prompt('Enter verifier name:')
  if (verifier) {
    dtrStore.updateRecord(rec.id, {
      verifiedBy: verifier,
      verificationDate: new Date().toISOString().split('T')[0],
      status: 'Verified',
    })
  }
}

function statusClass(s) {
  const map = { Pending: 'badge-orange', Submitted: 'badge-blue', Received: 'badge-green', Verified: 'badge-purple', Returned: 'badge-red' }
  return map[s] || 'badge-gray'
}
</script>

<template>
  <div class="page">
    <div class="info-banner">
      <span class="icon-svg banner-icon" v-html="svgIcons.verify"></span>
      <div>
        <strong>Verification Module</strong>
        <p>Review and verify DTR transmittals, leave forms, and other HR documents before processing.</p>
      </div>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
        <AppSelect
          v-model="filterStatus"
          :options="[{ label: 'All Status', value: '' }, ...dtrStore.statuses.map(s => ({ label: s, value: s }))]"
          placeholder="All Status"
        />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ items.length }} item(s)</span>
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Period</th>
            <th>Type</th><th>Submitted By</th><th>Date Submitted</th>
            <th>Status</th><th>Verified By</th><th>Verification Date</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="10" class="empty-row">No items for verification.</td></tr>
          <tr v-for="r in items" :key="r.id" :class="{ 'unverified-row': !r.verifiedBy }">
            <td><strong>{{ r.employeeName }}</strong><div class="sub-text">{{ r.employeeNo }}</div></td>
            <td>{{ r.department }}</td>
            <td>{{ r.period }}</td>
            <td><span class="badge" :class="r.transmittalType === 'Main' ? 'badge-blue' : 'badge-purple'">{{ r.transmittalType }}</span></td>
            <td>{{ r.submittedBy }}</td>
            <td>{{ r.dateSubmitted }}</td>
            <td><span class="badge" :class="statusClass(r.status)">{{ r.status }}</span></td>
            <td>{{ r.verifiedBy || '—' }}</td>
            <td>{{ r.verificationDate || '—' }}</td>
            <td>
              <button v-if="!r.verifiedBy" class="btn btn-verify" @click="verify(r)">
                <span class="icon-svg" v-html="svgIcons.verify"></span> Verify
              </button>
              <span v-else class="verified-label">
                <span class="icon-svg" v-html="svgIcons.check"></span> Done
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.info-banner {
  display: flex; align-items: flex-start; gap: 14px;
  background: #ebf5fb; border: 1px solid #a9cce3; border-radius: 10px;
  padding: 16px 20px; margin-bottom: 20px; font-size: 14px;
}
.banner-icon { width: 28px; height: 28px; color: #1a3a5c; flex-shrink: 0; }
.banner-icon :deep(svg) { width: 100%; height: 100%; }
.info-banner strong { color: #1a3a5c; }
.info-banner p { margin: 4px 0 0; color: #555; font-size: 13px; }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; }
.search-wrap { position: relative; display: inline-flex; align-items: center; }
.search-icon { position: absolute; left: 10px; color: #aaa; pointer-events: none; }
.search-input { padding: 8px 14px 8px 34px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; width: 240px; outline: none; }
.filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }
.record-count { font-size: 13px; color: #888; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 60vh; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.data-table thead tr { background: #1a3a5c; color: #fff; }
.data-table thead tr th { position: sticky; top: 0; z-index: 2; background: #1a3a5c; }
.data-table th { padding: 11px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.unverified-row { background: #fffde7 !important; }
.sub-text { font-size: 11px; color: #888; }
.badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-orange { background: #fef3e2; color: #e67e22; }
.badge-blue { background: #ebf5fb; color: #2980b9; }
.badge-green { background: #eafaf1; color: #27ae60; }
.badge-purple { background: #f5eef8; color: #8e44ad; }
.badge-red { background: #fdecea; color: #c0392b; }
.badge-gray { background: #f4f4f4; color: #666; }
.btn { padding: 6px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.btn-verify { background: #27ae60; color: #fff; }
.btn-verify:hover { background: #1e8449; }
.verified-label { color: #27ae60; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.empty-row { text-align: center; color: #aaa; padding: 40px; }
</style>
