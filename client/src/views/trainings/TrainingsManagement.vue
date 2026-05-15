<script setup>
import { ref, computed, onMounted } from 'vue'
import { useEmployeeStore } from '@/stores/employees'
import { usePermissions } from '@/composables/usePermissions'

const empStore = useEmployeeStore()
const { hasPermission, loadPermissions } = usePermissions()
const API = 'http://localhost/hrs-v2/server/api/trainings.php'

// ── State ────────────────────────────────────────────────────────────────────
const trainings    = ref([])
const loading      = ref(false)
const search       = ref('')
const filterCat    = ref('')
const filterStatus = ref('')

const categories = ['Medical', 'Nursing', 'Administrative', 'Technical', 'Leadership', 'Safety', 'Other']
const statuses   = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled']

// ── Training form modal ───────────────────────────────────────────────────────
const showForm      = ref(false)
const editId        = ref(null)
const saving        = ref(false)
const formErrors    = ref({})
const showConfirm   = ref(false)
// ── Delete modal state ───────────────────────────────────────────────────────
const showDeleteModal    = ref(false)
const deleteTarget       = ref(null)   // { type: 'training'|'participant', data }
const deletingConfirmed  = ref(false)

function blankForm() {
  return { title: '', category: 'Medical', instructor: '', venue: '',
           dateFrom: '', dateTo: '', duration: 1, maxParticipants: 30,
           status: 'Upcoming', description: '' }
}
const form = ref(blankForm())

// ── Participants panel ────────────────────────────────────────────────────────
const selectedTraining  = ref(null)
const participants      = ref([])
const loadingParts      = ref(false)
const showAddParticipant = ref(false)
const empSearch         = ref('')
const empDropOpen       = ref(false)
const addingParticipants = ref([])   // staged list before saving
const savingParts       = ref(false)

// ── Icons ─────────────────────────────────────────────────────────────────────
const icons = {
  add:     '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
  edit:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
  delete:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
  save:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>',
  close:   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
  search:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
  person:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>',
  check:   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
  training:'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>',
}

// ── Fetch trainings ───────────────────────────────────────────────────────────
async function fetchTrainings() {
  loading.value = true
  try {
    const res  = await fetch(API)
    const rows = await res.json()
    trainings.value = Array.isArray(rows) ? rows.map(r => ({
      id:              r.id,
      title:           r.title,
      category:        r.category        ?? 'Medical',
      instructor:      r.instructor      ?? '',
      venue:           r.venue           ?? '',
      dateFrom:        r.date_from       ?? '',
      dateTo:          r.date_to         ?? '',
      duration:        Number(r.duration)         || 1,
      maxParticipants: Number(r.max_participants) || 30,
      enrolled:        Number(r.enrolled)         || 0,
      status:          r.status          ?? 'Upcoming',
      description:     r.description     ?? '',
    })) : []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

onMounted(async () => {
  await loadPermissions()
  fetchTrainings()
})

// ── Filtered cards ────────────────────────────────────────────────────────────
const filtered = computed(() => trainings.value.filter(t => {
  const q = search.value.toLowerCase()
  const ms = !q || t.title.toLowerCase().includes(q) || t.instructor.toLowerCase().includes(q) || t.venue.toLowerCase().includes(q)
  const mc = !filterCat.value    || t.category === filterCat.value
  const mst= !filterStatus.value || t.status   === filterStatus.value
  return ms && mc && mst
}))

// ── Training CRUD ─────────────────────────────────────────────────────────────
function openAdd() {
  editId.value = null; form.value = blankForm(); formErrors.value = {}; showForm.value = true
}
function openEdit(t) {
  editId.value = t.id; form.value = { ...t }; formErrors.value = {}; showForm.value = true
}

async function save() {
  formErrors.value = {}
  if (!form.value.title.trim()) { formErrors.value.title = 'Required'; return }
  if (!form.value.dateFrom)     { formErrors.value.dateFrom = 'Required'; return }
  // Show confirmation only for updates
  if (editId.value) { showConfirm.value = true; return }
  await doSave()
}

async function doSave() {
  showConfirm.value = false
  saving.value = true
  try {
    const url    = editId.value ? `${API}?id=${editId.value}` : API
    const method = editId.value ? 'PUT' : 'POST'
    const res    = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form.value) })
    const json   = await res.json()
    if (!res.ok) throw new Error(json.error || 'Save failed')
    showForm.value = false
    await fetchTrainings()
    if (selectedTraining.value?.id === editId.value) {
      selectedTraining.value = trainings.value.find(t => t.id === editId.value) ?? null
    }
  } catch (e) { alert('Failed: ' + e.message) }
  finally { saving.value = false }
}

