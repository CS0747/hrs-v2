# Multi-Employee Calendar Display Solutions

## Current Problem

The calendar currently filters schedules to show **only the logged-in user**:

```javascript
// Line 453 & 531 in ScheduleDatabase.vue
const userName = auth.currentUser?.name ?? ''
return store.schedules.filter(s => {
  if (s.employeeName !== userName) return false  // ❌ Only shows current user
  // ...
})
```

This means HR staff cannot see schedules for all employees.

---

## Solution Options

### Option 1: **Employee Selector Dropdown** (Recommended)
Best for viewing one employee at a time with full detail.

### Option 2: **Department View**
Show all employees in a selected department.

### Option 3: **Multi-Row Calendar** (Google Calendar Style)
Show multiple employees as rows in the calendar.

### Option 4: **List View with Mini Calendars**
Traditional list with small calendar indicators.

---

## Option 1: Employee Selector (RECOMMENDED)

### Why This Works Best:
- ✅ Clean, focused view
- ✅ Easy to implement
- ✅ Works well with existing calendar
- ✅ No performance issues
- ✅ Mobile-friendly

### Implementation:

```vue
<script setup>
// Add employee filter state
const selectedEmployee = ref('all') // 'all' or employee_no
const viewMode = ref('personal') // 'personal' | 'all' | 'department'

// Computed list of unique employees from schedules
const employeeList = computed(() => {
  const unique = new Map()
  store.schedules.forEach(s => {
    if (!unique.has(s.employeeNo)) {
      unique.set(s.employeeNo, {
        employeeNo: s.employeeNo,
        employeeName: s.employeeName,
        department: s.department
      })
    }
  })
  return Array.from(unique.values()).sort((a, b) => 
    a.employeeName.localeCompare(b.employeeName)
  )
})

// Update schedule filtering functions
function schedulesForColumn(colIndex) {
  const colDate  = weekDays.value[colIndex]
  const dayName  = ALL_DAYS[colIndex]
  
  return store.schedules.filter(s => {
    // Filter by selected employee or view mode
    if (viewMode.value === 'personal') {
      const userName = auth.currentUser?.name ?? ''
      if (s.employeeName !== userName) return false
    } else if (viewMode.value === 'all') {
      // Show all employees
      if (selectedEmployee.value !== 'all' && s.employeeNo !== selectedEmployee.value) {
        return false
      }
    } else if (viewMode.value === 'department') {
      // Show only selected department
      if (filterDept.value && s.department !== filterDept.value) {
        return false
      }
    }
    
    // Rest of the filtering logic
    if (!(s.days ?? []).includes(dayName)) return false
    if (s.effectiveDate) {
      const eff = new Date(s.effectiveDate); eff.setHours(0, 0, 0, 0)
      if (colDate < eff) return false
    }
    if (s.endDate) {
      const end = new Date(s.endDate); end.setHours(23, 59, 59, 999)
      if (colDate > end) return false
    }
    return true
  })
}

function schedulesForDate(date) {
  if (!date) return []
  const dayName  = ALL_DAYS[date.getDay() === 0 ? 6 : date.getDay() - 1]
  
  return store.schedules.filter(s => {
    // Same filtering logic as schedulesForColumn
    if (viewMode.value === 'personal') {
      const userName = auth.currentUser?.name ?? ''
      if (s.employeeName !== userName) return false
    } else if (viewMode.value === 'all') {
      if (selectedEmployee.value !== 'all' && s.employeeNo !== selectedEmployee.value) {
        return false
      }
    } else if (viewMode.value === 'department') {
      if (filterDept.value && s.department !== filterDept.value) {
        return false
      }
    }
    
    if (!(s.days ?? []).includes(dayName)) return false
    if (s.effectiveDate) {
      const eff = new Date(s.effectiveDate); eff.setHours(0,0,0,0)
      if (date < eff) return false
    }
    if (s.endDate) {
      const end = new Date(s.endDate); end.setHours(23,59,59,999)
      if (date > end) return false
    }
    return true
  })
}
</script>

<template>
  <div class="page">
    
    <!-- Enhanced Toolbar with View Mode Selector -->
    <div class="toolbar">
      <div class="toolbar-left">
        <!-- View Mode Selector -->
        <div class="view-mode-selector">
          <button 
            class="view-mode-btn"
            :class="{ active: viewMode === 'personal' }"
            @click="viewMode = 'personal'"
          >
            <UserIcon /> My Schedule
          </button>
          <button 
            class="view-mode-btn"
            :class="{ active: viewMode === 'all' }"
            @click="viewMode = 'all'"
            v-if="hasPermission('Schedule Database', 'View')"
          >
            <UsersIcon /> All Employees
          </button>
          <button 
            class="view-mode-btn"
            :class="{ active: viewMode === 'department' }"
            @click="viewMode = 'department'"
            v-if="hasPermission('Schedule Database', 'View')"
          >
            <BuildingIcon /> By Department
          </button>
        </div>
        
        <!-- Employee Selector (shown when viewMode is 'all') -->
        <select 
          v-if="viewMode === 'all'"
          v-model="selectedEmployee"
          class="employee-selector"
        >
          <option value="all">All Employees ({{ employeeList.length }})</option>
          <option 
            v-for="emp in employeeList" 
            :key="emp.employeeNo"
            :value="emp.employeeNo"
          >
            {{ emp.employeeName }} ({{ emp.employeeNo }})
          </option>
        </select>
        
        <!-- Department Selector (shown when viewMode is 'department') -->
        <select 
          v-if="viewMode === 'department'"
          v-model="filterDept"
          class="department-selector"
        >
          <option value="">Select Department</option>
          <option v-for="dept in empStore.departments" :key="dept" :value="dept">
            {{ dept }}
          </option>
        </select>
        
        <div class="search-wrap">
          <span class="icon-svg search-icon" v-html="svgIcons.search"></span>
          <input v-model="search" class="search-input" placeholder="Search employee..." />
        </div>
      </div>
      
      <div class="toolbar-right">
        <button class="btn btn-secondary" @click="exportToPDF">
          <span class="icon-svg" v-html="svgIcons.print"></span> Export PDF
        </button>
        <button v-if="hasPermission('Schedule Database', 'Add')" class="btn btn-primary" @click="openAdd">
          <span class="icon-svg" v-html="svgIcons.add"></span> Add Schedule
        </button>
      </div>
    </div>
    
    <!-- Rest of the calendar... -->
  </div>
</template>

<style scoped>
.view-mode-selector {
  display: flex;
  gap: 4px;
  background: #f0f4f8;
  padding: 4px;
  border-radius: 8px;
}

.view-mode-btn {
  padding: 8px 16px;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.view-mode-btn:hover {
  background: #e0e8f0;
  color: #1a3a5c;
}

.view-mode-btn.active {
  background: #1a3a5c;
  color: #fff;
}

.employee-selector,
.department-selector {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  min-width: 250px;
}

.employee-selector:focus,
.department-selector:focus {
  border-color: #1a3a5c;
}
</style>
```

