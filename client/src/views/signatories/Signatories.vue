<script setup>
import { ref, onMounted } from 'vue'
import { useSignatoryStore } from '@/stores/signatories'
import { usePermissions } from '@/composables/usePermissions'
import { useNotificationStore } from '@/stores/notifications'
import AppModal from '@/components/AppModal.vue'

const store = useSignatoryStore()
const { hasPermission, loadPermissions } = usePermissions()
const notificationStore = useNotificationStore()

// Fetch signatories on component mount
onMounted(async () => {
  await loadPermissions()
  store.fetchRecords()
})

const svgIcons = {
  sign: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  add: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>`,
  edit: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  delete: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  save: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  close: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
  people: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
}
const showForm = ref(false)
const editId = ref(null)
const saving = ref(false)

const blankForm = () => ({
  name: '', position: '', role: '', department: '', active: true, order: store.signatories.length + 1,
})
const form = ref(blankForm())

function openAdd() { editId.value = null; form.value = blankForm(); showForm.value = true }
function openEdit(s) { editId.value = s.id; form.value = { ...s }; showForm.value = true }
const showDeleteModal = ref(false)
const showSaveModal   = ref(false)
const deleteTarget    = ref(null)

function save() {
  showSaveModal.value = true
}
async function confirmSave() {
  saving.value = true
  try {
    if (editId.value) {
      await store.updateRecord(editId.value, form.value)
    } else {
      await store.addRecord(form.value)
    }
    showSaveModal.value = false
    showForm.value = false
  } catch (err) {
    notificationStore.error('Error saving signatory: ' + err.message)
  } finally {
    saving.value = false
  }
}
function deleteRec(id) {
  deleteTarget.value = store.signatories.find(s => s.id === id)
  showDeleteModal.value = true
}
async function confirmDelete() {
  try {
    await store.deleteRecord(deleteTarget.value.id)
    showDeleteModal.value = false
    deleteTarget.value = null
  } catch (err) {
    notificationStore.error('Error deleting signatory: ' + err.message)
  }
}
async function toggleActive(id) {
  try {
    await store.toggleActive(id)
  } catch (err) {
    notificationStore.error('Error toggling status: ' + err.message)
  }
}
</script>

<template>
  <div class="page">
    <!-- Loading State -->
    <div v-if="store.loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading signatories...</p>
    </div>

    <!-- Error State -->
    <div v-if="store.error" class="error-banner">
      <strong>⚠️ Error:</strong> {{ store.error }}
      <button class="btn-retry" @click="store.fetchRecords()">Retry</button>
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <h3 class="section-label">
          <span class="icon-svg" v-html="svgIcons.sign"></span>
          Authorized Signatories
        </h3>
      </div>
      <div class="toolbar-right">
        <button v-if="hasPermission('Signatories', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Signatory
        </button>
      </div>
    </div>

    <div class="sig-grid">
      <div v-for="s in store.signatories.slice().sort((a,b) => a.order - b.order)" :key="s.id"
        class="sig-card" :class="{ inactive: !s.active }">
        <div class="sig-order">{{ s.order }}</div>
        <div class="sig-avatar">{{ s.name.split(' ').map(n => n[0]).join('').slice(0,2) }}</div>
        <div class="sig-info">
          <strong>{{ s.name }}</strong>
          <span>{{ s.position }}</span>
          <span class="sig-dept">{{ s.department }}</span>
          <span class="sig-role-badge">{{ s.role }}</span>
        </div>
        <div class="sig-actions">
          <button v-if="hasPermission('Signatories', 'Edit')" class="btn-icon" @click="openEdit(s)">
            <span class="icon-svg" v-html="svgIcons.edit"></span>
          </button>
          <button v-if="hasPermission('Signatories', 'Edit')" class="btn-icon" @click="toggleActive(s)" :title="s.active ? 'Deactivate' : 'Activate'">
            {{ s.active ? '🔴' : '🟢' }}
          </button>
          <button v-if="hasPermission('Signatories', 'Delete')" class="btn-icon danger" @click="deleteRec(s.id)">
            <span class="icon-svg" v-html="svgIcons.delete"></span>
          </button>
        </div>
        <div v-if="!s.active" class="inactive-label">INACTIVE</div>
      </div>
    </div>

    <!-- Signature Flow Diagram -->
    <div class="flow-section">
      <h3>
        <span class="icon-svg" v-html="svgIcons.people"></span>
        Signature Flow
      </h3>
      <div class="flow-diagram">
        <div v-for="(s, i) in store.signatories.filter(x => x.active).slice().sort((a,b) => a.order - b.order)" :key="s.id" class="flow-item">
          <div class="flow-node">
            <div class="flow-avatar">{{ s.name.split(' ').map(n => n[0]).join('').slice(0,2) }}</div>
            <div class="flow-name">{{ s.name }}</div>
            <div class="flow-role">{{ s.role }}</div>
          </div>
          <div v-if="i < store.signatories.filter(x => x.active).length - 1" class="flow-arrow">→</div>
        </div>
      </div>
    </div>

    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editId ? 'Edit Signatory' : 'Add Signatory' }}</h3>
          <button class="close-btn" @click="showForm = false">
            <span class="icon-svg" v-html="svgIcons.close"></span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full"><label>Full Name</label><input v-model="form.name" /></div>
            <div class="form-group"><label>Position</label><input v-model="form.position" /></div>
            <div class="form-group"><label>Role / Function</label><input v-model="form.role" /></div>
            <div class="form-group"><label>Department</label><input v-model="form.department" /></div>
            <div class="form-group"><label>Signing Order</label><input v-model.number="form.order" type="number" min="1" /></div>
            <div class="form-group">
              <label>Active</label>
              <AppSelect
                v-model="form.active"
                :options="[{ label: 'Yes', value: true }, { label: 'No', value: false }]"
              />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showForm = false" :disabled="saving">Cancel</button>
          <button class="btn btn-primary" @click="save" :disabled="saving">
            <span v-if="saving" class="spinner-small"></span>
            <span v-else class="icon-svg" v-html="svgIcons.save"></span>
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <AppModal
      v-if="showDeleteModal"
      type="delete"
      title="Remove Signatory"
      message="Are you sure you want to remove this signatory?"
      :detail="deleteTarget?.name + ' — ' + deleteTarget?.role"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Save Confirmation -->
    <AppModal
      v-if="showSaveModal"
      type="confirm"
      :title="editId ? 'Update Signatory' : 'Add Signatory'"
      :message="editId ? 'Save changes to this signatory?' : 'Add this new signatory?'"
      :detail="form.name"
      :confirmLabel="editId ? 'Yes, Update' : 'Yes, Add'"
      @confirm="confirmSave"
      @cancel="showSaveModal = false"
    />
  </div>
</template>

<style scoped>
.icon-svg { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; }
.icon-svg :deep(svg) { width:100%; height:100%; fill:currentColor; }
.page { padding: 24px; }
.toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 10px; }
.section-label { margin: 0; font-size: 18px; color: #1a3a5c; display: flex; align-items: center; gap: 8px; }
.btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary { background: #1a3a5c; color: #fff; }
.btn-secondary { background: #f0f4f8; color: #1a3a5c; border: 1px solid #ddd; }
.sig-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 28px; }
.sig-card {
  background: #fff; border-radius: 12px; padding: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07); display: flex;
  align-items: center; gap: 12px; position: relative;
  border-left: 4px solid #1a3a5c;
}
.sig-card.inactive { opacity: 0.5; border-left-color: #ccc; }
.sig-order {
  width: 28px; height: 28px; border-radius: 50%;
  background: #1a3a5c; color: #fff; display: flex;
  align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.sig-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: linear-gradient(135deg, #ffd700, #ffb300);
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800; color: #1a3a5c; flex-shrink: 0;
}
.sig-info { flex: 1; display: flex; flex-direction: column; font-size: 12px; color: #555; }
.sig-info strong { font-size: 14px; color: #1a3a5c; }
.sig-dept { color: #888; }
.sig-role-badge {
  display: inline-block; background: #ebf5fb; color: #2980b9;
  padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-top: 4px;
}
.sig-actions { display: flex; flex-direction: column; gap: 4px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 3px; border-radius: 4px; display: inline-flex; align-items: center; }
.btn-icon:hover { background: #f0f4f8; }
.btn-icon.danger:hover { background: #fdecea; }
.inactive-label {
  position: absolute; top: 8px; right: 8px;
  background: #ccc; color: #fff; font-size: 10px;
  font-weight: 700; padding: 2px 8px; border-radius: 10px;
}
.flow-section { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.flow-section h3 { margin: 0 0 16px; color: #1a3a5c; display: flex; align-items: center; gap: 8px; }
.flow-diagram { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.flow-item { display: flex; align-items: center; gap: 8px; }
.flow-node { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.flow-avatar {
  width: 48px; height: 48px; border-radius: 50%;
  background: linear-gradient(135deg, #1a3a5c, #2980b9);
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; color: #fff;
}
.flow-name { font-size: 11px; font-weight: 600; color: #1a3a5c; text-align: center; max-width: 80px; }
.flow-role { font-size: 10px; color: #888; text-align: center; max-width: 80px; }
.flow-arrow { font-size: 20px; color: #1a3a5c; font-weight: 700; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: #fff; border-radius: 12px; width: 500px; max-width: 95vw; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f0f4f8; }
.modal-header h3 { margin: 0; color: #1a3a5c; }
.close-btn { background: none; border: none; cursor: pointer; color: #888; display: inline-flex; align-items: center; padding: 4px; border-radius: 4px; }
.close-btn:hover { background: #f0f4f8; }
.modal-body { padding: 20px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #f0f4f8; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 600; color: #555; }
.form-group input, .form-group select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; }
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
.spinner-small {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
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
.btn-primary:disabled, .btn-secondary:disabled {
  opacity: 0.5; cursor: not-allowed;
}
</style>
