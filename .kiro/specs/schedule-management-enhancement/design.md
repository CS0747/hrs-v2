# Schedule Management System Enhancement - Design Document

## 1. Overview

### 1.1 Purpose
This design document specifies the technical architecture and implementation approach for enhancing the Schedule Database module with calendar-based scheduling, color-coded shift legends, department monitoring, and professional printing capabilities.

### 1.2 Design Goals
- **Intuitive Interaction**: Calendar-first interface reduces cognitive load and clicks
- **Visual Clarity**: Color-coded shifts provide instant recognition across all views
- **Scalable Architecture**: Component-based design supports future enhancements
- **Print Excellence**: Professional layouts optimized for physical distribution
- **Performance**: Responsive UI with efficient data handling for 500+ employees

### 1.3 Technology Stack
- **Frontend**: Vue.js 3 (Composition API), Pinia state management
- **Backend**: PHP 8.x with MySQL 8.x
- **UI Components**: Custom calendar components, existing AppSelect/AppModal
- **Printing**: Browser-native print with CSS @page rules
- **Date Handling**: Native JavaScript Date API

### 1.4 Key Design Decisions

**Decision 1: Native Calendar Implementation**
- **Rationale**: Existing codebase already has calendar logic; extending it avoids external dependencies
- **Trade-off**: More development effort vs. full control and zero bundle size increase

**Decision 2: Server-Side Shift Legend Configuration**
- **Rationale**: Department-specific legends may change; database storage enables runtime updates
- **Trade-off**: Additional API calls vs. flexibility without code deployment

**Decision 3: CSS-Based Print Layouts**
- **Rationale**: Browser print APIs are mature and universally supported
- **Trade-off**: Limited PDF control vs. no server-side PDF generation dependencies


## 2. Architecture

### 2.1 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Calendar   │  │  Monitoring  │  │    Print     │      │
│  │  Component   │  │  Dashboard   │  │   Engine     │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          │                  │                  │
┌─────────┼──────────────────┼──────────────────┼─────────────┐
│         │      State Management Layer (Pinia) │             │
│  ┌──────▼───────┐  ┌──────▼───────┐  ┌───────▼──────┐      │
│  │   Schedule   │  │   Employee   │  │    Legend    │      │
│  │    Store     │  │    Store     │  │    Store     │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          │                  │                  │
┌─────────┼──────────────────┼──────────────────┼─────────────┐
│         │         API Layer (HTTP/JSON)       │             │
│  ┌──────▼───────┐  ┌──────▼───────┐  ┌───────▼──────┐      │
│  │  schedule.   │  │  employees.  │  │   legends.   │      │
│  │     php      │  │     php      │  │     php      │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          │                  │                  │
┌─────────┼──────────────────┼──────────────────┼─────────────┐
│         │         Data Layer (MySQL)          │             │
│  ┌──────▼───────┐  ┌──────▼───────┐  ┌───────▼──────┐      │
│  │  schedules   │  │  employees   │  │shift_legends │      │
│  │    table     │  │    table     │  │    table     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Component Hierarchy

```
ScheduleDatabase.vue (Enhanced)
├── CalendarView
│   ├── CalendarHeader (navigation, view toggle)
│   ├── MiniCalendar (month overview)
│   ├── ShiftLegend (color-coded legend)
│   ├── WeekView
│   │   ├── TimeGrid (hourly slots)
│   │   └── ScheduleBlock (individual shifts)
│   └── MonthView
│       └── DayCell (with shift indicators)
├── ScheduleForm (modal)
│   ├── EmployeeCombobox
│   ├── TimePicker (start/end)
│   ├── ShiftSelector
│   └── DateCalendar (specific date selection)
├── MonitoringDashboard
│   ├── FilterBar
│   ├── DepartmentGroup
│   │   └── ScheduleRow (status indicators)
│   └── SearchBox
└── PrintPreview (generated HTML)
    ├── IndividualSchedulePrint
    ├── DepartmentSchedulePrint
    └── TransmittalReport
```

### 2.3 Data Flow

**Schedule Creation Flow:**
```
User clicks date → CalendarView emits dateSelected
→ ScheduleForm opens with pre-filled date
→ User configures time/shift → Form validates
→ User saves → scheduleStore.addSchedule()
→ API POST /schedule.php → DB insert
→ Store refreshes → Calendar re-renders with new block
→ Notification sent to employee
```

**Print Flow:**
```
User clicks Print → printSchedules() called with filtered data
→ Generate HTML with embedded styles
→ Open new window with print-optimized layout
→ Browser print dialog → Physical/PDF output
```


## 3. Components and Interfaces

### 3.1 Frontend Components

#### 3.1.1 CalendarView Component

**Purpose**: Main calendar interface for visualizing and interacting with schedules

**Props:**
```javascript
{
  view: 'week' | 'month',           // Current view mode
  selectedDate: Date,                // Currently selected date
  schedules: Array<Schedule>,        // Schedules to display
  legends: Array<ShiftLegend>        // Color-coded shift legends
}
```

**Emits:**
```javascript
{
  'date-selected': (date: Date) => void,
  'schedule-clicked': (schedule: Schedule) => void,
  'view-changed': (view: 'week' | 'month') => void
}
```

**Key Methods:**
- `renderWeekView()`: Generates time-grid layout with schedule blocks
- `renderMonthView()`: Generates month calendar with shift indicators
- `calculateBlockPosition(schedule)`: Computes CSS positioning for schedule blocks
- `applyShiftColor(shiftCode, department)`: Returns color from legend

#### 3.1.2 TimePicker Component

**Purpose**: Dual time selector for shift start and end times

**Props:**
```javascript
{
  modelValue: { start: string, end: string },  // HH:MM format
  format: '12h' | '24h',                       // Time format
  suggestions: Array<string>                   // Common times
}
```

**Emits:**
```javascript
{
  'update:modelValue': (value: { start: string, end: string }) => void,
  'shift-detected': (shiftCode: string) => void  // Auto-detection
}
```

**Validation:**
- End time must be after start time
- Times must be valid HH:MM format
- Emit validation errors for parent handling

#### 3.1.3 ShiftLegend Component

**Purpose**: Display color-coded shift legend based on department

**Props:**
```javascript
{
  department: string,                // Current department context
  legends: Array<ShiftLegend>,       // All available legends
  compact: boolean                   // Compact vs. full display
}
```

**Computed:**
- `filteredLegends`: Legends applicable to current department
- `hasMultiColor`: Whether any legend uses split colors

#### 3.1.4 MonitoringDashboard Component