---

## Option 2: Multi-Row Calendar (Advanced)

For showing **multiple employees simultaneously** in a Google Calendar style:

```vue
<template>
  <div class="multi-employee-calendar">
    <!-- Header with days -->
    <div class="calendar-header">
      <div class="employee-column-header">Employee</div>
      <div v-for="day in weekDays" :key="day" class="day-header">
        {{ formatDay(day) }}
      </div>
    </div>
    
    <!-- Rows for each employee -->
    <div 
      v-for="employee in visibleEmployees" 
      :key="employee.employeeNo"
      class="employee-row"
    >
      <div class="employee-info">
        <div class="employee-name">{{ employee.employeeName }}</div>
        <div class="employee-dept">{{ employee.department }}</div>
      </div>
      
      <div 
        v-for="(day, idx) in weekDays" 
        :key="idx"
        class="day-cell"
      >
        <div 
          v-for="schedule in getSchedulesForEmployeeAndDay(employee.employeeNo, idx)"
          :key="schedule.id"
          class="schedule-block"
          :class="shiftColor(schedule.shift)"
          @click="openEdit(schedule)"
        >
          {{ schedule.shiftTime }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Get unique employees with schedules
const visibleEmployees = computed(() => {
  const unique = new Map()
  
  // Filter schedules based on current filters
  const filtered = store.schedules.filter(s => {
    if (filterDept.value && s.department !== filterDept.value) return false
    if (search.value && !s.employeeName.toLowerCase().includes(search.value.toLowerCase())) return false
    return true
  })
  
  // Get unique employees
  filtered.forEach(s => {
    if (!unique.has(s.employeeNo)) {
      unique.set(s.employeeNo, {
        employeeNo: s.employeeNo,
        employeeName: s.employeeName,
        department: s.department
      })
    }
  })
  
  return Array.from(unique.values())
    .sort((a, b) => a.employeeName.localeCompare(b.employeeName))
    .slice(0, 20) // Limit to 20 employees for performance
})

function getSchedulesForEmployeeAndDay(employeeNo, dayIndex) {
  const colDate = weekDays.value[dayIndex]
  const dayName = ALL_DAYS[dayIndex]
  
  return store.schedules.filter(s => {
    if (s.employeeNo !== employeeNo) return false
    if (!(s.days ?? []).includes(dayName)) return false
    
    // Date range check
    if (s.effectiveDate) {
      const eff = new Date(s.effectiveDate)
      eff.setHours(0, 0, 0, 0)
      if (colDate < eff) return false
    }
    if (s.endDate) {
      const end = new Date(s.endDate)
      end.setHours(23, 59, 59, 999)
      if (colDate > end) return false
    }
    
    return true
  })
}
</script>

<style scoped>
.multi-employee-calendar {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}

.calendar-header {
  display: grid;
  grid-template-columns: 200px repeat(7, 1fr);
  background: #1a3a5c;
  color: #fff;
  font-weight: 600;
  font-size: 13px;
}

.employee-column-header,
.day-header {
  padding: 12px;
  text-align: center;
  border-right: 1px solid rgba(255,255,255,0.1);
}

.employee-row {
  display: grid;
  grid-template-columns: 200px repeat(7, 1fr);
  border-bottom: 1px solid #f0f4f8;
  min-height: 60px;
}

.employee-row:hover {
  background: #f9fafb;
}

.employee-info {
  padding: 12px;
  border-right: 1px solid #f0f4f8;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.employee-name {
  font-size: 13px;
  font-weight: 600;
  color: #1a1a2e;
}

.employee-dept {
  font-size: 11px;
  color: #888;
  margin-top: 2px;
}

.day-cell {
  padding: 8px;
  border-right: 1px solid #f0f4f8;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.schedule-block {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.schedule-block:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
</style>
```

