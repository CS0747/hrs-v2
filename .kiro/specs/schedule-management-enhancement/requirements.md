# Schedule Management System Enhancement - Requirements

## 1. Overview

### 1.1 Purpose
Enhance the Schedule Database module to provide a modern, intuitive calendar-based scheduling interface with improved workflow, standardized shift legends, department monitoring capabilities, and professional printable reports.

### 1.2 Goals
- **Simplify Schedule Creation**: Reduce clicks and redundant fields through calendar-based interaction
- **Standardize Shift Coding**: Implement consistent color-coded shift legends across all departments
- **Improve Monitoring**: Provide department-level schedule oversight and status tracking
- **Enable Professional Reporting**: Generate printable schedules and transmittal documents
- **Enhance User Experience**: Create an intuitive, responsive interface with real-time feedback

### 1.3 Scope
**In Scope:**
- Calendar-based schedule assignment interface
- Time picker integration for shift configuration
- Color-coded shift legend system (standard and nursing-specific)
- Department schedule monitoring dashboard
- Printable schedule reports (individual and department)
- Printable transmittal documents
- UI cleanup (remove redundant fields and buttons)

**Out of Scope:**
- Automated schedule generation/AI scheduling
- Mobile app development
- Integration with external calendar systems (Google Calendar, Outlook)
- Payroll integration
- Shift swap/trade functionality

---

## 2. User Stories

### 2.1 Schedule Creation

**US-1: Calendar-Based Date Selection**
- **As a** schedule administrator
- **I want to** click on a calendar date to assign a schedule
- **So that** I can quickly visualize and assign shifts without navigating through multiple forms

**Acceptance Criteria:**
- Calendar displays current month with all dates visible
- Clicking a date highlights it and opens the schedule configuration panel
- Selected date is clearly indicated with visual feedback
- Calendar supports navigation to previous/next months
- Dates with existing schedules show visual indicators

**US-2: Time Configuration Panel**
- **As a** schedule administrator
- **I want to** configure shift times directly when selecting a date
- **So that** I can set custom start and end times for each schedule

**Acceptance Criteria:**
- Time picker appears immediately when date is selected
- Start time and end time selectors are clearly labeled
- Time format is 12-hour with AM/PM indicators
- Time picker supports both dropdown and manual input
- Invalid time ranges (end before start) show validation errors
- Common shift times are suggested/pre-populated

**US-3: Shift Selection Integration**
- **As a** schedule administrator
- **I want to** select a shift type alongside the time configuration
- **So that** the schedule is properly categorized and color-coded

**Acceptance Criteria:**
- Shift dropdown appears below time picker
- Available shifts: Morning, Evening, Night, Custom, OFF
- Selected shift automatically updates based on time range (if applicable)
- Shift selection updates the schedule preview with correct color coding
- OFF duty option displays with red outlined circle indicator

**US-4: Remove Redundant Fields**
- **As a** schedule administrator
- **I want** redundant shift and shift time fields removed from the form
- **So that** the interface is cleaner and less confusing

**Acceptance Criteria:**
- Separate "Shift" field is removed from form
- Separate "Shift Time" field is removed from form
- Schedule data is derived from calendar date + time picker + shift selection
- Existing schedules migrate correctly to new format
- No data loss during transition

### 2.2 Shift Legend System

**US-5: Standard Shift Legend**
- **As a** schedule viewer
- **I want to** see a consistent legend for standard shifts
- **So that** I can quickly understand schedule codes

**Acceptance Criteria:**
- Legend displays: "85 → 8:00 AM – 5:00 PM" in black color
- OFF duty shows as red outlined circle (not solid)
- Legend is visible on all schedule views
- Legend is included in printed reports

**US-6: Nursing Department Color-Coded Legend**
- **As a** nursing department staff member
- **I want to** see color-coded shift abbreviations
- **So that** I can quickly identify different shift types

