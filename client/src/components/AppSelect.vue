<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { default: '' },
  options: { type: Array, default: () => [] },
  // options can be strings OR { label, value } objects
  placeholder: { type: String, default: 'Select...' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const wrapper = ref(null)

const normalized = computed(() =>
  props.options.map(o =>
    typeof o === 'string' ? { label: o, value: o } : o
  )
)

const selectedLabel = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined)
    return props.placeholder
  const match = normalized.value.find(o => o.value === props.modelValue)
  return match ? match.label : props.placeholder
})

function select(val) {
  emit('update:modelValue', val)
  open.value = false
}

function onClickOutside(e) {
  if (wrapper.value && !wrapper.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div class="app-select" ref="wrapper" :class="{ open }">
    <button type="button" class="app-select-trigger" @click="open = !open">
      <span class="app-select-value" :class="{ placeholder: modelValue === '' || modelValue === null || modelValue === undefined }">
        {{ selectedLabel }}
      </span>
      <span class="app-select-arrow">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
      </span>
    </button>

    <transition name="dropdown">
      <div v-if="open" class="app-select-dropdown">
        <div
          v-for="opt in normalized"
          :key="opt.value"
          class="app-select-option"
          :class="{ selected: opt.value === modelValue }"
          @mousedown.prevent="select(opt.value)"
        >
          {{ opt.label }}
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.app-select {
  position: relative;
  display: inline-block;
  min-width: 130px;
}

.app-select-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  padding: 0 12px;
  height: 36px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
  font-size: 13px;
  color: #333;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  white-space: nowrap;
  box-sizing: border-box;
}
.app-select-trigger:hover {
  border-color: #1a6b3c;
}
.app-select.open .app-select-trigger {
  border-color: #1a6b3c;
  box-shadow: 0 0 0 3px rgba(26, 107, 60, 0.15);
}

.app-select-value { flex: 1; text-align: left; }
.app-select-value.placeholder { color: #aaa; }

.app-select-arrow {
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #888;
  transition: transform 0.2s;
  flex-shrink: 0;
}
.app-select-arrow svg { width: 18px; height: 18px; }
.app-select.open .app-select-arrow { transform: rotate(180deg); }

/* The dropdown list box */
.app-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  min-width: 100%;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 9999;
  overflow: hidden;
  max-height: 260px;
  overflow-y: auto;
}

.app-select-option {
  padding: 9px 14px;
  font-size: 13px;
  color: #333;
  cursor: pointer;
  transition: background 0.15s;
}
.app-select-option:hover {
  background: #f0f9f4;
  color: #1a6b3c;
}
.app-select-option.selected {
  background: #1a6b3c;
  color: #fff;
  font-weight: 600;
}

/* Scrollbar inside dropdown */
.app-select-dropdown::-webkit-scrollbar { width: 4px; }
.app-select-dropdown::-webkit-scrollbar-thumb { background: #c0cdd8; border-radius: 2px; }

/* Transition */
.dropdown-enter-active, .dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