**Purpose**: Department-level schedule overview with status tracking

**Props:**
```javascript
{
  schedules: Array<Schedule>,
  departments: Array<string>,
  filters: { department: string, shift: string, status: string }
}
```

**Emits:**
```javascript
{
  'filter-changed': (filters: object) => void,
  'schedule-selected': (schedule: Schedule) => void
}
```

**Computed:**
- `groupedByDepartment`: Schedules organized by department
- `statusCounts`: Summary statistics per department
- `completionRate`: Percentage of submitted schedules

### 3.2 Backend API Endpoints

#### 3.2.1 Schedule API (Enhanced)

**Endpoint**: `/server/api/schedule.php`

**GET /schedule.php**
- Query params: `?id=<id>` | `?emp=<employeeNo>` | `?dept=<department>` | `?date=<YYYY-MM-DD>`
- Response: `Schedule | Schedule[]`

**POST /schedule.php**
- Body: `{ employeeNo, date, startTime, endTime, shiftCode, specificDates[] }`
- Response: `{ id, message }`
- Side effect: Notification sent to employee

**PUT /schedule.php?id=<id>**
- Body: `{ date, startTime, endTime, shiftCode, ... }`
- Response: `{ message }`
- Side effect: Update notification sent

**DELETE /schedule.php?id=<id>**
- Response: `{ message }`

#### 3.2.2 Shift Legends API (New)

**Endpoint**: `/server/api/shift_legends.php`

**GET /shift_legends.php**
- Query params: `?department=<dept>` (optional)
- Response: `ShiftLegend[]`

**POST /shift_legends.php** (Admin only)
- Body: `{ code, department, timeRange, colorPrimary, colorSecondary }`
- Response: `{ id, message }`

**PUT /shift_legends.php?id=<id>** (Admin only)
- Body: `{ code, department, timeRange, colorPrimary, colorSecondary }`
- Response: `{ message }`

### 3.3 State Management (Pinia Stores)

#### 3.3.1 Schedule Store (Enhanced)

```javascript
export const useScheduleStore = defineStore('schedule', () => {
  const schedules = ref([])
  const legends = ref([])
  const loading = ref(false)
  
  // New methods
  async function fetchLegends(department = null)
  async function addScheduleWithDates(schedule, specificDates)
  async function getSchedulesByDateRange(startDate, endDate)
  function getSchedulesForDate(date, employeeId = null)
  function getShiftColor(shiftCode, department)
  
  return { schedules, legends, ... }
})
```

#### 3.3.2 Legend Store (New)

```javascript
export const useLegendStore = defineStore('legend', () => {
  const legends = ref([])
  const departmentLegends = computed(() => groupByDepartment(legends.value))
  
  async function fetchLegends()
  async function addLegend(legend)
  async function updateLegend(id, data)
  function getLegendForShift(shiftCode, department)
  function getColorForShift(shiftCode, department)
  
  return { legends, departmentLegends, ... }
})
```


## 4. Data Models

### 4.1 Database Schema

#### 4.1.1 schedules Table (Enhanced)

```sql
CREATE TABLE schedules (
  id INT PRIMARY KEY AUTO_INCREMENT,
  employee_id INT,
  employee_no VARCHAR(50),
  employee_name VARCHAR(255),
  department VARCHAR(100),
  
  -- Enhanced fields
  schedule_date DATE NOT NULL,           -- Specific date for this schedule
  start_time TIME NOT NULL,              -- Shift start time
  end_time TIME NOT NULL,                -- Shift end time
  shift_code VARCHAR(10) NOT NULL,       -- 62, 210, 106, 610, 26, 85, OFF
  shift_name VARCHAR(50),                -- Morning, Evening, Night, Custom, OFF
  
  -- Legacy fields (deprecated but kept for migration)
  shift VARCHAR(50),                     -- Old shift field
  shift_time VARCHAR(100),               -- Old shift time field
  days JSON,                             -- Old days array
  effective_date DATE,                   -- Old effective date
  end_date DATE,                         -- Old end date
  rest_day VARCHAR(100),                 -- Old rest day field
  
  -- Status tracking
  status ENUM('Submitted', 'Pending', 'Missing') DEFAULT 'Pending',
  submitted_date DATETIME,
  last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT,
  remarks TEXT,
  
  -- Indexes
  INDEX idx_employee (employee_id),
  INDEX idx_date (schedule_date),
  INDEX idx_department (department),
  INDEX idx_status (status),
  
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### 4.1.2 shift_legends Table (New)

```sql
CREATE TABLE shift_legends (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(10) NOT NULL,             -- 62, 210, 106, 610, 26, 85, OFF
  department VARCHAR(100),               -- NULL for standard, specific for dept-specific
  time_range VARCHAR(50) NOT NULL,       -- "6:00 AM - 2:00 PM"
  color_primary VARCHAR(7) NOT NULL,     -- Hex color #2196F3
  color_secondary VARCHAR(7),            -- For split shifts (610, 26)
  display_order INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY unique_code_dept (code, department),
  INDEX idx_department (department),
  INDEX idx_active (active)
);
```

#### 4.1.3 schedule_transmittals Table (New)

```sql
CREATE TABLE schedule_transmittals (
  id INT PRIMARY KEY AUTO_INCREMENT,
  department VARCHAR(100) NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  page_number INT DEFAULT 1,
  staff_count INT DEFAULT 0,
  submitted_count INT DEFAULT 0,
  date_submitted DATE,
  remarks TEXT,
  generated_by INT,
  generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_department (department),
  INDEX idx_period (period_start, period_end),
  FOREIGN KEY (generated_by) REFERENCES users(id)
);
```

### 4.2 TypeScript Interfaces (Frontend)

```typescript
interface Schedule {
  id: number
  employeeId: number
  employeeNo: string
  employeeName: string
  department: string
  scheduleDate: string          // YYYY-MM-DD
  startTime: string             // HH:MM
  endTime: string               // HH:MM
  shiftCode: string             // 62, 210, 106, 610, 26, 85, OFF
  shiftName: string             // Morning, Evening, Night, Custom, OFF
  status: 'Submitted' | 'Pending' | 'Missing'
  submittedDate?: string
  lastUpdated: string
  remarks?: string
}

interface ShiftLegend {
  id: number
  code: string                  // 62, 210, 106, etc.
  department: string | null     // null = standard legend
  timeRange: string             // "6:00 AM - 2:00 PM"
  colorPrimary: string          // #2196F3
  colorSecondary?: string       // For split shifts
  displayOrder: number
  active: boolean
}