**Acceptance Criteria:**
- **62** (6:00 AM – 2:00 PM): Blue
- **210** (2:00 PM – 10:00 PM): Green
- **106** (10:00 PM – 6:00 AM): Red
- **610** (6:00 AM – 10:00 PM): 6=Blue, 10=Green (multi-color)
- **26** (2:00 PM – 6:00 AM): 2=Green, 6=Red (multi-color)
- **85** (8:00 AM – 5:00 PM): Black
- **OFF**: Red outlined circle
- Colors apply in calendar, tables, and print outputs
- Legend is department-specific (nursing vs. standard)

### 2.3 Schedule Monitoring

**US-7: Department Schedule Overview**
- **As a** department head
- **I want to** view all employee schedules for my department
- **So that** I can monitor schedule completion and identify gaps

**Acceptance Criteria:**
- Monitoring section appears below schedule creation interface
- Schedules grouped by department
- Table displays: Employee Name, Department, Schedule, Shift, Status
- Visual status indicators: Green (Submitted), Yellow (Pending), Red (Missing)
- Real-time updates when schedules are added/modified

**US-8: Schedule Filtering and Search**
- **As a** schedule administrator
- **I want to** filter and search schedules
- **So that** I can quickly find specific employees or departments

**Acceptance Criteria:**
- Filter by: Department, Shift, Status
- Search by employee name (real-time)
- Filters can be combined
- Clear filters button resets all selections
- Filter state persists during session

### 2.4 Printing and Reporting

**US-9: Individual Schedule Printing**
- **As an** employee
- **I want to** print my personal schedule
- **So that** I have a physical copy for reference

**Acceptance Criteria:**
- Print button available on individual schedule view
- Print layout is clean and professional
- Includes: Employee name, department, date range, shift details
- Color-coded shifts render correctly in print
- Legend included on printed document
- Optimized for standard paper size (A4/Letter)

**US-10: Department Schedule Printing**
- **As a** department head
- **I want to** print all schedules for my department
- **So that** I can distribute or post the schedule

**Acceptance Criteria:**
- Print button available on department view
- All employees in department included
- Landscape orientation for better readability
- Proper page breaks for multiple pages
- Header includes department name and date range
- Footer includes page numbers

**US-11: Transmittal Report**
- **As a** schedule administrator
- **I want to** generate a transmittal report for schedule submissions
- **So that** I can track and document schedule completion

**Acceptance Criteria:**
- Report includes: Department Name, Page Number, Staff Count, Date Submitted, Remarks
- Table format with clear columns
- Summary statistics (total staff, submitted, pending)
- Professional formatting suitable for official documentation
- Export to PDF supported
- Print preview available

---

## 3. Functional Requirements

### 3.1 Calendar Interface

**FR-1.1: Calendar Display**
- Display monthly calendar view with all dates
- Highlight current date
- Show visual indicators for dates with existing schedules
- Support month navigation (previous/next)
- Responsive layout for different screen sizes

**FR-1.2: Date Selection**
- Single-click date selection
- Visual feedback on selected date (highlight/border)
- Deselect by clicking same date or cancel button
- Support keyboard navigation (arrow keys)

**FR-1.3: Schedule Configuration Panel**
- Panel appears on date selection (side panel or modal)
- Contains: Time picker, Shift selector, Employee selector
- Real-time preview of schedule being created
- Save and Cancel buttons
- Validation before save

### 3.2 Time Management

**FR-2.1: Time Picker Component**
- Start time selector (hours and minutes)
- End time selector (hours and minutes)
- 12-hour format with AM/PM toggle
- Dropdown and manual input support
- Common times suggested (8:00 AM, 2:00 PM, 6:00 AM, etc.)

**FR-2.2: Time Validation**
- End time must be after start time
- Validate against 24-hour boundaries
- Show error messages for invalid ranges
- Prevent save with invalid times

**FR-2.3: Shift Auto-Detection**
- Automatically suggest shift based on time range:
  - 6:00 AM – 2:00 PM → Morning (62)
  - 2:00 PM – 10:00 PM → Evening (210)
  - 10:00 PM – 6:00 AM → Night (106)
  - 8:00 AM – 5:00 PM → Standard (85)
- Allow manual override of suggested shift

