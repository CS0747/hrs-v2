<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppSidebar from './components/AppSidebar.vue'
import AppHeader from './components/AppHeader.vue'

const route    = useRoute()
const auth     = useAuthStore()
const isPublic = computed(() => route.meta.public)

// Show read-only banner for Section Admin on all non-schedule pages
const showReadOnlyBanner = computed(() => {
  if (!auth.isSectionAdmin) return false
  // Schedule is the only page Section Admin can edit
  return route.path !== '/schedule'
})

const sidebarOpen = ref(true)
function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="app-layout" :class="{ 'no-sidebar': isPublic }">
    <template v-if="!isPublic">
      <AppSidebar :open="sidebarOpen" @toggle="toggleSidebar" />
      <div class="main-area">
        <AppHeader @toggle-sidebar="toggleSidebar" />

        <!-- Read-only banner for Section Admin -->
        <div v-if="showReadOnlyBanner" class="readonly-banner">
          <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>
          View only — Section Admin cannot modify data on this page.
        </div>

        <div class="content-area" :class="{ 'readonly-mode': showReadOnlyBanner }">
          <RouterView v-slot="{ Component, route: r }">
            <Transition name="page" appear>
              <component :is="Component" :key="r.fullPath" />
            </Transition>
          </RouterView>
        </div>
      </div>
    </template>
    <template v-else>
      <RouterView v-slot="{ Component, route: r }">
        <Transition name="page" appear>
          <component :is="Component" :key="r.fullPath" />
        </Transition>
      </RouterView>
    </template>
  </div>
</template>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f0f4f8;
  color: #333;
}
.app-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}
.app-layout.no-sidebar { display: block; height: auto; overflow: auto; }
.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  overflow: hidden;
}
.content-area {
  flex: 1;
  overflow-y: auto;
  background: #f0f4f8;
}

/* ── Read-only mode — disable all action buttons for Section Admin ─────────── */
.readonly-mode button.btn-primary,
.readonly-mode button[class*="btn-primary"],
.readonly-mode button.btn-confirm-ok,
.readonly-mode button.btn-delete,
.readonly-mode button.btn-delete-ok,
.readonly-mode .btn-icon,
.readonly-mode .btn-icon.danger,
.readonly-mode button[title="Edit"],
.readonly-mode button[title="Delete"],
.readonly-mode button[title="Deactivate"],
.readonly-mode button[title="Remove"],
.readonly-mode a.btn-primary,
.readonly-mode a[href*="/new"],
.readonly-mode a[href*="/edit"] {
  pointer-events: none !important;
  opacity: 0.35 !important;
  cursor: not-allowed !important;
  filter: grayscale(0.4);
}
/* Also make form inputs read-only visually */
.readonly-mode input:not([readonly]),
.readonly-mode select,
.readonly-mode textarea {
  pointer-events: none !important;
  background: #f8f9fa !important;
  color: #888 !important;
  cursor: not-allowed !important;
}
/* Custom dropdowns (AppSelect) */
.readonly-mode .app-select-trigger {
  pointer-events: none !important;
  opacity: 0.6 !important;
  cursor: not-allowed !important;
}
/* Checkboxes and toggles */
.readonly-mode input[type="checkbox"],
.readonly-mode .day-toggle,
.readonly-mode .status-toggle {
  pointer-events: none !important;
  opacity: 0.5 !important;
}
.readonly-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff8e1;
  border-bottom: 1px solid #ffc107;
  color: #856404;
  font-size: 12px;
  font-weight: 600;
  padding: 7px 20px;
  flex-shrink: 0;
}

/* ── Global button transitions ───────────────────────────────────────────── */
button, a[class*="btn"], [class*="btn"] {
  transition: background 0.18s ease, color 0.18s ease,
              border-color 0.18s ease, box-shadow 0.18s ease,
              transform 0.12s ease, opacity 0.18s ease !important;
}
button:hover:not(:disabled),
a[class*="btn"]:hover,
[class*="btn"]:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}
button:active:not(:disabled),
a[class*="btn"]:active,
[class*="btn"]:active:not(:disabled) {
  transform: translateY(0) scale(0.97);
  box-shadow: none;
}
button:disabled,
[class*="btn"]:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: none !important;
  box-shadow: none !important;
}

/* Icon-only buttons — no lift, just subtle scale */
[class*="btn-icon"]:hover:not(:disabled) {
  transform: scale(1.1) !important;
  box-shadow: none !important;
}
[class*="btn-icon"]:active:not(:disabled) {
  transform: scale(0.95) !important;
}
.page-enter-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.page-leave-active {
  transition: opacity 0.15s ease;
  position: absolute;
  width: 100%;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.page-leave-to {
  opacity: 0;
}
.content-area {
  position: relative;
}

::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #f0f4f8; }
::-webkit-scrollbar-thumb { background: #c0cdd8; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #1a6b3c; }

input, select, textarea {
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
input:hover, select:hover, textarea:hover {
  border-color: #1a6b3c !important;
  border-radius: 8px;
}
input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #1a6b3c !important;
  box-shadow: 0 0 0 3px rgba(26, 107, 60, 0.15);
  border-radius: 8px;
}
select option { border-radius: 6px; }
</style>