interface ScheduleFormData {
  employeeNo: string
  employeeName: string
  department: string
  scheduleDate: string
  startTime: string
  endTime: string
  shiftCode: string
  shiftName: string
  specificDates: string[]       // Additional dates for bulk assignment
  remarks?: string
}

interface PrintOptions {
  title: string
  orientation: 'portrait' | 'landscape'
  includeLegend: boolean
  filters: Record<string, string>
  dateRange?: string
}

interface TransmittalData {
  department: string
  pageNumber: number
  staffCount: number
  submittedCount: number
  dateSubmitted: string
  remarks: string
}
```

### 4.3 Data Validation Rules

**Schedule Validation:**
- `scheduleDate`: Required, must be valid date, cannot be more than 6 months in future
- `startTime`: Required, valid HH:MM format
- `endTime`: Required, valid HH:MM format, must be after startTime
- `shiftCode`: Required, must match existing legend code
- `employeeNo`: Required, must exist in employees table
- `department`: Required, must match employee's department

**Legend Validation:**
- `code`: Required, 2-10 characters, alphanumeric
- `timeRange`: Required, format "HH:MM AM/PM - HH:MM AM/PM"
- `colorPrimary`: Required, valid hex color #RRGGBB
- `colorSecondary`: Optional, valid hex color if provided
- `department`: Optional, must exist in departments table if provided


## 5. Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection Analysis

After analyzing all acceptance criteria, I identified the following redundancies:
- **Time validation properties** (2.5, FR-2.2, BR-2) all test that end time > start time → Combined into Property 1
- **Color consistency properties** (3.4, 6.8, 9.4, FR-3.3) all test that colors match legends across views → Combined into Property 2
- **Legend presence properties** (5.3, 5.4, 9.5) all test that legend appears in views → Combined into Property 3
- **Data completeness properties** (9.3, 10.2) test that print output contains all required data → Combined into Property 4
- **Migration properties** (4.4, 4.5) both test data migration correctness → Combined into Property 5

### Property 1: Time Range Validity
*For any* schedule with a non-OFF shift code, the end time must be strictly after the start time.

**Validates: Requirements 2.5, FR-2.2, BR-2**

### Property 2: Shift Color Consistency
*For any* shift code and department combination, the displayed color must be identical across all views (calendar, table, print output) and must match the color defined in the shift legend for that department.

**Validates: Requirements 3.4, 6.8, 9.4, FR-3.3**

### Property 3: Legend Visibility
*For any* schedule view (calendar, monitoring dashboard, print output), the shift legend must be present and contain all shift codes that appear in the displayed schedules.

**Validates: Requirements 5.3, 5.4, 9.5**

### Property 4: Schedule Uniqueness Per Date
*For any* employee and date combination, there exists at most one schedule record in the system.

**Validates: Requirements BR-1**

### Property 5: Calendar Month Completeness
*For any* month and year, the calendar must display exactly the correct number of days for that month (accounting for leap years).

**Validates: Requirements 1.1**

### Property 6: Date Navigation Correctness
*For any* displayed month, navigating to the next month should display the immediately following month, and navigating to the previous month should display the immediately preceding month.

**Validates: Requirements 1.4**

### Property 7: Schedule Indicator Accuracy
*For any* date displayed in the calendar, if one or more schedules exist for that date, a visual indicator must be present; if no schedules exist, no indicator should be present.

**Validates: Requirements 1.5**

### Property 8: Time Format Consistency
*For any* time value displayed in the system, it must be formatted as 12-hour time with AM/PM indicators (e.g., "2:00 PM").

**Validates: Requirements 2.3**

### Property 9: Shift Auto-Detection
*For any* time range that matches a predefined shift pattern (e.g., 6:00 AM - 2:00 PM), the system should automatically suggest the corresponding shift code (e.g., "62").

**Validates: Requirements 3.3**

### Property 10: Data Migration Preservation
*For any* schedule in the legacy format (with days array, effective_date, end_date), migrating to the new format (with schedule_date, start_time, end_time) must preserve all original information without data loss.

**Validates: Requirements 4.4, 4.5**

### Property 11: Department Grouping Correctness
*For any* set of schedules, when grouped by department, each schedule must appear in exactly one department group, and that group must match the schedule's department field.

**Validates: Requirements 7.2**

### Property 12: Filter Combination Correctness
*For any* combination of filters (department, shift, status), the filtered results must include only schedules that match all active filter criteria simultaneously.

**Validates: Requirements 8.1, 8.3**

### Property 13: Search Result Accuracy
*For any* search query string, the search results must include all and only those schedules whose employee name contains the query string (case-insensitive).

**Validates: Requirements 8.2**

### Property 14: Print Output Completeness
*For any* schedule included in a print operation, the printed output must contain all required fields: employee name, employee number, department, shift code, shift time, and date.

**Validates: Requirements 9.3, 10.2**

### Property 15: Department Legend Specificity
*For any* department, the shift legend displayed must be the department-specific legend if one exists, otherwise the standard legend, and must never mix legends from different departments.

**Validates: Requirements 6.9**

### Property 16: Transmittal Statistics Accuracy
*For any* department and date range, the transmittal report statistics (total staff count, submitted count, pending count) must accurately reflect the actual schedule data for that department and period.

**Validates: Requirements 11.3**

### Property 17: Past Schedule Immutability
*For any* schedule with a date earlier than the current date, all edit and delete operations must be blocked, while read operations remain permitted.

**Validates: Requirements BR-5**

### Property 18: Real-Time Update Propagation
*For any* schedule modification (create, update, delete), all active views displaying that schedule must reflect the change within the next render cycle.

**Validates: Requirements 7.5**


## 6. Error Handling

### 6.1 Frontend Error Handling

#### 6.1.1 Validation Errors

**Time Range Validation:**
```javascript
function validateTimeRange(startTime, endTime) {
  if (!startTime || !endTime) {
    return { valid: false, message: 'Both start and end times are required' }
  }
  
  const start = parseTime(startTime)
  const end = parseTime(endTime)
  
  if (end <= start) {
    return { valid: false, message: 'End time must be after start time' }
  }
  
  return { valid: true }
}
```

**Date Validation:**
```javascript
function validateScheduleDate(date) {
  const today = new Date()
  const scheduleDate = new Date(date)
  const sixMonthsFromNow = new Date()
  sixMonthsFromNow.setMonth(today.getMonth() + 6)
  
  if (scheduleDate > sixMonthsFromNow) {
    return { valid: false, message: 'Cannot schedule more than 6 months in advance' }
  }
  
  return { valid: true }
}
```

**Duplicate Schedule Check:**
```javascript
function checkDuplicateSchedule(employeeId, date, existingSchedules) {
  const duplicate = existingSchedules.find(s => 
    s.employeeId === employeeId && 
    s.scheduleDate === date
  )
  
  if (duplicate) {
    return { 
      valid: false, 
      message: `A schedule already exists for this employee on ${date}` 
    }
  }
  
  return { valid: true }
}
```

#### 6.1.2 API Error Handling

**Network Errors:**
```javascript
async function fetchSchedules() {
  try {
    const response = await fetch(API_URL)
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    return await response.json()
  } catch (error) {
    if (error.name === 'TypeError') {
      // Network error
      notificationStore.error('Network error. Please check your connection.')
    } else {
      notificationStore.error(`Failed to load schedules: ${error.message}`)
    }
    return []
  }
}
```

**Permission Errors:**
```javascript
function handlePermissionError(action) {
  notificationStore.error(
    `You don't have permission to ${action} schedules. ` +
    `Please contact your administrator.`
  )
}
```

#### 6.1.3 User Input Errors

**Empty Required Fields:**
- Display inline error messages below each field
- Disable save button until all required fields are valid
- Highlight invalid fields with red border

**Invalid Time Format:**
- Show format hint: "HH:MM AM/PM"
- Auto-correct common mistakes (e.g., "2pm" → "2:00 PM")
- Validate on blur and before save

### 6.2 Backend Error Handling

#### 6.2.1 Database Errors

**Connection Errors:**
```php
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            sendError('Database connection failed', 503);
        }
        return $conn;
    } catch (Exception $e) {
        error_log("DB Exception: " . $e->getMessage());
        sendError('Database unavailable', 503);
    }
}
```

**Constraint Violations:**
```php
function handleDuplicateSchedule($conn, $employeeId, $date) {
    $stmt = $conn->prepare(
        'SELECT id FROM schedules WHERE employee_id = ? AND schedule_date = ?'
    );
    $stmt->bind_param('is', $employeeId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendError('A schedule already exists for this employee on this date', 409);
    }
}
```

**Foreign Key Violations:**
```php
try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1452) { // Foreign key constraint
        sendError('Invalid employee ID', 400);
    } else {
        error_log("SQL Error: " . $e->getMessage());
        sendError('Database operation failed', 500);
    }
}
```

#### 6.2.2 Validation Errors

**Input Validation:**
```php
function validateScheduleData($data) {
    $errors = [];
    
    if (empty($data['employeeNo'])) {
        $errors[] = 'Employee number is required';
    }
    
    if (empty($data['scheduleDate'])) {
        $errors[] = 'Schedule date is required';
    } elseif (!validateDate($data['scheduleDate'])) {
        $errors[] = 'Invalid date format';
    }
    
    if (empty($data['startTime']) || empty($data['endTime'])) {
        $errors[] = 'Start and end times are required';
    } elseif ($data['endTime'] <= $data['startTime']) {
        $errors[] = 'End time must be after start time';
    }
    
    if (!empty($errors)) {
        sendError(implode('; ', $errors), 400);
    }
}
```

#### 6.2.3 Permission Errors

**Authorization Check:**
```php
if (!checkPermission($conn, $userId, 'Schedule Database', $action)) {
    error_log("Permission denied: User $userId attempted $action on Schedule Database");
    sendError('Permission denied', 403);
}
```

### 6.3 Error Recovery Strategies

#### 6.3.1 Optimistic UI Updates

**Strategy**: Update UI immediately, rollback on error
```javascript
async function updateSchedule(id, data) {
  const oldSchedule = schedules.value.find(s => s.id === id)
  const optimisticSchedule = { ...oldSchedule, ...data }
  
  // Optimistic update
  const index = schedules.value.findIndex(s => s.id === id)
  schedules.value[index] = optimisticSchedule
  
  try {
    await api.updateSchedule(id, data)
  } catch (error) {
    // Rollback on error
    schedules.value[index] = oldSchedule
    notificationStore.error('Failed to update schedule')
    throw error
  }
}
```

#### 6.3.2 Retry Logic

**Strategy**: Retry failed requests with exponential backoff
```javascript
async function fetchWithRetry(url, options = {}, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url, options)
      if (response.ok) return response
      
      if (response.status >= 500 && i < maxRetries - 1) {
        // Server error, retry
        await delay(Math.pow(2, i) * 1000)
        continue
      }
      
      throw new Error(`HTTP ${response.status}`)
    } catch (error) {
      if (i === maxRetries - 1) throw error
      await delay(Math.pow(2, i) * 1000)
    }
  }
}
```

#### 6.3.3 Graceful Degradation

**Strategy**: Provide limited functionality when services are unavailable
```javascript
const scheduleStore = useScheduleStore()