async function deleteTraining(t) {
  deleteTarget.value = { type: 'training', data: t }
  showDeleteModal.value = true
}

async function removeParticipant(p) {
  deleteTarget.value = { type: 'participant', data: p }
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  deleteTarget.value = null
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deletingConfirmed.value = true
  try {
    const { type, data } = deleteTarget.value
    if (type === 'training') {
      await fetch(`${API}?id=${data.id}`, { method: 'DELETE' })
      if (selectedTraining.value?.id === data.id) selectedTraining.value = null
      await fetchTrainings()
    } else {
      await fetch(`${API}?participants=1&training_id=${selectedTraining.value.id}&participant_id=${data.id}`, { method: 'DELETE' })
      await fetchParticipants(selectedTraining.value.id)
      await fetchTrainings()
      selectedTraining.value = trainings.value.find(t => t.id === selectedTraining.value.id) ?? selectedTraining.value
    }
  } catch (e) { alert('Failed to delete: ' + e.message) }
  finally { deletingConfirmed.value = false; cancelDelete() }
}

// ── Select training → load participants ───────────────────────────────────────
async function selectTraining(t) {
  selectedTraining.value = t
  await fetchParticipants(t.id)
}

async function fetchParticipants(trainingId) {
  loadingParts.value = true
  try {
    const res  = await fetch(`${API}?participants=1&training_id=${trainingId}`)
    participants.value = await res.json()
  } catch (e) { participants.value = [] }
  finally { loadingParts.value = false }
}

// ── Add participants combobox ─────────────────────────────────────────────────
const existingIds = computed(() => new Set(participants.value.map(p => p.employee_id)))
const stagedIds   = computed(() => new Set(addingParticipants.value.map(e => e.id)))

const filteredEmps = computed(() => {
  const q = empSearch.value.toLowerCase().trim()
  return empStore.employees.filter(e =>
    !existingIds.value.has(e.id) &&
    !stagedIds.value.has(e.id) &&
    (!q || e.lastName.toLowerCase().includes(q) || e.firstName.toLowerCase().includes(q) || e.employeeNo.toLowerCase().includes(q))
  ).slice(0, 50)
})

function stageEmployee(emp) {
  addingParticipants.value.push(emp)
  empSearch.value = ''
  empDropOpen.value = false
}

function unstage(emp) {
  addingParticipants.value = addingParticipants.value.filter(e => e.id !== emp.id)
}

function openAddParticipant() {
  addingParticipants.value = []
  empSearch.value = ''
  showAddParticipant.value = true
}

async function saveParticipants() {
  if (!addingParticipants.value.length) return
  savingParts.value = true
  try {
    const ids = addingParticipants.value.map(e => e.id)
    const res = await fetch(`${API}?participants=1&training_id=${selectedTraining.value.id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ employee_ids: ids }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.error || 'Failed')
    showAddParticipant.value = false
    await fetchParticipants(selectedTraining.value.id)
    await fetchTrainings()
    selectedTraining.value = trainings.value.find(t => t.id === selectedTraining.value.id) ?? selectedTraining.value
  } catch (e) { alert('Failed: ' + e.message) }
  finally { savingParts.value = false }
}

async function toggleAttended(p) {
  await fetch(`${API}?participants=1&training_id=${selectedTraining.value.id}&participant_id=${p.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ attended: p.attended ? 0 : 1 }),
  })
  await fetchParticipants(selectedTraining.value.id)
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusClass(s) {
  return { Upcoming: 'st-blue', Ongoing: 'st-green', Completed: 'st-gray', Cancelled: 'st-red' }[s] || 'st-gray'
}
function catColor(c) {
  return { Medical:'#e74c3c', Nursing:'#e91e63', Administrative:'#2980b9', Technical:'#8e44ad', Leadership:'#f39c12', Safety:'#27ae60', Other:'#7f8c8d' }[c] || '#7f8c8d'
}
function enrollPct(t) { return t.maxParticipants ? Math.min(100, Math.round(t.enrolled / t.maxParticipants * 100)) : 0 }
function onEmpBlur() { setTimeout(() => { empDropOpen.value = false }, 180) }
</script>