### 3.3 Shift Legend System

**FR-3.1: Standard Legend**
- Display legend on all schedule views
- Format: "Code → Time Range" with color indicator
- OFF duty: Red outlined circle (⭕)
- Standard shift (85): Black text

**FR-3.2: Nursing Department Legend**
- Department-specific legend detection
- Multi-color rendering for split shifts (610, 26)
- Color codes:
  - Blue: #2196F3
  - Green: #4CAF50
  - Red: #F44336
  - Black: #000000
- Apply colors to: Calendar cells, table rows, print outputs

**FR-3.3: Legend Persistence**
- Legend visible in: Calendar view, table view, print view
- Legend included in all exported documents
- Legend updates based on department context

### 3.4 Schedule Monitoring

**FR-4.1: Department Grouping**
- Group schedules by department
- Collapsible department sections
- Department summary (total staff, submitted count)

**FR-4.2: Status Tracking**
- Track schedule status: Submitted, Pending, Missing
- Visual indicators: Color-coded badges
- Last updated timestamp
- Submission date tracking

**FR-4.3: Filtering System**
- Multi-select department filter
- Shift type filter
- Status filter
- Combine multiple filters
- Real-time filter application

**FR-4.4: Search Functionality**
- Search by employee name
- Real-time search results
- Highlight matching text
- Clear search button

### 3.5 Printing System

**FR-5.1: Print Layout Engine**
- Generate print-optimized HTML
- Support A4 and Letter paper sizes
- Landscape and portrait orientation options
- Proper margins and spacing
- Page break handling

**FR-5.2: Individual Schedule Print**
- Employee header (name, department, employee number)
- Date range
- Shift details table
- Color-coded shifts
- Legend included
- Footer with generation date

**FR-5.3: Department Schedule Print**
- Department header
- All employee schedules in table format
- Landscape orientation
- Multi-page support with page numbers
- Summary section

**FR-5.4: Transmittal Report**
- Professional document header
- Table with columns: Department, Page No., Staff Submitted, Date, Remarks
- Summary statistics
- Signature lines (Prepared By, Noted By)
- Export to PDF

---

## 4. Non-Functional Requirements

### 4.1 Performance
- **NFR-1**: Calendar should load within 1 second
- **NFR-2**: Schedule save operation completes within 2 seconds
- **NFR-3**: Print preview generates within 3 seconds
- **NFR-4**: Search results appear within 500ms of typing
- **NFR-5**: Support up to 500 employees per department without performance degradation

### 4.2 Usability
- **NFR-6**: Interface should be intuitive for users with basic computer skills
- **NFR-7**: Maximum 3 clicks to create a schedule
- **NFR-8**: Color contrast meets WCAG AA standards
- **NFR-9**: All interactive elements have clear hover states
- **NFR-10**: Error messages are clear and actionable

### 4.3 Compatibility
- **NFR-11**: Support modern browsers (Chrome, Firefox, Edge, Safari - last 2 versions)
- **NFR-12**: Responsive design for screens 1024px and above
- **NFR-13**: Print output compatible with standard printers
- **NFR-14**: PDF export compatible with Adobe Reader and browser PDF viewers

### 4.4 Reliability
- **NFR-15**: System should handle concurrent schedule creation by multiple users
- **NFR-16**: Data validation prevents invalid schedules from being saved
- **NFR-17**: Graceful error handling with user-friendly messages
- **NFR-18**: Auto-save draft schedules to prevent data loss

### 4.5 Maintainability
- **NFR-19**: Code should follow existing project conventions
- **NFR-20**: Components should be reusable across different views
- **NFR-21**: Color codes should be configurable via constants/config file
- **NFR-22**: Print templates should be easily modifiable

---

## 5. Data Requirements

### 5.1 Schedule Data Model
```
Schedule {
  id: integer (primary key)
  employee_id: integer (foreign key)
  employee_no: string
  employee_name: string
  department: string
  date: date
  start_time: time
  end_time: time
  shift_code: string (62, 210, 106, 610, 26, 85, OFF)
  shift_name: string (Morning, Evening, Night, Custom, OFF)
  status: enum (Submitted, Pending, Missing)
  submitted_date: datetime
  last_updated: datetime
  created_by: integer (user_id)
  remarks: text
}
```