if (scheduleStore.error) {
  // Show cached data with warning
  showWarning('Unable to load latest schedules. Showing cached data.')
  // Disable create/edit/delete actions
  canEdit.value = false
} else {
  canEdit.value = hasPermission('Schedule Database', 'Edit')
}
```

### 6.4 Error Logging

#### 6.4.1 Frontend Logging

```javascript
function logError(context, error, additionalData = {}) {
  const errorLog = {
    timestamp: new Date().toISOString(),
    context,
    message: error.message,
    stack: error.stack,
    user: auth.currentUser?.id,
    ...additionalData
  }
  
  console.error('[Schedule Error]', errorLog)
  
  // Send to backend logging service
  if (import.meta.env.PROD) {
    fetch('/api/client-errors.php', {
      method: 'POST',
      body: JSON.stringify(errorLog)
    }).catch(() => {}) // Silent fail for logging
  }
}
```

#### 6.4.2 Backend Logging

```php
function logError($context, $message, $data = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'context' => $context,
        'message' => $message,
        'user_id' => $_SERVER['HTTP_X_USER_ID'] ?? null,
        'data' => $data
    ];
    
    error_log(json_encode($logEntry));
    
    // Also log to database for audit trail
    $conn = getConnection();
    $stmt = $conn->prepare(
        'INSERT INTO error_logs (context, message, user_id, data) VALUES (?, ?, ?, ?)'
    );
    $dataJson = json_encode($data);
    $stmt->bind_param('ssis', $context, $message, $logEntry['user_id'], $dataJson);
    $stmt->execute();
}
```


## 7. Testing Strategy

### 7.1 Testing Approach

This feature requires a **dual testing approach** combining unit tests and property-based tests:

- **Unit Tests**: Verify specific examples, edge cases, and error conditions
- **Property-Based Tests**: Verify universal properties across all inputs
- Both are complementary and necessary for comprehensive coverage

**Balance Principle**: Avoid writing too many unit tests for scenarios that property-based tests can cover through randomization. Focus unit tests on:
- Specific examples that demonstrate correct behavior
- Integration points between components
- Edge cases and error conditions

Property tests should focus on:
- Universal properties that hold for all inputs
- Comprehensive input coverage through randomization

### 7.2 Property-Based Testing Configuration

**Library Selection**: 
- **JavaScript/Vue**: Use `fast-check` library for property-based testing
- Installation: `npm install --save-dev fast-check`

**Configuration Requirements**:
- Minimum **100 iterations** per property test (due to randomization)
- Each property test must reference its design document property
- Tag format: `// Feature: schedule-management-enhancement, Property {number}: {property_text}`