<template>
  <div class="page">

    <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-wrap">
          <span class="icon-svg" v-html="icons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search title, instructor, venue..." />
        </div>
        <AppSelect v-model="filterCat"
          :options="[{ label: 'All Categories', value: '' }, ...categories.map(c => ({ label: c, value: c }))]"
          placeholder="All Categories" />
        <AppSelect v-model="filterStatus"
          :options="[{ label: 'All Status', value: '' }, ...statuses.map(s => ({ label: s, value: s }))]"
          placeholder="All Status" />
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ filtered.length }} training(s)</span>
        <button v-if="hasPermission('Trainings', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="icons.add"></span> Add Training
        </button>
      </div>
    </div>

    <!-- ── Main layout: cards + detail panel ─────────────────────────────── -->
    <div class="main-layout">

      <!-- Cards grid -->
      <div class="cards-col">
        <div v-if="loading" class="empty-state">Loading...</div>
        <div v-else-if="filtered.length === 0" class="empty-state">No trainings found.</div>
        <div
          v-for="t in filtered" :key="t.id"
          class="training-card"
          :class="{ selected: selectedTraining?.id === t.id }"
          @click="selectTraining(t)"
        >
          <div class="card-accent" :style="{ background: catColor(t.category) }"></div>
          <div class="card-body">
            <div class="card-top-row">
              <span class="cat-badge" :style="{ background: catColor(t.category) + '22', color: catColor(t.category) }">
                {{ t.category }}
              </span>
              <span class="status-badge" :class="statusClass(t.status)">{{ t.status }}</span>
            </div>
            <h4 class="card-title">
              <span class="icon-svg title-icon" v-html="icons.training"></span>
              {{ t.title }}
            </h4>
            <p v-if="t.description" class="card-desc">{{ t.description }}</p>
            <div class="card-meta">
              <span v-if="t.instructor">👤 {{ t.instructor }}</span>
              <span v-if="t.venue">📍 {{ t.venue }}</span>
              <span>📅 {{ t.dateFrom }}{{ t.dateTo && t.dateTo !== t.dateFrom ? ' – ' + t.dateTo : '' }}</span>
              <span>⏱ {{ t.duration }} day{{ t.duration > 1 ? 's' : '' }}</span>
            </div>
            <div class="card-enroll">
              <div class="enroll-text">
                <span class="icon-svg" v-html="icons.person"></span>
                {{ t.enrolled }} / {{ t.maxParticipants }} enrolled
              </div>
              <div class="enroll-bar">
                <div class="enroll-fill"
                  :style="{ width: enrollPct(t) + '%', background: enrollPct(t) >= 90 ? '#e67e22' : catColor(t.category) }">
                </div>
              </div>
            </div>
            <div class="card-actions" @click.stop>
              <button v-if="hasPermission('Trainings', 'Edit')" class="btn-icon" title="Edit" @click="openEdit(t)">
                <span class="icon-svg" v-html="icons.edit"></span>
              </button>
              <button v-if="hasPermission('Trainings', 'Delete')" class="btn-icon danger" title="Delete" @click="deleteTraining(t)">
                <span class="icon-svg" v-html="icons.delete"></span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail / participants panel -->
      <div class="detail-panel" :class="{ visible: !!selectedTraining }">
        <div v-if="!selectedTraining" class="panel-empty">
          <span class="icon-svg xl" v-html="icons.training"></span>
          <p>Click a training card to view participants</p>
        </div>
        <template v-else>
          <div class="panel-header">
            <div>
              <h3>{{ selectedTraining.title }}</h3>
              <span class="cat-badge sm" :style="{ background: catColor(selectedTraining.category) + '22', color: catColor(selectedTraining.category) }">
                {{ selectedTraining.category }}
              </span>
            </div>
            <button class="btn btn-primary sm" @click="openAddParticipant">
              <span class="icon-svg" v-html="icons.person"></span> Add Participants
            </button>
          </div>

          <div class="panel-stats">
            <div class="pstat"><strong>{{ selectedTraining.enrolled }}</strong><span>Enrolled</span></div>
            <div class="pstat"><strong>{{ participants.filter(p => p.attended).length }}</strong><span>Attended</span></div>
            <div class="pstat"><strong>{{ selectedTraining.maxParticipants }}</strong><span>Max</span></div>
          </div>

          <div v-if="loadingParts" class="panel-loading">Loading participants...</div>
          <div v-else-if="participants.length === 0" class="panel-no-parts">
            No participants yet. Click "Add Participants" to enroll employees.
          </div>
          <div v-else class="participants-list">
            <div v-for="p in participants" :key="p.id" class="participant-row">
              <div class="part-avatar">{{ p.first_name?.[0] }}{{ p.last_name?.[0] }}</div>
              <div class="part-info">
                <strong>{{ p.last_name }}, {{ p.first_name }}</strong>
                <span>{{ p.position || '—' }} · {{ p.department || '—' }}</span>
              </div>
              <button class="btn-icon danger sm" @click="removeParticipant(p)" title="Remove">
                <span class="icon-svg" v-html="icons.delete"></span>
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- ── Add/Edit Training Modal ────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal">
          <div class="modal-header">
            <h3>{{ editId ? 'Edit Training' : 'Add Training' }}</h3>
            <button class="close-btn" @click="showForm = false">
              <span class="icon-svg" v-html="icons.close"></span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-grid">
              <div class="form-group full">
                <label>Training Title <span class="req">*</span></label>
                <input v-model="form.title" placeholder="e.g. Basic Life Support Training" />
                <span v-if="formErrors.title" class="field-error">{{ formErrors.title }}</span>
              </div>
              <div class="form-group">
                <label>Category</label>
                <AppSelect v-model="form.category" :options="categories" />
              </div>
              <div class="form-group">
                <label>Status</label>
                <AppSelect v-model="form.status" :options="statuses" />
              </div>
              <div class="form-group">
                <label>Instructor / Facilitator</label>
                <input v-model="form.instructor" placeholder="Name or organization" />
              </div>
              <div class="form-group">
                <label>Venue</label>
                <input v-model="form.venue" placeholder="Location" />
              </div>
              <div class="form-group">
                <label>Date From <span class="req">*</span></label>
                <input v-model="form.dateFrom" type="date" />
                <span v-if="formErrors.dateFrom" class="field-error">{{ formErrors.dateFrom }}</span>
              </div>
              <div class="form-group">
                <label>Date To</label>
                <input v-model="form.dateTo" type="date" />
              </div>
              <div class="form-group">
                <label>Duration (days)</label>
                <input v-model.number="form.duration" type="number" min="1" />
              </div>
              <div class="form-group">
                <label>Max Participants</label>
                <input v-model.number="form.maxParticipants" type="number" min="1" />
              </div>
              <div class="form-group full">
                <label>Description</label>
                <textarea v-model="form.description" rows="2" placeholder="Brief description"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showForm = false" :disabled="saving">Cancel</button>
            <button class="btn btn-primary" @click="save" :disabled="saving">
              <span class="icon-svg" v-html="icons.save"></span>
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Add Participants Modal ─────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showAddParticipant" class="modal-overlay" @click.self="showAddParticipant = false">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3>Add Participants</h3>
            <button class="close-btn" @click="showAddParticipant = false">
              <span class="icon-svg" v-html="icons.close"></span>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-sub">Search and select employees to enroll in <strong>{{ selectedTraining?.title }}</strong>.</p>

            <!-- Employee search combobox -->
            <div class="emp-combobox">
              <div class="search-wrap full-w">
                <span class="icon-svg search-icon-sm" v-html="icons.search"></span>
                <input
                  v-model="empSearch"
                  class="emp-search-input"
                  placeholder="Type name or employee no..."
                  @focus="empDropOpen = true"
                  @blur="onEmpBlur"
                  autocomplete="off"
                />
              </div>
              <div v-if="empDropOpen && filteredEmps.length" class="emp-dropdown">
                <div v-for="emp in filteredEmps" :key="emp.id" class="emp-option"
                  @mousedown.prevent="stageEmployee(emp)">
                  <span class="emp-opt-no">{{ emp.employeeNo }}</span>
                  <span class="emp-opt-name">{{ emp.lastName }}, {{ emp.firstName }}</span>
                  <span class="emp-opt-dept">{{ emp.department }}</span>
                </div>
              </div>
              <div v-if="empDropOpen && !filteredEmps.length" class="emp-dropdown">
                <div class="emp-option-empty">No employees found.</div>
              </div>
            </div>

            <!-- Staged list -->
            <div v-if="addingParticipants.length" class="staged-list">
              <div class="staged-header">Selected ({{ addingParticipants.length }})</div>
              <div v-for="emp in addingParticipants" :key="emp.id" class="staged-row">
                <div class="part-avatar sm">{{ emp.firstName?.[0] }}{{ emp.lastName?.[0] }}</div>
                <div class="part-info">
                  <strong>{{ emp.lastName }}, {{ emp.firstName }}</strong>
                  <span>{{ emp.employeeNo }}</span>
                </div>
                <button class="btn-icon danger sm" @click="unstage(emp)">
                  <span class="icon-svg" v-html="icons.close"></span>
                </button>
              </div>
            </div>
            <div v-else class="staged-empty">No employees selected yet.</div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showAddParticipant = false" :disabled="savingParts">Cancel</button>
            <button class="btn btn-primary" @click="saveParticipants" :disabled="savingParts || !addingParticipants.length">
              <span class="icon-svg" v-html="icons.person"></span>
              {{ savingParts ? 'Enrolling...' : `Enroll ${addingParticipants.length || ''}` }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>    <!-- ── Update Confirmation Modal ────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showConfirm" class="modal-overlay" @click.self="showConfirm = false">
        <div class="modal modal-sm">
          <div class="modal-icon-wrap">
            <span class="icon-svg confirm-icon" v-html="icons.save"></span>
          </div>
          <h3 class="confirm-title">Save Changes</h3>
          <p class="confirm-msg">Are you sure you want to update this training?</p>
          <div class="confirm-card">
            <span class="icon-svg" v-html="icons.training"></span>
            <div>
              <strong>{{ form.title }}</strong>
              <span>{{ form.category }} · {{ form.dateFrom }}</span>
            </div>
          </div>
          <p class="confirm-note">All changes will be saved to the database.</p>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showConfirm = false" :disabled="saving">Cancel</button>
            <button class="btn btn-confirm-ok" @click="doSave" :disabled="saving">
              <span class="icon-svg" v-html="icons.save"></span>
              {{ saving ? 'Saving...' : 'Yes, Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
    <!-- ── Delete Confirmation Modal ────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="cancelDelete">
        <div class="modal modal-sm del-modal-box">
          <div class="modal-icon-wrap del-icon-wrap">
            <span class="icon-svg del-warn-icon" v-html="icons.delete"></span>
          </div>
          <h3 class="confirm-title">
            {{ deleteTarget?.type === 'training' ? 'Delete Training' : 'Remove Participant' }}
          </h3>
          <p class="confirm-msg">
            {{ deleteTarget?.type === 'training'
              ? 'Are you sure you want to delete this training? All participants will also be removed.'
              : 'Are you sure you want to remove this participant from the training?' }}
          </p>
          <!-- Training target card -->
          <div v-if="deleteTarget?.type === 'training'" class="confirm-card">
            <span class="icon-svg" v-html="icons.training"></span>
            <div>
              <strong>{{ deleteTarget.data.title }}</strong>
              <span>{{ deleteTarget.data.category }} · {{ deleteTarget.data.dateFrom }}</span>
            </div>
          </div>
          <!-- Participant target card -->
          <div v-else-if="deleteTarget?.type === 'participant'" class="confirm-card">
            <div class="part-avatar-sm">{{ deleteTarget.data.first_name?.[0] }}{{ deleteTarget.data.last_name?.[0] }}</div>
            <div>
              <strong>{{ deleteTarget.data.last_name }}, {{ deleteTarget.data.first_name }}</strong>
              <span>{{ deleteTarget.data.position || '—' }} · {{ deleteTarget.data.department || '—' }}</span>
            </div>
          </div>
          <p class="del-warning-note">This action cannot be undone.</p>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="cancelDelete" :disabled="deletingConfirmed">Cancel</button>
            <button class="btn btn-delete-ok" @click="confirmDelete" :disabled="deletingConfirmed">
              <span class="icon-svg" v-html="icons.delete"></span>
              {{ deletingConfirmed ? 'Deleting...' : (deleteTarget?.type === 'training' ? 'Yes, Delete' : 'Yes, Remove') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.icon-svg.xl { width:48px; height:48px; color:#ccc; }
.icon-svg.xl :deep(svg) { width:48px; height:48px; fill:#ccc; }
.page { padding:24px; display:flex; flex-direction:column; height:100%; }

/* Toolbar */
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.toolbar-left, .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-wrap { position:relative; display:inline-flex; align-items:center; }
.search-wrap .icon-svg { position:absolute; left:10px; color:#aaa; pointer-events:none; }
.search-input { padding:8px 14px 8px 34px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:240px; outline:none; }
.record-count { font-size:13px; color:#888; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:background 0.2s; }
.btn.sm { padding:6px 12px; font-size:12px; }
.btn-primary { background:#1a3a5c; color:#fff; }
.btn-primary:hover:not(:disabled) { background:#2980b9; }
.btn-primary:disabled { background:#a0b4c8; cursor:not-allowed; }
.btn-secondary { background:#f0f4f8; color:#1a3a5c; border:1px solid #ddd; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; border-radius:4px; display:inline-flex; align-items:center; color:#555; transition:background 0.15s; }
.btn-icon:hover { background:#f0f4f8; color:#1a3a5c; }
.btn-icon.danger:hover { background:#fdecea; color:#e74c3c; }
.btn-icon.sm { padding:3px; }

/* Main layout */
.main-layout { display:grid; grid-template-columns:1fr 380px; gap:20px; flex:1; min-height:0; }
@media (max-width:1100px) { .main-layout { grid-template-columns:1fr; } }

/* Cards */
.cards-col { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; align-content:start; overflow-y:auto; max-height:calc(100vh - 160px); padding-right:4px; }
.empty-state { grid-column:1/-1; text-align:center; color:#aaa; padding:60px; font-size:14px; }
.training-card {
  background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.07);
  cursor:pointer; transition:box-shadow 0.2s, transform 0.15s;
  display:flex; overflow:hidden; border:2px solid transparent;
}
.training-card:hover { box-shadow:0 4px 20px rgba(0,0,0,0.12); transform:translateY(-2px); }
.training-card.selected { border-color:#1a3a5c; box-shadow:0 4px 20px rgba(26,58,92,0.2); }
.card-accent { width:5px; flex-shrink:0; }
.card-body { padding:14px 16px; flex:1; display:flex; flex-direction:column; gap:8px; }
.card-top-row { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.cat-badge { padding:2px 10px; border-radius:10px; font-size:11px; font-weight:700; }
.cat-badge.sm { font-size:10px; padding:2px 8px; }
.status-badge { padding:2px 10px; border-radius:10px; font-size:11px; font-weight:600; }
.st-blue   { background:#ebf5fb; color:#2980b9; }
.st-green  { background:#eafaf1; color:#27ae60; }
.st-gray   { background:#f4f4f4; color:#666; }
.st-red    { background:#fdecea; color:#c0392b; }
.card-title { margin:0; font-size:14px; font-weight:700; color:#1a1a2e; display:flex; align-items:flex-start; gap:6px; line-height:1.3; }
.title-icon { flex-shrink:0; margin-top:1px; color:#1a3a5c; }
.title-icon :deep(svg) { fill:#1a3a5c; }
.card-desc { margin:0; font-size:12px; color:#888; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.card-meta { display:flex; flex-wrap:wrap; gap:6px 14px; font-size:11px; color:#666; }
.card-enroll { display:flex; flex-direction:column; gap:4px; }
.enroll-text { display:flex; align-items:center; gap:4px; font-size:12px; color:#555; font-weight:600; }
.enroll-bar { height:5px; background:#f0f4f8; border-radius:3px; overflow:hidden; }
.enroll-fill { height:100%; border-radius:3px; transition:width 0.3s; }
.card-actions { display:flex; justify-content:flex-end; gap:4px; margin-top:4px; }

/* Detail panel */
.detail-panel {
  background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.07);
  display:flex; flex-direction:column; overflow:hidden;
  max-height:calc(100vh - 160px);
}
.panel-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; flex:1; color:#aaa; font-size:14px; padding:40px; text-align:center; }
.panel-header { padding:16px 18px; border-bottom:1px solid #f0f4f8; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.panel-header h3 { margin:0 0 6px; font-size:15px; color:#1a1a2e; line-height:1.3; }
.panel-stats { display:flex; gap:0; border-bottom:1px solid #f0f4f8; }
.pstat { flex:1; padding:12px; text-align:center; border-right:1px solid #f0f4f8; }
.pstat:last-child { border-right:none; }
.pstat strong { display:block; font-size:22px; font-weight:800; color:#1a3a5c; }
.pstat span { font-size:11px; color:#888; }
.panel-loading, .panel-no-parts { padding:24px; text-align:center; color:#aaa; font-size:13px; }
.participants-list { overflow-y:auto; flex:1; padding:8px 0; }
.participant-row { display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid #f9fafb; transition:background 0.15s; }
.participant-row:hover { background:#f9fafb; }
.part-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.part-avatar.sm { width:28px; height:28px; font-size:10px; }
.part-info { flex:1; display:flex; flex-direction:column; }
.part-info strong { font-size:13px; color:#1a1a2e; }
.part-info span { font-size:11px; color:#888; }
.attend-btn { padding:4px 10px; border-radius:8px; border:1.5px solid #ddd; background:#f9fafb; color:#888; font-size:11px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:all 0.15s; white-space:nowrap; }
.attend-btn.attended { background:#eafaf1; border-color:#27ae60; color:#27ae60; }
.attend-btn:hover { border-color:#1a3a5c; color:#1a3a5c; }

/* Modals */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(2px); }
.modal { background:#fff; border-radius:12px; width:700px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.modal.modal-sm { width:480px; }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f0f4f8; }
.modal-header h3 { margin:0; color:#1a3a5c; font-size:16px; }
.close-btn { background:none; border:none; cursor:pointer; color:#888; display:inline-flex; align-items:center; padding:4px; border-radius:4px; }
.close-btn:hover { background:#f0f4f8; }
.modal-body { padding:20px; }
.modal-sub { margin:0 0 14px; font-size:13px; color:#555; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #f0f4f8; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group.full { grid-column:1/-1; }
.form-group label { font-size:12px; font-weight:600; color:#555; }
.form-group input, .form-group textarea { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; }
.form-group input:focus, .form-group textarea:focus { border-color:#1a3a5c; }
.field-error { font-size:11px; color:#c0392b; }
.req { color:#c0392b; }

/* Employee combobox */
.emp-combobox { position:relative; margin-bottom:14px; }
.full-w { width:100%; }
.search-icon-sm { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#aaa; pointer-events:none; }
.emp-search-input { width:100%; padding:8px 12px 8px 34px; border:1px solid #ddd; border-radius:6px; font-size:13px; outline:none; box-sizing:border-box; }
.emp-search-input:focus { border-color:#1a3a5c; }
.emp-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; max-height:200px; overflow-y:auto; }
.emp-option { display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; border-bottom:1px solid #f5f5f5; transition:background 0.15s; }
.emp-option:last-child { border-bottom:none; }
.emp-option:hover { background:#f0f9f4; }
.emp-opt-no { font-family:monospace; font-size:11px; color:#888; min-width:80px; flex-shrink:0; }
.emp-opt-name { font-size:13px; font-weight:600; color:#1a1a2e; flex:1; }
.emp-opt-dept { font-size:11px; color:#aaa; flex-shrink:0; }
.emp-option-empty { padding:12px; text-align:center; color:#aaa; font-size:13px; }

/* Staged list */
.staged-list { border:1px solid #e9ecef; border-radius:8px; overflow:hidden; }
.staged-header { padding:8px 12px; background:#f8f9fa; font-size:12px; font-weight:600; color:#555; border-bottom:1px solid #e9ecef; }
.staged-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid #f5f5f5; }
.staged-row:last-child { border-bottom:none; }
.staged-empty { text-align:center; color:#aaa; font-size:13px; padding:20px; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition:opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition:transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity:0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform:scale(0.95); opacity:0; }

/* Confirm modal */
.modal.modal-sm { width:420px; display:flex; flex-direction:column; align-items:center; gap:12px; padding:28px 24px 20px; text-align:center; }
.modal-icon-wrap { width:56px; height:56px; border-radius:50%; background:#e8f5ee; display:flex; align-items:center; justify-content:center; }
.confirm-icon { width:28px; height:28px; }
.confirm-icon :deep(svg) { width:28px; height:28px; fill:#1a6b3c; }
.confirm-title { margin:0; font-size:18px; font-weight:700; color:#1a1a2e; }
.confirm-msg { margin:0; font-size:14px; color:#555; }
.confirm-card { display:flex; align-items:center; gap:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:12px 16px; width:100%; text-align:left; }
.confirm-card .icon-svg { color:#1a3a5c; flex-shrink:0; }
.confirm-card .icon-svg :deep(svg) { fill:#1a3a5c; }
.confirm-card strong { display:block; font-size:14px; color:#1a1a2e; }
.confirm-card span { font-size:12px; color:#888; }
.confirm-note { margin:0; font-size:12px; color:#1a6b3c; font-weight:600; }
.btn-confirm-ok { flex:1; padding:10px; border-radius:8px; background:#1a6b3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; transition:background 0.2s; }
.btn-confirm-ok:hover:not(:disabled) { background:#27ae60; }
.btn-confirm-ok:disabled { background:#a0c4b0; cursor:not-allowed; }
.btn-confirm-ok .icon-svg :deep(svg) { fill:#fff; }

/* Delete modal */
.del-modal-box { border-top:4px solid #e74c3c; }
.del-icon-wrap { background:#fdecea !important; }
.del-warn-icon { width:28px; height:28px; }
.del-warn-icon :deep(svg) { width:28px; height:28px; fill:#e74c3c; }
.del-warning-note { margin:0; font-size:12px; color:#e74c3c; font-weight:600; }
.part-avatar-sm { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#c0392b,#e74c3c); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.btn-delete-ok { flex:1; padding:10px; border-radius:8px; background:#e74c3c; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; transition:background 0.2s; }
.btn-delete-ok:hover:not(:disabled) { background:#c0392b; }
.btn-delete-ok:disabled { background:#f1948a; cursor:not-allowed; }
.btn-delete-ok .icon-svg :deep(svg) { fill:#fff; }
</style>
