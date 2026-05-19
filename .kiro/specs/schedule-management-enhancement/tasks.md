# Implementation Plan: Schedule Management System Enhancement

## Overview

This implementation plan breaks down the Schedule Management System Enhancement into actionable development tasks. The feature adds calendar-based scheduling, color-coded shift legends, department monitoring, and professional printing capabilities to the existing GEAMH HRIS system.

**Technology Stack:**
- Frontend: Vue.js 3 (Composition API), Pinia state management
- Backend: PHP 8.x with MySQL 8.x
- Testing: Vitest for unit tests, fast-check for property-based tests

**Implementation Approach:**
- Incremental development with validation at each phase
- Property-based testing for universal correctness properties
- Unit tests for specific examples and edge cases
- Each task builds on previous work with no orphaned code

---

## Tasks

- [ ] 1. Database Schema and Backend API Setup
  - [ ] 1.1 Create database schema updates
    - Create `shift_legends` table with columns: id, code, department, time_range, color_primary, color_secondary, display_order, active
    - Add new columns to `schedules` table: schedule_date (DATE), start_time (TIME), end_time (TIME), shift_code (VARCHAR), shift_name (VARCHAR), status (ENUM)
    - Create `schedule_transmittals` table with columns: id, department, period_start, period_end, page_number, staff_count, submitted_count, date_submitted, remarks, generated_by, generated_at
    - Add indexes: idx_schedule_date, idx_employee_dept, idx_shift_code, idx_department, idx_status
    - _Requirements: 5.1, 5.2, 5.3_

  - [ ] 1.2 Write database migration script
    - Create migration script to add new tables and columns
    - Preserve existing schedule data in legacy fields (days, effective_date, end_date, rest_day)
    - Populate `shift_legends` table with standard legends (85 → 8:00 AM - 5:00 PM, OFF)
    - Populate `shift_legends` table with nursing-specific legends (62, 210, 106, 610, 26)
    - Verify data integrity after migration
    - _Requirements: 4.4, 4.5, BR-1_

  - [ ] 1.3 Create shift legends API endpoint (server/api/shift_legends.php)
    - Implement GET /shift_legends.php with optional department filter
    - Implement POST /shift_legends.php for creating legends (admin only)
    - Implement PUT /shift_legends.php?id=<id> for updating legends (admin only)
    - Add permission checks using existing checkPermission function
    - Add input validation for code, timeRange, colorPrimary, colorSecondary
    - _Requirements: 3.3.1, 3.3.2, FR-3.1, FR-3.2_

  - [ ] 1.4 Enhance schedule API endpoint (server/api/schedule.php)
    - Add validation for schedule_date, start_time, end_time, shift_code fields
    - Implement duplicate schedule check (same employee_id + schedule_date)
    - Add time range validation (end_time must be after start_time)
    - Update POST endpoint to accept new schedule format with specific dates
    - Update PUT endpoint to support new fields
    - Add query parameter support: ?date=<YYYY-MM-DD>, ?dept=<department>
    - _Requirements: 2.5, 3.2.1, 3.2.2, FR-2.2, BR-1, BR-2_

  - [ ]* 1.5 Write property test for time range validation
    - **Property 1: Time Range Validity**
    - **Validates: Requirements 2.5, FR-2.2, BR-2**
    - Test that for all non-OFF schedules, end_time > start_time
    - Use fast-check to generate random time ranges
    - Tag: `// Feature: schedule-management-enhancement, Property 1: Time Range Validity`

  - [ ]* 1.6 Write property test for schedule uniqueness
    - **Property 4: Schedule Uniqueness Per Date**
    - **Validates: Requirements BR-1**
    - Test that for any employee and date, at most one schedule exists
    - Use fast-check to generate random employee IDs and dates
    - Tag: `// Feature: schedule-management-enhancement, Property 4: Schedule Uniqueness Per Date`