**Example Property Test Structure**:
```javascript
import fc from 'fast-check'
import { describe, it, expect } from 'vitest'

describe('Schedule Management - Property Tests', () => {
  it('Property 1: Time Range Validity', () => {
    // Feature: schedule-management-enhancement, Property 1: Time Range Validity
    fc.assert(
      fc.property(
        fc.record({
          startTime: fc.integer({ min: 0, max: 23 }).chain(h =>
            fc.integer({ min: 0, max: 59 }).map(m => `${h}:${m}`)
          ),
          endTime: fc.integer({ min: 0, max: 23 }).chain(h =>
            fc.integer({ min: 0, max: 59 }).map(m => `${h}:${m}`)
          ),
          shiftCode: fc.constantFrom('62', '210', '106', '85')
        }),
        (schedule) => {
          const validation = validateTimeRange(schedule.startTime, schedule.endTime)
          if (schedule.shiftCode !== 'OFF') {
            const start = parseTime(schedule.startTime)
            const end = parseTime(schedule.endTime)
            expect(validation.valid).toBe(end > start)
          }
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

### 7.3 Unit Testing Strategy

#### 7.3.1 Component Tests

**CalendarView Component**:
```javascript
describe('CalendarView', () => {
  it('should display correct number of days for February in leap year', () => {
    const wrapper = mount(CalendarView, {
      props: { selectedDate: new Date(2024, 1, 1) }
    })
    const days = wrapper.findAll('.cal-day')
    expect(days.length).toBe(29)
  })
  
  it('should emit date-selected when date is clicked', async () => {
    const wrapper = mount(CalendarView)
    await wrapper.find('.cal-day').trigger('click')
    expect(wrapper.emitted('date-selected')).toBeTruthy()
  })
  
  it('should show indicator on dates with schedules', () => {
    const schedules = [{ scheduleDate: '2024-05-15', employeeId: 1 }]
    const wrapper = mount(CalendarView, { props: { schedules } })
    const dayWithSchedule = wrapper.find('[data-date="2024-05-15"]')
    expect(dayWithSchedule.find('.schedule-indicator').exists()).toBe(true)
  })
})
```

**TimePicker Component**:
```javascript
describe('TimePicker', () => {
  it('should show validation error when end time is before start time', async () => {
    const wrapper = mount(TimePicker)
    await wrapper.find('[data-testid="start-time"]').setValue('14:00')
    await wrapper.find('[data-testid="end-time"]').setValue('10:00')
    expect(wrapper.find('.error-message').text()).toContain('End time must be after start time')
  })
  
  it('should format times in 12-hour format with AM/PM', () => {
    const wrapper = mount(TimePicker, {
      props: { modelValue: { start: '14:00', end: '22:00' } }
    })
    expect(wrapper.text()).toContain('2:00 PM')
    expect(wrapper.text()).toContain('10:00 PM')
  })
  
  it('should auto-detect shift based on time range', async () => {
    const wrapper = mount(TimePicker)
    await wrapper.find('[data-testid="start-time"]').setValue('06:00')
    await wrapper.find('[data-testid="end-time"]').setValue('14:00')
    expect(wrapper.emitted('shift-detected')[0]).toEqual(['62'])
  })
})
```

**ShiftLegend Component**:
```javascript
describe('ShiftLegend', () => {
  it('should display nursing-specific legends for nursing department', () => {
    const legends = [
      { code: '62', department: 'Nursing', colorPrimary: '#2196F3' },
      { code: '85', department: null, colorPrimary: '#000000' }
    ]
    const wrapper = mount(ShiftLegend, {
      props: { department: 'Nursing', legends }
    })
    expect(wrapper.text()).toContain('62')
    expect(wrapper.text()).not.toContain('85')
  })
  
  it('should display standard legends for non-nursing departments', () => {
    const legends = [
      { code: '62', department: 'Nursing', colorPrimary: '#2196F3' },
      { code: '85', department: null, colorPrimary: '#000000' }
    ]
    const wrapper = mount(ShiftLegend, {
      props: { department: 'Administration', legends }
    })
    expect(wrapper.text()).toContain('85')
    expect(wrapper.text()).not.toContain('62')
  })
})
```

#### 7.3.2 Store Tests

**Schedule Store**:
```javascript
describe('useScheduleStore', () => {
  it('should add schedule and refresh list', async () => {
    const store = useScheduleStore()
    const initialCount = store.schedules.length
    
    await store.addSchedule({
      employeeNo: 'TEST-001',
      scheduleDate: '2024-05-20',
      startTime: '08:00',
      endTime: '17:00',
      shiftCode: '85'
    })
    
    expect(store.schedules.length).toBe(initialCount + 1)
  })
  
  it('should prevent duplicate schedules for same employee and date', async () => {
    const store = useScheduleStore()
    const schedule = {
      employeeNo: 'TEST-001',
      scheduleDate: '2024-05-20',
      startTime: '08:00',
      endTime: '17:00',
      shiftCode: '85'
    }
    
    await store.addSchedule(schedule)
    await expect(store.addSchedule(schedule)).rejects.toThrow('already exists')
  })
})
```

#### 7.3.3 API Integration Tests

**Schedule API**:
```javascript
describe('Schedule API', () => {
  it('should create schedule with valid data', async () => {
    const response = await fetch('/api/schedule.php', {
      method: 'POST',
      body: JSON.stringify({
        employeeNo: 'TEST-001',
        scheduleDate: '2024-05-20',
        startTime: '08:00',
        endTime: '17:00',
        shiftCode: '85'
      })
    })
    
    expect(response.status).toBe(201)
    const data = await response.json()
    expect(data.id).toBeDefined()
  })
  
  it('should return 400 for invalid time range', async () => {
    const response = await fetch('/api/schedule.php', {
      method: 'POST',
      body: JSON.stringify({
        employeeNo: 'TEST-001',
        scheduleDate: '2024-05-20',
        startTime: '17:00',
        endTime: '08:00',
        shiftCode: '85'
      })
    })
    
    expect(response.status).toBe(400)
  })
  
  it('should return 409 for duplicate schedule', async () => {
    const schedule = {
      employeeNo: 'TEST-001',
      scheduleDate: '2024-05-20',
      startTime: '08:00',
      endTime: '17:00',
      shiftCode: '85'
    }
    
    await fetch('/api/schedule.php', { method: 'POST', body: JSON.stringify(schedule) })
    const response = await fetch('/api/schedule.php', { method: 'POST', body: JSON.stringify(schedule) })
    
    expect(response.status).toBe(409)
  })
})
```

### 7.4 Property-Based Test Cases

#### 7.4.1 Core Properties

**Property 1: Time Range Validity**
```javascript
it('Property 1: Time Range Validity', () => {
  // Feature: schedule-management-enhancement, Property 1: Time Range Validity
  fc.assert(
    fc.property(
      fc.record({
        startHour: fc.integer({ min: 0, max: 23 }),
        startMin: fc.integer({ min: 0, max: 59 }),
        endHour: fc.integer({ min: 0, max: 23 }),
        endMin: fc.integer({ min: 0, max: 59 }),
        shiftCode: fc.constantFrom('62', '210', '106', '85', '610', '26')
      }),
      (data) => {
        const startTime = `${data.startHour}:${data.startMin}`
        const endTime = `${data.endHour}:${data.endMin}`
        const validation = validateTimeRange(startTime, endTime)
        
        const startMinutes = data.startHour * 60 + data.startMin
        const endMinutes = data.endHour * 60 + data.endMin
        
        if (endMinutes > startMinutes) {
          expect(validation.valid).toBe(true)
        } else {
          expect(validation.valid).toBe(false)
        }
      }
    ),
    { numRuns: 100 }
  )
})
```

**Property 2: Shift Color Consistency**
```javascript
it('Property 2: Shift Color Consistency', () => {
  // Feature: schedule-management-enhancement, Property 2: Shift Color Consistency
  fc.assert(
    fc.property(
      fc.record({
        shiftCode: fc.constantFrom('62', '210', '106', '85', 'OFF'),
        department: fc.constantFrom('Nursing', 'Administration', 'Pharmacy'),
        views: fc.array(fc.constantFrom('calendar', 'table', 'print'), { minLength: 2, maxLength: 3 })
      }),
      (data) => {
        const colors = data.views.map(view => 
          getShiftColor(data.shiftCode, data.department, view)
        )
        
        // All colors should be identical
        const firstColor = colors[0]
        expect(colors.every(c => c === firstColor)).toBe(true)
      }
    ),
    { numRuns: 100 }
  )
})
```

**Property 4: Schedule Uniqueness Per Date**
```javascript
it('Property 4: Schedule Uniqueness Per Date', () => {
  // Feature: schedule-management-enhancement, Property 4: Schedule Uniqueness Per Date
  fc.assert(
    fc.property(
      fc.record({
        employeeId: fc.integer({ min: 1, max: 100 }),
        date: fc.date({ min: new Date('2024-01-01'), max: new Date('2024-12-31') })
      }),
      (data) => {
        const schedules = getSchedulesForEmployee(data.employeeId)
        const dateStr = data.date.toISOString().split('T')[0]
        const schedulesOnDate = schedules.filter(s => s.scheduleDate === dateStr)
        
        expect(schedulesOnDate.length).toBeLessThanOrEqual(1)
      }
    ),
    { numRuns: 100 }
  )
})
```

**Property 5: Calendar Month Completeness**
```javascript
it('Property 5: Calendar Month Completeness', () => {
  // Feature: schedule-management-enhancement, Property 5: Calendar Month Completeness
  fc.assert(
    fc.property(
      fc.record({
        year: fc.integer({ min: 2020, max: 2030 }),
        month: fc.integer({ min: 0, max: 11 })
      }),
      (data) => {
        const calendar = generateCalendar(data.year, data.month)
        const expectedDays = new Date(data.year, data.month + 1, 0).getDate()
        
        expect(calendar.days.length).toBe(expectedDays)
      }
    ),
    { numRuns: 100 }
  )
})
```

**Property 12: Filter Combination Correctness**
```javascript
it('Property 12: Filter Combination Correctness', () => {
  // Feature: schedule-management-enhancement, Property 12: Filter Combination Correctness
  fc.assert(
    fc.property(
      fc.record({
        department: fc.option(fc.constantFrom('Nursing', 'Administration', 'Pharmacy')),
        shift: fc.option(fc.constantFrom('62', '210', '106', '85')),
        status: fc.option(fc.constantFrom('Submitted', 'Pending', 'Missing'))
      }),
      (filters) => {
        const allSchedules = getAllSchedules()
        const filtered = applyFilters(allSchedules, filters)
        
        // Every filtered schedule must match all active filters
        filtered.forEach(schedule => {
          if (filters.department) {
            expect(schedule.department).toBe(filters.department)
          }
          if (filters.shift) {
            expect(schedule.shiftCode).toBe(filters.shift)
          }
          if (filters.status) {
            expect(schedule.status).toBe(filters.status)
          }
        })
      }
    ),
    { numRuns: 100 }
  )
})
```

### 7.5 Integration Testing

**End-to-End Schedule Creation Flow**:
```javascript
describe('Schedule Creation E2E', () => {
  it('should create schedule from calendar click to database', async () => {
    // 1. Click calendar date
    await page.click('[data-date="2024-05-20"]')
    
    // 2. Form should open
    await page.waitForSelector('.schedule-form')
    
    // 3. Fill form
    await page.type('[data-testid="employee-search"]', 'TEST-001')
    await page.click('.emp-option:first-child')
    await page.type('[data-testid="start-time"]', '08:00 AM')
    await page.type('[data-testid="end-time"]', '05:00 PM')
    await page.select('[data-testid="shift-select"]', '85')
    
    // 4. Save
    await page.click('[data-testid="save-button"]')
    
    // 5. Verify schedule appears in calendar
    await page.waitForSelector('[data-date="2024-05-20"] .schedule-block')
    
    // 6. Verify in database
    const schedule = await db.query(
      'SELECT * FROM schedules WHERE employee_no = ? AND schedule_date = ?',
      ['TEST-001', '2024-05-20']
    )
    expect(schedule).toBeDefined()
  })
})
```

### 7.6 Performance Testing

**Load Testing**:
```javascript
describe('Performance Tests', () => {
  it('should render calendar with 500 schedules in under 1 second', async () => {
    const schedules = generateRandomSchedules(500)
    const startTime = performance.now()
    
    const wrapper = mount(CalendarView, { props: { schedules } })
    await wrapper.vm.$nextTick()
    
    const endTime = performance.now()
    expect(endTime - startTime).toBeLessThan(1000)
  })
  
  it('should filter 1000 schedules in under 500ms', () => {
    const schedules = generateRandomSchedules(1000)
    const filters = { department: 'Nursing', shift: '62' }
    
    const startTime = performance.now()
    const filtered = applyFilters(schedules, filters)
    const endTime = performance.now()
    
    expect(endTime - startTime).toBeLessThan(500)
  })
})
```

### 7.7 Test Coverage Goals

- **Unit Test Coverage**: Minimum 80% code coverage
- **Property Test Coverage**: All 18 correctness properties must have corresponding tests
- **Integration Test Coverage**: All critical user flows (create, edit, delete, print)
- **Edge Case Coverage**: All error conditions and boundary cases

### 7.8 Continuous Integration

**Test Execution in CI/CD**:
```yaml
# .github/workflows/test.yml
name: Test Schedule Management

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm install
      - run: npm run test:unit
      - run: npm run test:property
      - run: npm run test:integration
      - run: npm run test:coverage
