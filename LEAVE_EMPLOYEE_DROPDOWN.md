# Leave Management - Employee Dropdown Feature

## Overview
Added an employee dropdown selector to the Leave Management form, replacing manual employee number and name input fields.

## Changes Made

### 1. Frontend Component (`client/src/views/leave/LeaveManagement.vue`)
- **Imported** `useEmployeeStore` to access employee list
- **Added** searchable employee dropdown with auto-complete functionality
- **Replaced** manual input fields with read-only auto-filled fields
- **Features**:
  - Search by employee name or employee number
  - Shows employee details (name, number, department, position)
  - Auto-fills employee number, name, and department when selected
  - Clear button to reset selection
  - Click-outside handler to close dropdown
  - Displays first 50 employees by default, filters on search
  - Visual feedback for selected employee

### 2. Form Validation
- Changed validation from `employeeNo` and `employeeName` to `employeeId`
- Ensures an employee is selected from the dropdown before saving

### 3. Data Flow
- Employee data is fetched from database via `useEmployeeStore`
- When employee is selected:
  - `form.employeeId` → Employee's database ID
  - `form.employeeNo` → Auto-filled from employee record
  - `form.employeeName` → Auto-filled as "LastName, FirstName MiddleName"
  - `form.department` → Auto-filled from employee record
- All fields are sent to backend API when saving

### 4. Backend API (`server/api/leave.php`)
- Already supports `employee_id` field (no changes needed)
- Stores employee reference in `leave_records.employee_id` column

## User Experience

### Adding Leave Record:
1. Click "Add Leave" button
2. Search for employee by typing name or employee number
3. Select employee from dropdown
4. Employee details auto-fill (number, name, department)
5. Fill in leave details (type, dates, reason, etc.)
6. Save

### Editing Leave Record:
1. Click edit button on existing record
2. Employee dropdown shows current employee name
3. Can change employee by searching and selecting different one
4. Update other fields as needed
5. Save changes

## Benefits
- **Data Consistency**: Ensures employee data matches database records
- **User-Friendly**: No need to manually type employee details
- **Error Prevention**: Eliminates typos in employee names/numbers
- **Fast Search**: Quick filtering by name or number
- **Visual Clarity**: Shows department and position for verification

## Technical Details
- Employee list loaded on component mount via `employeeStore.fetchEmployees()`
- Dropdown shows max 50 results (performance optimization)
- Search is case-insensitive and matches partial strings
- Click-outside handler prevents dropdown from staying open
- Disabled/readonly fields prevent manual editing of auto-filled data