- [ ] 2. Checkpoint - Verify database and API functionality
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 3. Frontend State Management and Stores
  - [ ] 3.1 Create legend store (client/src/stores/legend.js)
    - Create Pinia store with state: legends (array)
    - Implement fetchLegends() method to call GET /shift_legends.php
    - Implement getLegendForShift(shiftCode, department) method
    - Implement getColorForShift(shiftCode, department) method
    - Add computed property: departmentLegends (grouped by department)
    - _Requirements: 3.3.3, FR-3.2, FR-3.3_

  - [ ] 3.2 Enhance schedule store (client/src/stores/schedule.js)
    - Add legends state and integrate with legend store
    - Implement getSchedulesByDateRange(startDate, endDate) method
    - Implement getSchedulesForDate(date, employeeId) method
    - Implement addScheduleWithDates(schedule, specificDates) method for bulk assignment
    - Update addSchedule() to use new API format (schedule_date, start_time, end_time, shift_code)
    - Update updateSchedule() to support new fields
    - Add getShiftColor(shiftCode, department) method using legend store
    - _Requirements: 3.1.3, 3.2.1, FR-1.3_

  - [ ]* 3.3 Write unit tests for legend store
    - Test fetchLegends() returns correct legends
    - Test getLegendForShift() returns department-specific legend when available
    - Test getLegendForShift() falls back to standard legend
    - Test getColorForShift() returns correct color
    - _Requirements: 3.3.2, FR-3.2_

  - [ ]* 3.4 Write unit tests for enhanced schedule store
    - Test getSchedulesForDate() filters by date correctly
    - Test addScheduleWithDates() creates multiple schedules
    - Test getShiftColor() returns consistent colors
    - _Requirements: 3.2.1, FR-1.3_