```


## 8. Implementation Plan

### 8.1 Phase 1: Database and Backend (Week 1)

#### 8.1.1 Database Schema Updates
- Create `shift_legends` table
- Add new columns to `schedules` table (schedule_date, start_time, end_time, shift_code)
- Create `schedule_transmittals` table
- Add indexes for performance
- Write migration script to preserve existing data

#### 8.1.2 Backend API Development
- Enhance `schedule.php` with new endpoints
- Create `shift_legends.php` API
- Add validation for time ranges and duplicate schedules
- Implement permission checks
- Add error handling and logging

#### 8.1.3 Data Migration
- Script to convert legacy schedules (days array) to new format (specific dates)
- Populate `shift_legends` table with standard and nursing-specific legends
- Verify data integrity after migration

### 8.2 Phase 2: Core Components (Week 2)

#### 8.2.1 Calendar Components
- Enhance existing CalendarView with improved time grid
- Implement schedule block positioning algorithm
- Add mini-calendar navigation
- Implement week/month view toggle
- Add schedule indicators on dates

#### 8.2.2 Form Components
- Enhance ScheduleForm with new fields
- Create TimePicker component
- Implement shift auto-detection
- Add date calendar for specific date selection
- Implement validation logic

#### 8.2.3 Legend Component
- Create ShiftLegend component
- Implement department-specific legend loading
- Add multi-color support for split shifts
- Ensure legend visibility across all views

### 8.3 Phase 3: State Management (Week 2)

#### 8.3.1 Store Enhancements
- Enhance scheduleStore with new methods
- Create legendStore
- Implement caching for legends
- Add optimistic updates
- Implement error recovery

#### 8.3.2 Composables
- Create `useScheduleCalendar` composable for calendar logic
- Create `useShiftColors` composable for color management
- Create `useScheduleFilters` composable for filtering logic

### 8.4 Phase 4: Monitoring and Filtering (Week 3)

#### 8.4.1 Monitoring Dashboard
- Create MonitoringDashboard component
- Implement department grouping
- Add status indicators
- Implement real-time updates
- Add summary statistics

#### 8.4.2 Filtering System
- Implement multi-filter logic
- Add search functionality
- Create FilterBar component
- Add clear filters functionality
- Implement filter persistence

### 8.5 Phase 5: Printing System (Week 3)

#### 8.5.1 Print Templates
- Create individual schedule print template
- Create department schedule print template
- Create transmittal report template
- Implement color-coded shift rendering in print
- Add legend to print outputs

#### 8.5.2 Print Engine
- Enhance `print.js` utility
- Implement print preview
- Add landscape/portrait orientation support
- Implement page break logic
- Add PDF export support (browser-native)

### 8.6 Phase 6: Testing and QA (Week 4)

#### 8.6.1 Unit Testing
- Write component tests
- Write store tests
- Write utility function tests
- Achieve 80% code coverage

#### 8.6.2 Property-Based Testing
- Implement all 18 property tests
- Configure fast-check with 100 iterations
- Verify all properties pass

#### 8.6.3 Integration Testing
- Test end-to-end flows
- Test API integration
- Test print functionality
- Test cross-browser compatibility

#### 8.6.4 User Acceptance Testing
- Conduct UAT with HR staff
- Gather feedback
- Fix identified issues
- Verify all acceptance criteria

### 8.7 Phase 7: Deployment and Training (Week 5)

#### 8.7.1 Deployment
- Deploy database migrations
- Deploy backend API updates
- Deploy frontend updates
- Verify production environment
- Monitor for errors

#### 8.7.2 Training and Documentation
- Create user manual
- Conduct training sessions
- Create video tutorials
- Provide support during transition
- Gather user feedback

### 8.8 Rollback Plan

**If critical issues are discovered:**

1. **Immediate Actions**:
   - Revert frontend deployment to previous version
   - Keep database changes (backward compatible)
   - Display maintenance message to users

2. **Data Preservation**:
   - All new schedules remain in database
   - Legacy fields still populated for backward compatibility
   - No data loss occurs

3. **Investigation**:
   - Review error logs
   - Identify root cause
   - Develop fix
   - Test fix in staging

4. **Re-deployment**:
   - Deploy fix
   - Verify functionality
   - Resume normal operations

## 9. Security Considerations

### 9.1 Authentication and Authorization

**Permission Checks**:
- All API endpoints verify user permissions before processing
- Frontend components check permissions before rendering actions
- Permissions: View, Add, Edit, Delete for "Schedule Database" module

**Session Management**:
- User ID passed in HTTP headers (X-User-ID)
- Session validation on every API request
- Automatic logout after inactivity

### 9.2 Input Validation

**Frontend Validation**:
- Validate all user inputs before submission
- Sanitize employee search queries
- Validate date formats and ranges
- Validate time formats

**Backend Validation**:
- Re-validate all inputs on server side
- Use prepared statements for all database queries
- Escape special characters in SQL
- Validate foreign key references

### 9.3 Data Protection

**Sensitive Data**:
- Employee schedules are considered sensitive
- Access restricted by department (department heads see only their department)
- Audit logging for all schedule modifications
- No schedule data in client-side logs

**SQL Injection Prevention**:
```php
// Always use prepared statements
$stmt = $conn->prepare('SELECT * FROM schedules WHERE employee_id = ?');
$stmt->bind_param('i', $employeeId);
$stmt->execute();
```

**XSS Prevention**:
```javascript
// Sanitize user input before rendering
function sanitizeHTML(str) {
  const div = document.createElement('div')
  div.textContent = str
  return div.innerHTML
}
```

### 9.4 API Security

**Rate Limiting**:
- Limit API requests to 100 per minute per user
- Return 429 Too Many Requests if exceeded

**CORS Configuration**:
- Restrict API access to known frontend origins
- Validate Origin header on all requests

**Error Messages**:
- Never expose internal system details in error messages
- Log detailed errors server-side only
- Return generic error messages to client

## 10. Performance Optimization

### 10.1 Frontend Optimization

**Component Lazy Loading**:
```javascript
const MonitoringDashboard = defineAsyncComponent(() =>
  import('./components/MonitoringDashboard.vue')
)
```

**Virtual Scrolling**:
- Implement virtual scrolling for large schedule lists
- Render only visible items in viewport
- Improves performance with 500+ schedules

**Memoization**:
```javascript
const filteredSchedules = computed(() => {
  // Expensive filtering operation
  return schedules.value.filter(/* ... */)
})
```

**Debounced Search**:
```javascript
const debouncedSearch = debounce((query) => {
  searchSchedules(query)
}, 300)
```

### 10.2 Backend Optimization

**Database Indexing**:
```sql
CREATE INDEX idx_schedule_date ON schedules(schedule_date);
CREATE INDEX idx_employee_dept ON schedules(employee_id, department);
CREATE INDEX idx_shift_code ON schedules(shift_code);
```

**Query Optimization**:
```php
// Fetch only needed columns
$stmt = $conn->prepare(
  'SELECT id, employee_name, shift_code, schedule_date 
   FROM schedules 
   WHERE department = ? AND schedule_date BETWEEN ? AND ?'
);
```

**Caching**:
```php
// Cache shift legends (rarely change)
$cacheKey = "shift_legends_" . $department;
$legends = $cache->get($cacheKey);
if (!$legends) {
    $legends = fetchLegendsFromDB($department);
    $cache->set($cacheKey, $legends, 3600); // 1 hour
}
```

### 10.3 Print Optimization

**CSS Optimization**:
```css
@media print {
  /* Hide unnecessary elements */
  .no-print { display: none; }
  
  /* Optimize for print */
  * { -webkit-print-color-adjust: exact; }
  
  /* Prevent page breaks inside elements */
  .schedule-block { page-break-inside: avoid; }
}
```

**Lazy Print Generation**:
- Generate print HTML only when print button is clicked
- Don't pre-render print templates
- Close print window after printing

## 11. Accessibility Considerations

### 11.1 Keyboard Navigation

**Calendar Navigation**:
- Arrow keys to navigate between dates
- Enter to select date
- Tab to move between calendar and form
- Escape to close modals

**Form Navigation**:
- Tab order follows logical flow
- All interactive elements keyboard accessible
- Focus indicators clearly visible

### 11.2 Screen Reader Support

**ARIA Labels**:
```html
<button aria-label="Navigate to previous month">‹</button>
<div role="grid" aria-label="Schedule calendar">
  <div role="gridcell" aria-label="May 15, 2024, has 2 schedules">15</div>
