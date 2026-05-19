<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AppSelect from '@/components/AppSelect.vue'

const API = 'http://localhost/hrs-v2/server/api/birthday_celebrants.php'

const filterMonth = ref(new Date().getMonth() + 1)
const show65      = ref(false)
const search      = ref('')
const loading     = ref(false)
const celebrants  = ref([])
const turning65   = ref([])
const missingBirthDate = ref(0)

const months = [
  { value: 1,  label: 'January'   }, { value: 2,  label: 'February'  },
  { value: 3,  label: 'March'     }, { value: 4,  label: 'April'     },
  { value: 5,  label: 'May'       }, { value: 6,  label: 'June'      },
  { value: 7,  label: 'July'      }, { value: 8,  label: 'August'    },
  { value: 9,  label: 'September' }, { value: 10, label: 'October'   },
  { value: 11, label: 'November'  }, { value: 12, label: 'December'  },
]

const svgIcons = {
  search: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
}

async function fetchCelebrants() {
  loading.value = true
  try {
    const params = new URLSearchParams({ month: filterMonth.value })
    if (search.value.trim()) params.set('search', search.value.trim())
    const res  = await fetch(`${API}?${params}`)
    const data = await res.json()
    celebrants.value      = data.celebrants ?? []
    missingBirthDate.value = data.missing_birth_date ?? 0
  } catch (e) {
    console.error('Birthday fetch error:', e)
    celebrants.value = []
  } finally {
    loading.value = false
  }
}

async function fetchTurning65() {
  try {
    const res  = await fetch(`${API}?turning65=1`)
    const data = await res.json()
    turning65.value = data.celebrants ?? []
  } catch (e) {
    turning65.value = []
  }
}

// Re-fetch when month or search changes
watch([filterMonth, search], () => fetchCelebrants())

onMounted(() => {
  fetchCelebrants()
  fetchTurning65()
})

// Filtered list — apply show65 toggle client-side
const displayList = computed(() =>
  show65.value ? celebrants.value.filter(e => e.is_retirement_age) : celebrants.value
)

const currentMonthName = computed(() =>
  months.find(m => m.value === Number(filterMonth.value))?.label
)

function getAgeThisYear(e) {
  return e.age_this_year ?? '—'
}
</script>

<template>
  <div class="page">
    <!-- Filters -->
    <div class="toolbar">
      <div class="toolbar-left">
        <AppSelect
          v-model="filterMonth"
          :options="months.map(m => ({ label: m.label, value: m.value }))"
        />
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search name..." />
        </div>
        <label class="toggle-label">
          <input type="checkbox" v-model="show65" />
          Show 65+ only
        </label>
      </div>
      <div class="toolbar-right">
        <span class="record-count">{{ displayList.length }} celebrant(s)</span>
      </div>
    </div>

    <!-- Missing birth date warning -->
    <div v-if="missingBirthDate > 0" class="missing-warning">
      ⚠️ <strong>{{ missingBirthDate }} employee(s)</strong> have no birth date on record and won't appear here.
      Update their profiles in the <router-link to="/employees">Employee Masterlist</router-link> to include them.
    </div>

    <!-- Turning 65 Alert -->
    <div v-if="turning65.length > 0" class="alert-box">
      <strong>⚠️ Employees Turning 65 This Year ({{ new Date().getFullYear() }}):</strong>
      <div class="turning65-list">
        <span v-for="emp in turning65" :key="emp.id" class="turning65-badge">
          {{ emp.last_name }}, {{ emp.first_name }}{{ emp.department ? ' (' + emp.department + ')' : '' }} — Age {{ emp.age_this_year }}
        </span>
      </div>
    </div>

    <!-- Celebrants Grid -->
    <div class="section-header">
      <h3>🎂 Birthday Celebrants — {{ currentMonthName }}</h3>
    </div>

    <div v-if="loading" class="loading-state">
      ⏳ Loading celebrants...
    </div>

    <div v-else-if="displayList.length === 0" class="empty-state">
      No birthday celebrants for {{ currentMonthName }}.
    </div>

    <div v-else class="celebrants-grid">
      <div v-for="emp in displayList" :key="emp.id" class="celebrant-card">
        <div class="card-top">
          <div class="avatar-large">{{ emp.first_name[0] }}{{ emp.last_name[0] }}</div>
          <div class="emp-info">
            <strong>{{ emp.last_name }}, {{ emp.first_name }}</strong>
            <span>{{ emp.position }}</span>
            <span class="dept-tag">{{ emp.department }}</span>
          </div>
        </div>
        <div class="card-bottom">
          <div class="bday-row">
            <span>🎂 {{ emp.birthday_label }}</span>
            <span class="age-badge">Age {{ emp.current_age }}</span>
          </div>
          <div class="countdown">
            {{ emp.is_today ? '🎉 Today!' : `⏳ ${emp.days_until_birthday} day(s)` }}
          </div>
          <div v-if="emp.is_retirement_age" class="retirement-tag">
            🎖️ Retirement Age
          </div>
        </div>
      </div>
    </div>
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
  font-size: 13px; width: 220px; outline: none;
}
.filter-select {
  padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
  font-size: 13px; outline: none; background: #fff;
}
.toggle-label { display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; }
.record-count { font-size: 13px; color: #888; }
.alert-box {
  background: #fff8e1; border: 1px solid #ffd700; border-radius: 10px;
  padding: 14px 18px; margin-bottom: 20px;
}
.alert-box strong { color: #b8860b; font-size: 13px; }
.turning65-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.turning65-badge {
  background: #ffd700; color: #1a3a5c; padding: 4px 12px;
  border-radius: 12px; font-size: 12px; font-weight: 600;
}
.section-header { margin-bottom: 16px; }
.section-header h3 { margin: 0; font-size: 18px; color: #1a3a5c; }
.empty-state { text-align: center; color: #aaa; padding: 60px; font-size: 15px; }
.loading-state { text-align: center; color: #888; padding: 60px; font-size: 15px; }
.missing-warning {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  color: #856404;
}
.missing-warning a { color: #1a3a5c; font-weight: 600; }
.celebrants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}
.celebrant-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  overflow: hidden;
  border-top: 4px solid #ffd700;
}
.card-top {
  display: flex; align-items: center; gap: 14px;
  padding: 16px; background: #f9fafb;
}
.avatar-large {
  width: 50px; height: 50px; border-radius: 50%;
  background: linear-gradient(135deg, #ffd700, #ffb300);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 800; color: #1a3a5c; flex-shrink: 0;
}
.emp-info { display: flex; flex-direction: column; font-size: 12px; color: #555; }
.emp-info strong { font-size: 14px; color: #1a3a5c; }
.dept-tag {
  display: inline-block; background: #e8f0fe; color: #1a3a5c;
  padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-top: 4px;
}
.card-bottom { padding: 12px 16px; }
.bday-row {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 13px; color: #333; margin-bottom: 6px;
}
.age-badge {
  background: #1a3a5c; color: #fff; padding: 2px 10px;
  border-radius: 10px; font-size: 11px; font-weight: 700;
}
.countdown { font-size: 12px; color: #888; }
.retirement-tag {
  margin-top: 8px; background: #fdecea; color: #c0392b;
  padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
}
</style>