- [ ] 4. Core Calendar Components
  - [ ] 4.1 Create TimePicker component (client/src/components/schedule/TimePicker.vue)
    - Create component with props: modelValue ({ start, end }), format ('12h' | '24h'), suggestions (array)
    - Implement dual time selectors for start and end times
    - Add 12-hour format display with AM/PM indicators
    - Implement time validation (end must be after start)
    - Emit 'update:modelValue' when times change
    - Implement shift auto-detection based on time ranges (6-2 → 62, 2-10 → 210, 10-6 → 106, 8-5 → 85)
    - Emit 'shift-detected' event with suggested shift code
    - Display validation error messages inline
    - _Requirements: 2.2, 2.3, 3.2.2, 3.2.3, FR-2.1, FR-2.2, FR-2.3_

  - [ ]* 4.2 Write unit tests for TimePicker component
    - Test validation error when end time is before start time
    - Test 12-hour format display with AM/PM
    - Test shift auto-detection for common time ranges
    - Test manual input and dropdown selection
    - _Requirements: 2.2, 2.3, FR-2.1, FR-2.2_

  - [ ]* 4.3 Write property test for time format consistency
    - **Property 8: Time Format Consistency**
    - **Validates: Requirements 2.3**
    - Test that all displayed times use 12-hour format with AM/PM
    - Use fast-check to generate random times
    - Tag: `// Feature: schedule-management-enhancement, Property 8: Time Format Consistency`

  - [ ] 4.4 Create ShiftLegend component (client/src/components/schedule/ShiftLegend.vue)
    - Create component with props: department (string), legends (array), compact (boolean)
    - Implement computed property: filteredLegends (filter by department)
    - Display legend items with color indicators (colored dot or box)
    - Support multi-color display for split shifts (610, 26)
    - Display OFF duty as red outlined circle (⭕)
    - Support compact and full display modes
    - _Requirements: 2.6, 3.3.1, 3.3.2, FR-3.1, FR-3.2_

  - [ ]* 4.5 Write unit tests for ShiftLegend component
    - Test nursing-specific legends display for nursing department
    - Test standard legends display for non-nursing departments
    - Test OFF duty displays as red outlined circle
    - Test multi-color rendering for split shifts
    - _Requirements: 2.6, 3.3.2, FR-3.2_

  - [ ]* 4.6 Write property test for legend visibility
    - **Property 3: Legend Visibility**
    - **Validates: Requirements 5.3, 5.4, 9.5**
    - Test that legend contains all shift codes present in displayed schedules
    - Use fast-check to generate random schedule sets
    - Tag: `// Feature: schedule-management-enhancement, Property 3: Legend Visibility`

  - [ ] 4.7 Enhance CalendarView component (client/src/views/schedule/ScheduleDatabase.vue)
    - Add schedule indicators (colored dots) on dates with existing schedules
    - Implement visual feedback for selected date (light blue background #E3F2FD)
    - Add bold border for current date
    - Implement hover state (light gray background)
    - Update schedule block rendering to use shift colors from legend store
    - Integrate ShiftLegend component into calendar layout
    - _Requirements: 1.5, 6.1, FR-1.1, FR-1.2, FR-3.3_

  - [ ]* 4.8 Write unit tests for CalendarView enhancements
    - Test schedule indicators appear on dates with schedules
    - Test selected date has correct visual feedback
    - Test current date has bold border
    - Test hover state applies correctly
    - _Requirements: 1.5, 6.1, FR-1.1_

  - [ ]* 4.9 Write property test for calendar month completeness
    - **Property 5: Calendar Month Completeness**
    - **Validates: Requirements 1.1**
    - Test that calendar displays correct number of days for any month (including leap years)
    - Use fast-check to generate random year/month combinations
    - Tag: `// Feature: schedule-management-enhancement, Property 5: Calendar Month Completeness`

  - [ ]* 4.10 Write property test for date navigation correctness
    - **Property 6: Date Navigation Correctness**
    - **Validates: Requirements 1.4**
    - Test that next/previous month navigation is correct
    - Use fast-check to generate random dates
    - Tag: `// Feature: schedule-management-enhancement, Property 6: Date Navigation Correctness`

- [ ] 5. Checkpoint - Verify calendar components render correctly
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Enhanced Schedule Form
  - [ ] 6.1 Enhance ScheduleForm modal in ScheduleDatabase.vue
    - Integrate TimePicker component into form
    - Add shift selector dropdown with color indicators
    - Update form to use new schedule format (schedule_date, start_time, end_time, shift_code)
    - Remove redundant "Shift" and "Shift Time" fields from form
    - Add specific date selection calendar (form-cal-grid)
    - Implement "Select All Month" and "Clear" buttons for date selection
    - Update save() method to call addScheduleWithDates() for bulk assignment
    - _Requirements: 2.1, 2.2, 2.3, 2.4, FR-1.3, FR-2.1_

  - [ ] 6.2 Implement form validation
    - Validate employee selection (required)
    - Validate time range using TimePicker validation
    - Validate shift code selection (required)
    - Validate at least one date selected (schedule_date or specificDates)
    - Display validation errors inline with clear messages
    - Disable save button when form is invalid
    - _Requirements: 2.5, 3.2.2, FR-2.2_

  - [ ]* 6.3 Write unit tests for enhanced ScheduleForm
    - Test form opens with pre-filled date when calendar date clicked
    - Test validation prevents save with invalid data
    - Test bulk date selection creates multiple schedules
    - Test "Select All Month" selects all days in current month
    - _Requirements: 2.1, 2.2, FR-1.3_

  - [ ]* 6.4 Write property test for shift auto-detection
    - **Property 9: Shift Auto-Detection**
    - **Validates: Requirements 3.3**
    - Test that time ranges matching predefined patterns suggest correct shift codes
    - Use fast-check to generate time ranges
    - Tag: `// Feature: schedule-management-enhancement, Property 9: Shift Auto-Detection`

- [ ] 7. Department Monitoring Dashboard
  - [ ] 7.1 Create MonitoringDashboard component (client/src/components/schedule/MonitoringDashboard.vue)
    - Create component with props: schedules (array), departments (array), filters (object)
    - Implement department grouping with collapsible sections
    - Display schedule table with columns: Employee Name, Department, Schedule, Shift, Status
    - Add status badges: Submitted (green), Pending (yellow), Missing (red)
    - Implement computed property: groupedByDepartment
    - Implement computed property: statusCounts (summary per department)
    - Emit 'filter-changed' when filters are updated
    - Emit 'schedule-selected' when schedule row is clicked
    - _Requirements: 2.7, 7.1, 7.2, FR-4.1, FR-4.2_

  - [ ] 7.2 Create FilterBar component (client/src/components/schedule/FilterBar.vue)
    - Create component with props: departments (array), shifts (array), statuses (array)
    - Implement multi-select department filter
    - Implement shift type filter dropdown
    - Implement status filter dropdown
    - Add search input for employee name (real-time)
    - Add "Clear Filters" button
    - Emit 'filter-changed' with filter object
    - _Requirements: 2.8, FR-4.3, FR-4.4_

  - [ ] 7.3 Integrate MonitoringDashboard into ScheduleDatabase.vue
    - Add MonitoringDashboard component below calendar view
    - Pass filtered schedules to dashboard
    - Implement filter state management
    - Connect FilterBar to schedule filtering logic
    - Update filtered computed property to apply all filters
    - _Requirements: 2.7, 2.8, FR-4.1, FR-4.3_

  - [ ]* 7.4 Write unit tests for MonitoringDashboard
    - Test department grouping is correct
    - Test status badges display correct colors
    - Test collapsible sections work
    - Test statusCounts computed property is accurate
    - _Requirements: 7.1, 7.2, FR-4.1, FR-4.2_

  - [ ]* 7.5 Write unit tests for FilterBar
    - Test filter changes emit correct events
    - Test search input filters in real-time
    - Test "Clear Filters" resets all selections
    - _Requirements: 2.8, FR-4.3, FR-4.4_

  - [ ]* 7.6 Write property test for department grouping correctness
    - **Property 11: Department Grouping Correctness**
    - **Validates: Requirements 7.2**
    - Test that each schedule appears in exactly one department group
    - Use fast-check to generate random schedule sets
    - Tag: `// Feature: schedule-management-enhancement, Property 11: Department Grouping Correctness`

  - [ ]* 7.7 Write property test for filter combination correctness
    - **Property 12: Filter Combination Correctness**
    - **Validates: Requirements 8.1, 8.3**
    - Test that filtered results match all active filter criteria
    - Use fast-check to generate random filter combinations
    - Tag: `// Feature: schedule-management-enhancement, Property 12: Filter Combination Correctness`

  - [ ]* 7.8 Write property test for search result accuracy
    - **Property 13: Search Result Accuracy**
    - **Validates: Requirements 8.2**
    - Test that search results include only matching employee names
    - Use fast-check to generate random search queries
    - Tag: `// Feature: schedule-management-enhancement, Property 13: Search Result Accuracy`

- [ ] 8. Checkpoint - Verify monitoring dashboard functionality
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Printing System
  - [ ] 9.1 Create individual schedule print template
    - Create printIndividualSchedule() function in client/src/utils/print.js
    - Generate HTML with employee header (name, department, employee number)
    - Create schedule table with columns: Date, Shift Code, Time Range, Status
    - Apply shift colors from legend store to table rows
    - Include ShiftLegend in print output
    - Add footer with generation date and timestamp
    - Use portrait orientation
    - Apply print-specific CSS (@media print)
    - _Requirements: 2.9, 9.3, FR-5.2_

  - [ ] 9.2 Create department schedule print template
    - Create printDepartmentSchedule() function in client/src/utils/print.js
    - Generate HTML with department header and date range
    - Create multi-column table with all employees in department
    - Apply shift colors to schedule cells
    - Include ShiftLegend in print output
    - Use landscape orientation
    - Implement page break logic for multiple pages
    - Add page numbers in footer
    - Apply print-specific CSS
    - _Requirements: 2.10, 10.2, FR-5.3_

  - [ ] 9.3 Create transmittal report template
    - Create printTransmittalReport() function in client/src/utils/print.js
    - Generate HTML with professional header (GEAMH logo, title)
    - Create table with columns: Department, Page No., Staff Submitted, Date, Remarks
    - Add summary statistics (total staff, submitted count, pending count)
    - Add signature lines (Prepared By, Noted By)
    - Use portrait orientation
    - Apply print-specific CSS
    - _Requirements: 2.11, 11.3, FR-5.4_

  - [ ] 9.4 Enhance print utility (client/src/utils/print.js)
    - Update printSchedules() function to support new print templates
    - Add print preview functionality (open in new window)
    - Implement color-coded shift rendering in print
    - Add support for landscape/portrait orientation
    - Ensure colors render correctly in print (@media print CSS)
    - Add browser print dialog integration
    - _Requirements: 2.9, 2.10, 9.4, FR-5.1_

  - [ ] 9.5 Add print buttons to UI
    - Add "Print" button to individual schedule view
    - Add "Print" button to department view in MonitoringDashboard
    - Add "Generate Transmittal" button to toolbar
    - Connect buttons to respective print functions
    - Show loading indicator during print generation
    - _Requirements: 2.9, 2.10, 2.11_

  - [ ]* 9.6 Write unit tests for print functions
    - Test printIndividualSchedule() generates correct HTML structure
    - Test printDepartmentSchedule() includes all employees
    - Test printTransmittalReport() calculates statistics correctly
    - Test print preview opens in new window
    - _Requirements: 9.3, 10.2, 11.3_

  - [ ]* 9.7 Write property test for print output completeness
    - **Property 14: Print Output Completeness**
    - **Validates: Requirements 9.3, 10.2**
    - Test that all required fields appear in print output
    - Use fast-check to generate random schedules
    - Tag: `// Feature: schedule-management-enhancement, Property 14: Print Output Completeness`

  - [ ]* 9.8 Write property test for shift color consistency
    - **Property 2: Shift Color Consistency**
    - **Validates: Requirements 3.4, 6.8, 9.4, FR-3.3**
    - Test that shift colors are identical across calendar, table, and print views
    - Use fast-check to generate random shift codes and departments
    - Tag: `// Feature: schedule-management-enhancement, Property 2: Shift Color Consistency`

  - [ ]* 9.9 Write property test for department legend specificity
    - **Property 15: Department Legend Specificity**
    - **Validates: Requirements 6.9**
    - Test that correct legend (department-specific or standard) is displayed
    - Use fast-check to generate random departments
    - Tag: `// Feature: schedule-management-enhancement, Property 15: Department Legend Specificity`

- [ ] 10. Data Migration and Legacy Support
  - [ ] 10.1 Create data migration utility
    - Create migration script to convert legacy schedules to new format
    - For each legacy schedule with days array, create individual schedule records for each day
    - Calculate schedule_date from effective_date + day of week
    - Map legacy shift to shift_code (Morning → 62, Afternoon → 210, Night → 106, etc.)
    - Parse shift_time to extract start_time and end_time
    - Set status to 'Submitted' for existing schedules
    - Preserve legacy fields for backward compatibility
    - _Requirements: 4.4, 4.5, BR-1_

  - [ ] 10.2 Test data migration
    - Run migration on test database
    - Verify all legacy schedules converted correctly
    - Verify no data loss occurred
    - Verify schedule counts match before and after
    - Test rollback procedure
    - _Requirements: 4.4, 4.5_

  - [ ]* 10.3 Write property test for data migration preservation
    - **Property 10: Data Migration Preservation**
    - **Validates: Requirements 4.4, 4.5**
    - Test that migrating legacy format preserves all information
    - Use fast-check to generate random legacy schedules
    - Tag: `// Feature: schedule-management-enhancement, Property 10: Data Migration Preservation`

- [ ] 11. Additional Property-Based Tests
  - [ ]* 11.1 Write property test for schedule indicator accuracy
    - **Property 7: Schedule Indicator Accuracy**
    - **Validates: Requirements 1.5**
    - Test that visual indicators appear only on dates with schedules
    - Use fast-check to generate random dates and schedules
    - Tag: `// Feature: schedule-management-enhancement, Property 7: Schedule Indicator Accuracy`

  - [ ]* 11.2 Write property test for transmittal statistics accuracy
    - **Property 16: Transmittal Statistics Accuracy**
    - **Validates: Requirements 11.3**
    - Test that transmittal report statistics match actual schedule data
    - Use fast-check to generate random schedule sets
    - Tag: `// Feature: schedule-management-enhancement, Property 16: Transmittal Statistics Accuracy`

  - [ ]* 11.3 Write property test for past schedule immutability
    - **Property 17: Past Schedule Immutability**
    - **Validates: Requirements BR-5**
    - Test that edit/delete operations are blocked for past schedules
    - Use fast-check to generate random dates (past and future)
    - Tag: `// Feature: schedule-management-enhancement, Property 17: Past Schedule Immutability`

  - [ ]* 11.4 Write property test for real-time update propagation
    - **Property 18: Real-Time Update Propagation**
    - **Validates: Requirements 7.5**
    - Test that schedule modifications reflect in all active views
    - Use fast-check to generate random schedule modifications
    - Tag: `// Feature: schedule-management-enhancement, Property 18: Real-Time Update Propagation`

- [ ] 12. Integration Testing and End-to-End Flows
  - [ ]* 12.1 Write integration test for schedule creation flow
    - Test complete flow: calendar click → form open → fill data → save → verify in calendar
    - Test schedule appears in database
    - Test notification sent to employee
    - _Requirements: 2.1, 2.2, 2.3, FR-1.3_

  - [ ]* 12.2 Write integration test for bulk schedule assignment
    - Test selecting multiple dates in form calendar
    - Test "Select All Month" creates schedules for all days
    - Test all schedules saved to database
    - _Requirements: 2.1, FR-1.3_

  - [ ]* 12.3 Write integration test for filtering and search
    - Test applying multiple filters simultaneously
    - Test search filters results in real-time
    - Test "Clear Filters" resets view
    - _Requirements: 2.8, FR-4.3, FR-4.4_

  - [ ]* 12.4 Write integration test for print workflow
    - Test print button opens print preview
    - Test print preview contains all required data
    - Test colors render correctly in print
    - _Requirements: 2.9, 2.10, 2.11_

- [ ] 13. Performance Optimization and Testing
  - [ ] 13.1 Implement performance optimizations
    - Add database indexes (idx_schedule_date, idx_employee_dept, idx_shift_code)
    - Implement virtual scrolling for large schedule lists (500+ schedules)
    - Add memoization for expensive computed properties (filteredSchedules)
    - Implement debounced search (300ms delay)
    - Add caching for shift legends (1 hour TTL)
    - _Requirements: NFR-1, NFR-2, NFR-3, NFR-4, NFR-5_

  - [ ]* 13.2 Write performance tests
    - Test calendar renders with 500 schedules in under 1 second
    - Test filtering 1000 schedules completes in under 500ms
    - Test print preview generates in under 3 seconds
    - Test search results appear within 500ms
    - _Requirements: NFR-1, NFR-2, NFR-3, NFR-4_

- [ ] 14. Accessibility and Cross-Browser Testing
  - [ ] 14.1 Implement accessibility features
    - Add ARIA labels to calendar navigation buttons
    - Add role="grid" and role="gridcell" to calendar
    - Implement keyboard navigation (arrow keys, Enter, Tab, Escape)
    - Add focus trap for modals
    - Ensure all interactive elements are keyboard accessible
    - Verify color contrast meets WCAG AA standards (4.5:1 ratio)
    - Add focus indicators to all interactive elements
    - _Requirements: NFR-8, NFR-9, 6.1, 6.2_

  - [ ]* 14.2 Test cross-browser compatibility
    - Test in Chrome (last 2 versions)
    - Test in Firefox (last 2 versions)
    - Test in Edge (last 2 versions)
    - Test in Safari (last 2 versions)
    - Test print functionality in all browsers
    - _Requirements: NFR-11, NFR-13_

  - [ ]* 14.3 Test responsive design
    - Test on screens 1024px and above
    - Test calendar layout on different screen sizes
    - Test print layouts on A4 and Letter paper sizes
    - _Requirements: NFR-12, NFR-13_

- [ ] 15. Final Integration and Deployment Preparation
  - [ ] 15.1 Run complete test suite
    - Run all unit tests and verify 80% code coverage
    - Run all 18 property-based tests with 100 iterations each
    - Run all integration tests
    - Fix any failing tests
    - _Requirements: All_

  - [ ] 15.2 Create deployment checklist
    - Document database migration steps
    - Document API endpoint changes
    - Document frontend deployment steps
    - Create rollback plan
    - Document monitoring and logging setup
    - _Requirements: C-6, C-7, C-9_

  - [ ] 15.3 Prepare user documentation
    - Create user manual for new calendar interface
    - Document shift legend system
    - Document monitoring dashboard usage
    - Document print functionality
    - Create quick reference guide
    - _Requirements: C-8_

- [ ] 16. Final Checkpoint - Complete system verification
  - Ensure all tests pass, ask the user if questions arise.

---

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Property-based tests use fast-check library with minimum 100 iterations
- Unit tests focus on specific examples and edge cases
- All code should follow existing GEAMH HRIS conventions and patterns
- Checkpoints ensure incremental validation throughout implementation
- Database migration preserves all existing data in legacy fields for backward compatibility
- Print functionality uses browser-native print APIs with CSS @media print rules
- Color-coded shifts use hex colors: Blue (#2196F3), Green (#4CAF50), Red (#F44336), Black (#000000)

## Testing Summary

- **18 Property-Based Tests**: Cover universal correctness properties
- **Unit Tests**: Cover specific examples, edge cases, and component behavior
- **Integration Tests**: Cover end-to-end user flows
- **Performance Tests**: Verify NFR compliance
- **Accessibility Tests**: Verify WCAG AA compliance
- **Cross-Browser Tests**: Verify compatibility across modern browsers

## Implementation Timeline

- **Phase 1**: Database and Backend (Tasks 1-2) - Week 1
- **Phase 2**: State Management (Task 3) - Week 2
- **Phase 3**: Core Components (Tasks 4-6) - Week 2
- **Phase 4**: Monitoring Dashboard (Tasks 7-8) - Week 3
- **Phase 5**: Printing System (Task 9) - Week 3
- **Phase 6**: Migration and Testing (Tasks 10-14) - Week 4
- **Phase 7**: Deployment Preparation (Tasks 15-16) - Week 5
