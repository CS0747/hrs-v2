<script setup>
/**
 * AppModal — reusable alert/confirm modal
 *
 * Props:
 *   type:    'delete' | 'confirm' | 'success' | 'warning'
 *   title:   string
 *   message: string
 *   detail:  string (optional sub-line, e.g. record name)
 *   confirmLabel: string (default depends on type)
 *   loading: boolean
 *
 * Emits: confirm, cancel
 */
const props = defineProps({
  type:         { type: String, default: 'confirm' },
  title:        { type: String, required: true },
  message:      { type: String, default: '' },
  detail:       { type: String, default: '' },
  confirmLabel: { type: String, default: '' },
  loading:      { type: Boolean, default: false },
})
const emit = defineEmits(['confirm', 'cancel'])

const icons = {
  delete:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`,
  confirm: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>`,
  success: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  warning: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
}

const colorMap = {
  delete:  { bg: '#fdecea', icon: '#e74c3c', btn: '#e74c3c', btnHover: '#c0392b' },
  confirm: { bg: '#ebf5fb', icon: '#2980b9', btn: '#1a3a5c', btnHover: '#2980b9' },
  success: { bg: '#eafaf1', icon: '#27ae60', btn: '#1a6b3c', btnHover: '#27ae60' },
  warning: { bg: '#fef3e2', icon: '#e67e22', btn: '#e67e22', btnHover: '#d35400' },
}

const colors = colorMap[props.type] || colorMap.confirm

const defaultLabels = {
  delete:  'Yes, Delete',
  confirm: 'Confirm',
  success: 'OK',
  warning: 'Proceed',
}

const btnLabel = props.confirmLabel || defaultLabels[props.type] || 'Confirm'
</script>

<template>
  <Transition name="modal">
    <div class="modal-overlay" @click.self="emit('cancel')">
      <div class="modal" role="dialog" aria-modal="true">
        <!-- Icon -->
        <div class="modal-icon-wrap" :style="{ background: colors.bg }">
          <span class="modal-icon" :style="{ color: colors.icon }" v-html="icons[type]"></span>
        </div>

        <!-- Content -->
        <h3 class="modal-title">{{ title }}</h3>
        <p v-if="message" class="modal-message">{{ message }}</p>

        <!-- Detail card (e.g. record name) -->
        <div v-if="detail" class="modal-detail-card">
          <span class="detail-text">{{ detail }}</span>
        </div>

        <p v-if="type === 'delete'" class="modal-warning-note">This action cannot be undone.</p>

        <!-- Actions -->
        <div class="modal-actions">
          <button
            v-if="type !== 'success'"
            class="btn btn-cancel"
            @click="emit('cancel')"
            :disabled="loading"
          >
            Cancel
          </button>
          <button
            class="btn btn-confirm"
            :style="{ background: colors.btn }"
            @click="emit('confirm')"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner">⏳</span>
            <span v-else>{{ btnLabel }}</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(2px);
}
.modal {
  background: #fff;
  border-radius: 16px;
  padding: 32px 28px 24px;
  width: 100%; max-width: 400px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  display: flex; flex-direction: column; align-items: center;
  gap: 10px; text-align: center;
}
.modal-icon-wrap {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
}
.modal-icon { display: inline-flex; width: 28px; height: 28px; }
.modal-icon :deep(svg) { width: 28px; height: 28px; fill: currentColor; }
.modal-title { margin: 0; font-size: 18px; font-weight: 700; color: #1a1a2e; }
.modal-message { margin: 0; font-size: 14px; color: #555; line-height: 1.5; }
.modal-detail-card {
  background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 10px; padding: 10px 18px;
  width: 100%; text-align: center;
}
.detail-text { font-size: 14px; font-weight: 600; color: #1a1a2e; }
.modal-warning-note { margin: 0; font-size: 12px; color: #e74c3c; font-weight: 600; }
.modal-actions { display: flex; gap: 10px; width: 100%; margin-top: 6px; }
.btn { flex: 1; padding: 11px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-cancel { background: #f0f4f8; color: #555; border: 1px solid #ddd; }
.btn-cancel:hover:not(:disabled) { background: #e0e8f0; }
.btn-confirm { color: #fff; }
.btn-confirm:hover:not(:disabled) { opacity: 0.88; }
.spinner { font-size: 14px; }

/* Transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .modal, .modal-leave-active .modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform: scale(0.95); opacity: 0; }
</style>