---

## Option 3: Department Grid View

Show all employees in a department with compact schedule indicators:

```vue
<template>
  <div class="department-grid">
    <div 
      v-for="employee in departmentEmployees" 
      :key="employee.employeeNo"
      class="employee-card"
    >
      <div class="employee-header">
        <h4>{{ employee.employeeName }}</h4>
        <span class="employee-no">{{ employee.employeeNo }}</span>
      </div>
      
      <div class="schedule-summary">
        <div 
          v-for="schedule in getEmployeeSchedules(employee.employeeNo)"
          :key="schedule.id"
          class="schedule-item"
        >
          <span class="shift-badge" :class="shiftColor(schedule.shift)">
            {{ schedule.shift }}
          </span>
          <span class="schedule-days">
            {{ schedule.days.join(', ') }}
          </span>
          <span class="schedule-time">
            {{ schedule.shiftTime }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.department-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
  padding: 16px;
}

.employee-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.2s;
}

.employee-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  transform: translateY(-2px);
}

.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f4f8;
}

.employee-header h4 {
  margin: 0;
  font-size: 14px;
  color: #1a1a2e;
}

.employee-no {
  font-size: 11px;
  color: #888;
  font-family: monospace;
}

.schedule-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.schedule-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 12px;
}

.schedule-days {
  color: #666;
  font-size: 11px;
}

.schedule-time {
  color: #888;
  font-size: 11px;
  font-family: monospace;
}
</style>
```

---

## Recommendation

**Implement Option 1 (Employee Selector)** because:

1. ✅ **Easy to implement** - Minimal code changes
2. ✅ **Clean UI** - Doesn't clutter the interface
3. ✅ **Flexible** - Can switch between personal, all, or department view
4. ✅ **Performance** - No issues with many employees
5. ✅ **Mobile-friendly** - Works on all screen sizes

**Then add Option 2 (Multi-Row)** as an alternative view mode for HR staff who need to see multiple employees at once.

---

## Implementation Steps

1. Add view mode selector (Personal / All / Department)
2. Add employee dropdown when "All" is selected
3. Update `schedulesForColumn()` and `schedulesForDate()` to respect view mode
4. Add permission check (only HR/Admin can see all employees)
5. Test with multiple employees
6. (Optional) Add multi-row view as alternative

Would you like me to implement Option 1 now?