### 5.2 Shift Legend Configuration
```
ShiftLegend {
  code: string (primary key)
  department: string
  time_range: string
  color_primary: string (hex)
  color_secondary: string (hex, optional for split shifts)
  display_order: integer
}
```

### 5.3 Transmittal Data
```
Transmittal {
  id: integer (primary key)
  department: string
  page_number: integer
  staff_count: integer
  submitted_count: integer
  date_submitted: date
  remarks: text
  generated_by: integer (user_id)
  generated_at: datetime
}
```

---

## 6. UI/UX Requirements

### 6.1 Calendar Interface
- **Layout**: Full-width calendar with side panel for configuration
- **Colors**: 
  - Selected date: Light blue background (#E3F2FD)
  - Current date: Bold border
  - Dates with schedules: Small colored dot indicator
- **Interactions**:
  - Hover: Light gray background
  - Click: Open configuration panel with smooth animation
  - Double-click: Quick edit existing schedule

### 6.2 Time Picker
- **Style**: Dropdown with clock icon
- **Format**: HH:MM AM/PM
- **Behavior**: 
  - Click to open dropdown
  - Scroll through hours/minutes
  - Type to jump to time
  - Tab navigation between fields

### 6.3 Shift Selector
- **Style**: Dropdown or button group
- **Options**: Visual buttons with color indicators
- **Behavior**: Single selection, immediate visual feedback

### 6.4 Monitoring Dashboard
- **Layout**: Table with filters above
- **Grouping**: Collapsible department sections
- **Status Badges**:
  - Submitted: Green badge with checkmark
  - Pending: Yellow badge with clock icon
  - Missing: Red badge with warning icon

### 6.5 Print Layouts
- **Individual Schedule**:
  - Portrait orientation
  - Header: Employee info
  - Body: Schedule table
  - Footer: Legend and generation date
- **Department Schedule**:
  - Landscape orientation
  - Header: Department name and date range
  - Body: Multi-column table
  - Footer: Page numbers
- **Transmittal**:
  - Portrait orientation
  - Professional header with logo
  - Table with clear columns
  - Signature section at bottom

---

## 7. Business Rules

### 7.1 Schedule Assignment Rules
- **BR-1**: An employee can have only one schedule per date
- **BR-2**: Schedule start time must be before end time
- **BR-3**: OFF duty schedules do not require time configuration
- **BR-4**: Schedules can be created up to 6 months in advance
- **BR-5**: Past schedules can be viewed but not edited (read-only)

### 7.2 Department Rules
- **BR-6**: Nursing department uses color-coded shift system
- **BR-7**: Other departments use standard shift system
- **BR-8**: Department-specific legends are automatically applied
- **BR-9**: Department heads can only view their own department schedules

### 7.3 Status Rules
- **BR-10**: New schedules default to "Pending" status
- **BR-11**: Status changes to "Submitted" when employee confirms
- **BR-12**: Status is "Missing" if no schedule exists for current period
- **BR-13**: Status changes are logged with timestamp and user

### 7.4 Printing Rules
- **BR-14**: Only submitted schedules appear in official prints
- **BR-15**: Transmittal reports include only completed departments
- **BR-16**: Print permissions follow existing role-based access control
- **BR-17**: Printed documents include generation timestamp and user

---

## 8. Constraints

### 8.1 Technical Constraints
- **C-1**: Must integrate with existing Vue.js frontend
- **C-2**: Must use existing PHP backend API structure
- **C-3**: Must work with existing MySQL database
- **C-4**: Must maintain existing authentication/authorization system
- **C-5**: Must support existing browser print functionality

### 8.2 Business Constraints
- **C-6**: Implementation must not disrupt current schedule operations
- **C-7**: Existing schedule data must be preserved and migrated
- **C-8**: Training materials must be provided for new interface
- **C-9**: Rollback plan required in case of issues

### 8.3 Design Constraints
- **C-10**: Must follow existing GEAMH HRIS design system
- **C-11**: Must use existing color palette where applicable
- **C-12**: Must maintain consistent header/footer across all pages
- **C-13**: Must support existing print infrastructure

---

## 9. Assumptions

- **A-1**: Users have basic computer literacy and can use calendar interfaces
- **A-2**: Printers support color printing for shift legends
- **A-3**: Network connectivity is stable for real-time updates
- **A-4**: Existing employee and department data is accurate
- **A-5**: Users have appropriate permissions configured in the system
- **A-6**: Browser print settings allow landscape orientation
- **A-7**: PDF export functionality is available in target browsers

---

## 10. Dependencies

### 10.1 Internal Dependencies
- **D-1**: Employee Masterlist module (for employee data)
- **D-2**: Department Management module (for department data)
- **D-3**: Authentication system (for user permissions)
- **D-4**: Notification system (for schedule updates)
- **D-5**: Audit logging system (for change tracking)

### 10.2 External Dependencies
- **D-6**: Vue.js framework (existing version)
- **D-7**: Calendar component library (to be selected)
- **D-8**: Time picker component library (to be selected)
- **D-9**: Print CSS library or custom print styles
- **D-10**: PDF generation library (optional, for enhanced export)

---

## 11. Success Criteria

### 11.1 Functional Success
- ✅ Users can create schedules in 3 clicks or less
- ✅ All shift codes display with correct colors
- ✅ Department monitoring shows real-time status
- ✅ Print outputs are professional and accurate
- ✅ No data loss during migration from old system

### 11.2 Performance Success
- ✅ 95% of operations complete within specified time limits
- ✅ System handles 50 concurrent users without degradation
- ✅ Print generation completes within 3 seconds for 100 employees

### 11.3 User Satisfaction
- ✅ 80% of users find new interface easier than old system
- ✅ Training time reduced by 50% compared to old system
- ✅ User error rate reduced by 30%
- ✅ Positive feedback from department heads on monitoring features

### 11.4 Business Success
- ✅ Schedule creation time reduced by 40%
- ✅ Schedule completion rate increases by 20%
- ✅ Printing time reduced by 50%
- ✅ Zero critical bugs in production after 1 month

---

## 12. Risks and Mitigation

### 12.1 Technical Risks
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Calendar library compatibility issues | High | Medium | Evaluate multiple libraries, have fallback plan |
| Print rendering inconsistencies across browsers | Medium | High | Extensive cross-browser testing, use print CSS best practices |
| Performance issues with large datasets | High | Low | Implement pagination, lazy loading, and data caching |
| Color-coding not visible in print | Medium | Low | Test with multiple printers, provide grayscale fallback |

### 12.2 User Adoption Risks
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Resistance to new interface | Medium | Medium | Provide training, keep old system available temporarily |
| Confusion with color codes | Low | Medium | Clear legend always visible, tooltips on hover |
| Difficulty using time picker | Low | Low | Intuitive design, allow manual input as alternative |

### 12.3 Business Risks
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Data migration errors | High | Low | Thorough testing, backup before migration, rollback plan |
| Disruption to schedule operations | High | Low | Phased rollout, parallel running period |
| Increased support requests | Medium | Medium | Comprehensive documentation, training sessions |

---

## 13. Future Enhancements (Out of Current Scope)

- **FE-1**: Mobile app for schedule viewing and submission
- **FE-2**: Automated schedule generation based on rules
- **FE-3**: Shift swap/trade functionality between employees
- **FE-4**: Integration with external calendar systems (Google, Outlook)
- **FE-5**: SMS/email notifications for schedule changes
- **FE-6**: Advanced analytics and reporting dashboard
- **FE-7**: Shift pattern templates for recurring schedules
- **FE-8**: Overtime tracking and alerts
- **FE-9**: Leave integration (auto-mark OFF for approved leaves)
- **FE-10**: Multi-language support for shift names

---

## 14. Acceptance Testing Scenarios

### 14.1 Schedule Creation
1. **Scenario**: Create a morning shift schedule
   - Click on calendar date
   - Set time: 6:00 AM - 2:00 PM
   - Select shift: Morning (62)
   - Verify color: Blue
   - Save and confirm schedule appears in calendar

2. **Scenario**: Create OFF duty schedule
   - Click on calendar date
   - Select shift: OFF
   - Verify red outlined circle appears
   - Save and confirm

3. **Scenario**: Attempt invalid time range
   - Click on calendar date
   - Set time: 5:00 PM - 8:00 AM (end before start)
   - Verify error message appears
   - Verify save button is disabled

### 14.2 Shift Legend Display
1. **Scenario**: View nursing department schedule
   - Navigate to nursing department
   - Verify color-coded shifts: 62 (Blue), 210 (Green), 106 (Red)
   - Verify split shift colors: 610 (Blue+Green), 26 (Green+Red)
   - Verify legend is visible

2. **Scenario**: View standard department schedule
   - Navigate to non-nursing department
   - Verify 85 shift displays in black
   - Verify OFF displays as red outlined circle
   - Verify standard legend is visible

### 14.3 Monitoring Dashboard
1. **Scenario**: Filter by department
   - Select department from filter
   - Verify only selected department schedules appear
   - Verify count updates correctly

2. **Scenario**: Search for employee
   - Type employee name in search
   - Verify matching results appear in real-time
   - Verify highlighting of matched text

3. **Scenario**: View status indicators
   - Verify submitted schedules show green badge
   - Verify pending schedules show yellow badge
   - Verify missing schedules show red badge

### 14.4 Printing
1. **Scenario**: Print individual schedule
   - Click print button on employee schedule
   - Verify print preview opens
   - Verify colors render correctly
   - Verify legend is included
   - Print and verify output quality

2. **Scenario**: Print department schedule
   - Click print button on department view
   - Verify landscape orientation
   - Verify all employees included
   - Verify page breaks are appropriate
   - Print and verify output quality

3. **Scenario**: Generate transmittal report
   - Click generate transmittal button
   - Verify all departments listed
   - Verify staff counts are accurate
   - Verify date and remarks fields populated
   - Export to PDF and verify

---

## 15. Glossary

- **Schedule**: A work shift assignment for an employee on a specific date
- **Shift Code**: Abbreviated code representing shift time (e.g., 62, 85, OFF)
- **Shift Legend**: Visual guide showing shift codes, times, and colors
- **OFF Duty**: Non-working day for an employee
- **Transmittal**: Official document tracking schedule submission status
- **Department Head**: Manager responsible for a department's schedules
- **Split Shift**: Shift code with multiple time segments (e.g., 610, 26)
- **Status**: Current state of schedule (Submitted, Pending, Missing)
- **Calendar View**: Visual representation of schedules in monthly calendar format
- **Monitoring Dashboard**: Overview panel showing schedule status across departments

---

## Correctness Properties

### Property 1: Schedule Uniqueness
**Property**: For any given employee and date, there exists at most one schedule.
```
∀ employee_id, date: COUNT(schedules WHERE employee_id = e AND date = d) ≤ 1
```

### Property 2: Time Validity
**Property**: For all non-OFF schedules, end time must be after start time.
```
∀ schedule WHERE shift_code ≠ 'OFF': end_time > start_time
```

### Property 3: Color Consistency
**Property**: All schedules with the same shift code in the same department must display the same color.
```
∀ s1, s2 WHERE s1.shift_code = s2.shift_code AND s1.department = s2.department:
  s1.display_color = s2.display_color
```

### Property 4: Status Integrity
**Property**: A schedule's status must be one of the defined valid states.
```
∀ schedule: schedule.status ∈ {Submitted, Pending, Missing}
```

### Property 5: Print Completeness
**Property**: All submitted schedules for a department must appear in the department print output.
```
∀ schedule WHERE status = 'Submitted' AND department = d:
  schedule ∈ print_output(d)
```

---

**Document Version**: 1.0  
**Created**: May 18, 2026  
**Status**: Draft - Pending Review