</div>
```

**Semantic HTML**:
- Use proper heading hierarchy (h1, h2, h3)
- Use `<table>` for tabular data
- Use `<button>` for actions, not `<div>`

### 11.3 Color Contrast

**WCAG AA Compliance**:
- All text has minimum 4.5:1 contrast ratio
- Shift colors chosen for sufficient contrast
- Status indicators use both color and icons

**Color Blindness**:
- Don't rely solely on color to convey information
- Use patterns or icons in addition to colors
- Test with color blindness simulators

### 11.4 Focus Management

**Modal Focus Trap**:
```javascript
function trapFocus(modal) {
  const focusableElements = modal.querySelectorAll(
    'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  const firstElement = focusableElements[0]
  const lastElement = focusableElements[focusableElements.length - 1]
  
  modal.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault()
        lastElement.focus()
      } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault()
        firstElement.focus()
      }
    }
  })
}
```

## 12. Monitoring and Maintenance

### 12.1 Application Monitoring

**Error Tracking**:
- Log all errors to database
- Monitor error rates
- Alert on critical errors
- Weekly error review

**Performance Monitoring**:
- Track API response times
- Monitor database query performance
- Track frontend render times
- Alert on performance degradation

### 12.2 Usage Analytics

**Track Key Metrics**:
- Number of schedules created per day
- Most used shift codes
- Print frequency
- Filter usage patterns
- User adoption rate

### 12.3 Maintenance Tasks

**Daily**:
- Review error logs
- Monitor system health
- Check backup status

**Weekly**:
- Review performance metrics
- Analyze usage patterns
- Update documentation if needed

**Monthly**:
- Database optimization (ANALYZE, OPTIMIZE)
- Review and archive old schedules
- Update shift legends if needed
- Security audit

**Quarterly**:
- User feedback survey
- Feature enhancement planning
- Performance optimization review
- Dependency updates

## 13. Future Enhancements

### 13.1 Short-term (3-6 months)

- **Bulk Schedule Import**: CSV upload for mass schedule creation
- **Schedule Templates**: Save and reuse common schedule patterns
- **Email Notifications**: Automatic email when schedule is assigned
- **Mobile Responsive**: Optimize for tablet and mobile viewing

### 13.2 Medium-term (6-12 months)

- **Shift Swap Requests**: Allow employees to request shift swaps
- **Overtime Tracking**: Integrate with overtime calculation
- **Leave Integration**: Auto-mark OFF for approved leaves
- **Advanced Analytics**: Dashboard with schedule statistics

### 13.3 Long-term (12+ months)

- **AI Schedule Generation**: Automated schedule creation based on rules
- **Mobile App**: Native iOS/Android app for schedule viewing
- **Calendar Sync**: Integration with Google Calendar, Outlook
- **Multi-language Support**: Support for Filipino and other languages

---

**Document Version**: 1.0  
**Created**: May 18, 2026  
**Last Updated**: May 18, 2026  
**Status**: Draft - Ready for Review  
**Author**: Development Team  
**Reviewers**: HR Department, IT Department, Management

