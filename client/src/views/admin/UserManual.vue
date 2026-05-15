<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// Filled black SVG icons matching sidebar style
const sectionIcons = {
  login:           `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>`,
  dashboard:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>`,
  employees:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  birthday:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 6c1.11 0 2-.89 2-2 0-.32-.08-.61-.21-.87L12 0l-1.79 3.13c-.13.26-.21.55-.21.87 0 1.11.89 2 2 2zm4.6 9.99l-1.07-1.07-1.08 1.07c-1.3 1.3-3.58 1.31-4.89 0l-1.07-1.07-1.09 1.07C6.75 17.74 5.88 18 5 18c-.88 0-1.75-.26-2.45-.66V22c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-4.66c-.7.4-1.57.66-2.45.66-.88 0-1.75-.26-2.5-.01zM18 9H6c-1.66 0-3 1.34-3 3v1.54c0 1.08.88 1.96 1.96 1.96.54 0 1.02-.22 1.38-.57l2.14-2.13 2.13 2.13c.74.74 2.03.74 2.77 0l2.14-2.13 2.13 2.13c.36.35.84.57 1.38.57C20.12 15.5 21 14.62 21 13.54V12c0-1.66-1.34-3-3-3z"/></svg>`,
  schedule:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>`,
  trainings:       `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>`,
  departments:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
  dtr:             `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>`,
  audit_transmittal:`<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>`,
  leave:           `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/><path d="M9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>`,
  to:              `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5h-9.17C6.41 5 2 9.41 2 14.83V15h20V6c0-.55-.45-1-1-1m-8 6H5.01c1.34-2.38 3.89-4 6.82-4H13zm-8 8h16c.55 0 1-.45 1-1v-2H2c0 1.65 1.35 3 3 3"/></svg>`,
  verification:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  tracking:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
  signatories:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
  ai:              `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 10.12h-6.78l2.74-2.82c-2.73-2.7-7.15-2.8-9.88-.1-2.73 2.71-2.73 7.08 0 9.79s7.15 2.71 9.88 0C18.32 15.65 19 14.08 19 12.1h2c0 1.98-.88 4.55-2.64 6.29-3.51 3.48-9.21 3.48-12.72 0-3.5-3.47-3.53-9.11-.02-12.58s9.14-3.47 12.65 0L21 3v7.12zM12.5 8v4.25l3.5 2.08-.72 1.21L11 13V8h1.5z"/></svg>`,
  audit_history:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 9H7v-2h4v2zm4-4H7v-2h8v2zm0-4H7V8h8v2z"/></svg>`,
  version_history: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>`,
  accounts:        `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
  roles:           `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>`,
}

const allSections = [
  {
    id: 'login',
    title: 'Login',
    iconKey: 'login',
    roles: ['Super Admin','Admin','DIOS'],
    content: `To access the GEAMH HRIS, enter your assigned username and Biometrics Number (minimum 6 characters) on the login page. The Biometrics Number serves as your secure password. Click "Log In" to proceed. If you forget your credentials, contact the System Administrator.`,
    details: [
      { label: 'Username', value: 'Your assigned system username (e.g. superadmin, admin, dios123)' },
      { label: 'Biometrics Number', value: 'Minimum 6 characters — serves as your password' },
      { label: 'Create Account', value: 'Available on the login page — accessible to authorized users only' },
    ],
  },
  {
    id: 'dashboard',
    title: 'Dashboard',
    iconKey: 'dashboard',
    roles: ['Super Admin','Admin','DIOS'],
    content: `The Dashboard is the home screen after login. It displays real-time statistics and quick navigation tools.`,
    details: [
      { label: 'Total Employees', value: 'Count of all active employees in the system' },
      { label: 'Birthday Celebrants', value: 'Employees with birthdays this month — shows upcoming celebrant with scrolling ticker' },
      { label: 'Pending Leaves', value: 'Number of leave requests awaiting approval' },
      { label: 'DTR Pending', value: 'DTR transmittals not yet processed' },
      { label: 'Turning 65 This Year', value: 'Employees reaching retirement age' },
      { label: 'Quick Actions', value: 'Shortcut buttons: Add Employee, DTR Transmittal, Leave Request, AI Scanning, Schedule, Trainings' },
    ],
  },
  {
    id: 'employees',
    title: 'Employee Masterlist',
    iconKey: 'employees',
    roles: ['DIOS','Super Admin','Admin'],
    content: `The Employee Masterlist contains all employee records. You can search, filter, sort, add, edit, and delete employees. An "Edited" badge appears on rows that have been modified.`,
    details: [
      { label: 'Add Employee', value: 'Click "Add Employee" to create a new record. Fill in personal info, employment details, and government IDs.' },
      { label: 'Edit Employee', value: 'Click the pencil icon. A confirmation modal appears before saving. A "Previous" snapshot is saved automatically.' },
      { label: 'Delete Employee', value: 'Click the trash icon. A confirmation modal with employee details appears before deletion.' },
      { label: 'Version History', value: 'Click the clock icon on any row to view all changes. Types: ✏️ Edited (after save), 📋 Previous (before edit). Click "View Snapshot" to see exact data at that time.' },
      { label: 'Work Schedule', value: 'Standard: Mon–Sat (6 days/week). Sunday is the rest day.' },
      { label: 'Filters', value: 'Filter by Department, Status, Gender, Group (GEAMH/KP). Sort by any column.' },
      { label: 'Birthday Celebrants', value: 'Click 🎂 Birthdays to view all celebrants for the current month.' },
    ],
    note: { type: 'info', text: 'Section Admin can view but cannot add, edit, or delete employee records.' },
  },
  {
    id: 'birthday',
    title: 'Birthday Celebrants',
    iconKey: 'birthday',
    roles: ['Super Admin','Admin','DIOS'],
    content: `The Birthday Celebrants page shows all employees with birthdays in the selected month. Filter by month, search by name, and toggle to show employees aged 65+.`,
    details: [
      { label: 'Month Filter', value: 'Select any month to view celebrants for that period' },
      { label: 'Search', value: 'Search by employee name' },
      { label: 'Show 65+ Only', value: 'Toggle to filter employees at retirement age' },
      { label: 'Countdown', value: 'Each card shows days until the next birthday' },
      { label: 'Retirement Flag', value: '🎖️ Retirement Age badge appears for employees turning 65' },
    ],
  },
  {
    id: 'schedule',
    title: 'Schedule Database',
    iconKey: 'schedule',
    roles: ['DIOS','Super Admin','Admin'],
    content: `The Schedule Database manages employee work schedules. New schedules must be approved before they can be edited.`,
    details: [
      { label: 'Add Schedule', value: 'Search for an employee, select shift type, working days (max 6), and effective dates. Rest day is auto-calculated.' },
      { label: 'Approval Workflow', value: 'New schedules start as "Pending". Use ✓ Approve or ✗ Reject buttons. Only approved schedules can be edited.' },
      { label: 'Working Days', value: 'Select up to 6 days. Once 6 are selected, remaining days are disabled. Rest day updates automatically.' },
      { label: 'Shift Types', value: 'Morning (7AM–3PM), Afternoon (3PM–11PM), Night (11PM–7AM), Split, Flexible' },
      { label: 'Filters', value: 'Filter by Department, Shift, and Approval Status' },
    ],
    note: { type: 'warning', text: 'Section Admin can add and edit schedules. All other sections are view-only for Section Admin.' },
  },
  {
    id: 'trainings',
    title: 'Trainings',
    iconKey: 'trainings',
    roles: ['DIOS','Super Admin','Admin'],
    content: `The Trainings module manages employee training programs. View trainings as cards, click to see participants, and manage attendance.`,
    details: [
      { label: 'Add Training', value: 'Fill in title, category, instructor, venue, dates, duration, max participants, and status.' },
      { label: 'Categories', value: 'Medical, Nursing, Administrative, Technical, Leadership, Safety, Other' },
      { label: 'Status', value: 'Upcoming, Ongoing, Completed, Cancelled' },
      { label: 'Participants', value: 'Click a training card to view enrolled employees. Toggle attended/absent per participant.' },
      { label: 'Add Participants', value: 'Search and select employees to enroll. Already-enrolled employees are excluded from search.' },
      { label: 'Enrollment Bar', value: 'Visual progress bar shows enrollment vs. max capacity. Turns orange when 90%+ full.' },
    ],
  },
  {
    id: 'departments',
    title: 'Departments',
    iconKey: 'departments',
    roles: ['DIOS','Super Admin','Admin'],
    content: `Manage hospital departments. Add, edit, deactivate, and reactivate departments. The department list is used in employee records and filters throughout the system.`,
    details: [
      { label: 'Add Department', value: 'Enter department name, code (e.g. NUR), and description.' },
      { label: 'Edit Department', value: 'Update name, code, description, or status.' },
      { label: 'Deactivate', value: 'Soft-delete — sets department to inactive. Data is preserved.' },
      { label: 'Status Toggle', value: 'Click the Active/Inactive badge directly on the row to toggle status.' },
      { label: 'Auto-sync', value: 'Department list updates automatically in all dropdowns across the system.' },
    ],
  },
  {
    id: 'dtr',
    title: 'DTR Transmittal',
    iconKey: 'dtr',
    roles: ['DIOS','Super Admin','Admin'],
    content: `Manage Daily Time Record transmittals. Track the submission, receipt, and verification of DTR documents from all departments.`,
    details: [
      { label: 'Add DTR Record', value: 'Enter employee info, period, transmittal type (Main/Thea/Other), and submission details.' },
      { label: 'Status Flow', value: 'Pending → Submitted → Received → Verified → Returned' },
      { label: 'Filters', value: 'Filter by period, department, transmittal type, and status' },
      { label: 'Verification', value: 'Record who verified the DTR and the verification date' },
    ],
  },
  {
    id: 'audit_transmittal',
    title: 'Audit & Transmittal',
    iconKey: 'audit_transmittal',
    roles: ['DIOS','Super Admin','Admin'],
    content: `The Audit & Transmittal section tracks document transmittals for audit purposes. Monitor the flow of official documents between offices.`,
    details: [
      { label: 'Document Types', value: 'DTR Transmittal, Leave Form, Travel Order, Memorandum, Other' },
      { label: 'Tracking', value: 'Record document number, origin office, destination, forwarding date, and receipt date' },
      { label: 'Status', value: 'Pending, In Transit, Received, Returned, Lost' },
    ],
  },
  {
    id: 'leave',
    title: 'Leave Management',
    iconKey: 'leave',
    roles: ['DIOS','Super Admin','Admin'],
    content: `Process and track employee leave requests. Approve or disapprove applications and monitor leave history.`,
    details: [
      { label: 'Leave Types', value: 'Vacation, Sick, Maternity, Paternity, Special, Emergency, Forced, Study, Rehabilitation, Terminal' },
      { label: 'Add Leave', value: 'Select employee, leave type, date range, number of days, and reason.' },
      { label: 'Approval', value: 'Change status to Approved or Disapproved. Record approver name and date.' },
      { label: 'Filters', value: 'Filter by leave type, status, and date range' },
    ],
  },
  {
    id: 'to',
    title: 'Travel Orders',
    iconKey: 'to',
    roles: ['DIOS','Super Admin','Admin'],
    content: `Manage employee travel orders. Filter records and generate printable reports.`,
    details: [
      { label: 'Add T.O.', value: 'Enter employee, destination, purpose, dates, number of days, and transport type.' },
      { label: 'Transport Types', value: 'Public Transport, Government Vehicle, Private Vehicle' },
      { label: 'Filters', value: 'Filter by status, department, date from, and date to' },
      { label: 'Print', value: 'Click 🖨 Print to generate a printable report of filtered records' },
      { label: 'Status', value: 'Pending, Approved, Disapproved' },
    ],
  },
  {
    id: 'verification',
    title: 'Verification',
    iconKey: 'verification',
    roles: ['DIOS','Super Admin','Admin'],
    content: `The Verification module tracks the verification status of DTR and other documents. Record who verified each document and when.`,
    details: [
      { label: 'Verify', value: 'Mark documents as verified, record verifier name and date' },
      { label: 'Status', value: 'Pending, Verified, Returned' },
    ],
  },
  {
    id: 'tracking',
    title: 'Tracking & Receiving',
    iconKey: 'tracking',
    roles: ['Super Admin','Admin','DIOS'],
    content: `Track the flow of documents between offices. Two tabs: Receiving (incoming) and Outgoing (sent). Default assignee is HR AMELA (Gonzales, Realyn P.).`,
    details: [
      { label: 'Receiving Tab', value: 'Log incoming documents. Click "Receive" to mark as received — auto-fills HR AMELA as receiver.' },
      { label: 'Outgoing Tab', value: 'Log documents sent out from the HR office.' },
      { label: 'Default Assignee', value: 'Gonzales, Realyn P. (HR AMELA) — pre-filled in all receiving/outgoing forms.' },
      { label: 'Print', value: 'Click 🖨 Print to generate a report of the current tab\'s filtered records.' },
      { label: 'Filters', value: 'Filter by document type and status' },
    ],
  },
  {
    id: 'signatories',
    title: 'Signatories',
    iconKey: 'signatories',
    roles: ['DIOS','Super Admin','Admin'],
    content: `Manage the list of authorized signatories for official documents. Set signing order and active status.`,
    details: [
      { label: 'Add Signatory', value: 'Enter name, position, role (e.g. Final Approver), department, and signing order.' },
      { label: 'Signing Order', value: 'Determines the sequence in which signatories must sign documents.' },
      { label: 'Active/Inactive', value: 'Toggle signatory status without deleting the record.' },
    ],
  },
  {
    id: 'ai',
    title: 'AI Scanning Tools',
    iconKey: 'ai',
    roles: ['Super Admin','Admin','DIOS'],
    content: `Upload HR documents for automatic data extraction using AI/OCR technology. Preview extracted data before saving to the system.`,
    details: [
      { label: 'Supported Files', value: 'PDF, JPG, PNG, Excel (XLSX/CSV), Word (DOCX) — Max 20 MB' },
      { label: 'Best Accuracy', value: 'Upload original digital files (Excel/PDF) for perfect extraction. Photos of documents have lower OCR accuracy due to lighting and angle.' },
      { label: 'OCR for Images', value: 'Photos are processed with adaptive thresholding and sharpening before OCR. Results may vary based on image quality.' },
      { label: 'Preview', value: 'Review extracted data before saving. Edit fields if needed.' },
      { label: 'Export', value: 'Export extracted data as 📊 Excel (CSV) or 📝 Word (DOC) using the export buttons.' },
      { label: 'Save to System', value: 'Click "Save to System" to store the scan in the database.' },
    ],
  },
  {
    id: 'audit_history',
    title: 'Audit History',
    iconKey: 'audit_history',
    roles: ['Super Admin','DIOS'],
    content: `View a complete log of all user actions in the system. Every login, data change, and deletion is recorded with timestamp, user, and details.`,
    details: [
      { label: 'Action Types', value: 'LOGIN, LOGOUT, CREATE, UPDATE, DELETE, VIEW, EXPORT, OTHER' },
      { label: 'Filters', value: 'Filter by module, action type, user name, and date range' },
      { label: 'Search', value: 'Search by user name, action, or details' },
      { label: 'Export CSV', value: 'Click ⬇ Export CSV to download the filtered log as a spreadsheet' },
      { label: 'Modules', value: 'Auth, Employee, DTR, Leave, T.O., Schedule, Training, Tracking, Signatory, Other' },
    ],
  },
  {
    id: 'version_history',
    title: 'Version History',
    iconKey: 'version_history',
    roles: ['Super Admin','DIOS'],
    content: `Track all changes made to the Employee Masterlist. Every edit is recorded with the data before and after the change.`,
    details: [
      { label: '✏️ Edited', value: 'Recorded after a save is confirmed — shows the updated data' },
      { label: '📋 Previous', value: 'Recorded when Edit is clicked — shows the data before editing' },
      { label: 'View Snapshot', value: 'Click "View Snapshot" to see the exact employee data at that point in time, including work schedule (Mon–Sat, 5 days/week)' },
      { label: 'Filter', value: 'Filter by type (Edited/Previous) or search by employee name' },
      { label: 'Clear History', value: 'Super Admin can clear all version history (irreversible)' },
    ],
  },
  {
    id: 'accounts',
    title: 'Account Management',
    iconKey: 'accounts',
    roles: ['DIOS'],
    content: `Manage all system user accounts. View, add, edit, and delete accounts. Available to DIOS role.`,
    details: [
      { label: 'Roles Available', value: 'Super Admin, Admin, IT, Section Admin, DIOS' },
      { label: 'Add Account', value: 'Enter full name, username, role, department, and biometrics number (min 6 chars).' },
      { label: 'Edit Account', value: 'Update any field. Leave biometrics number blank to keep the current one.' },
      { label: 'Delete Account', value: 'Cannot delete your own account. Confirmation required.' },
      { label: 'Role Colors', value: 'Super Admin=Green, Admin=Blue, IT=Purple, Section Admin=Orange, DIOS=Globe' },
    ],
  },
  {
    id: 'roles',
    title: 'Role Permissions',
    iconKey: 'roles',
    roles: ['Super Admin','Admin','DIOS'],
    content: `Each role has specific access permissions. DIOS is the head of the system with full access. The table below summarizes what each role can do.`,
    roleTable: true,
  },
]

// Show all sections to all logged-in users (User Manual is documentation for everyone)
const userRole = computed(() => auth.userRole)
const sections = computed(() => allSections)

const activeSection = ref('login')
</script>

<template>
  <div class="page">
    <div class="manual-layout">

      <!-- Sidebar nav -->
      <div class="manual-nav">
      <div class="manual-nav-title">
          <span class="manual-title-icon" v-html="sectionIcons.roles"></span>
          User Manual
        </div>
        <div class="manual-nav-role">
          Logged in as: <strong>{{ userRole || 'Guest' }}</strong>
        </div>
        <button
          v-for="s in sections" :key="s.id"
          class="nav-item"
          :class="{ active: activeSection === s.id }"
          @click="activeSection = s.id"
        >
          <span class="nav-icon" v-html="sectionIcons[s.iconKey]"></span>
          <span class="nav-label">{{ s.title }}</span>
        </button>
      </div>

      <!-- Content -->
      <div class="manual-content">
        <div v-for="s in sections" :key="s.id" v-show="activeSection === s.id" class="section-body">

          <div class="section-header">
            <span class="section-icon" v-html="sectionIcons[s.iconKey]"></span>
            <div>
              <h2>{{ s.title }}</h2>
              <div class="section-roles">
                <span v-for="r in s.roles" :key="r" class="role-chip" :class="'role-' + r.toLowerCase().replace(' ','-')">{{ r }}</span>
              </div>
            </div>
          </div>

          <div class="section-text">{{ s.content }}</div>

          <!-- Details table -->
          <div v-if="s.details" class="details-table">
            <div v-for="d in s.details" :key="d.label" class="detail-row">
              <span class="detail-label">{{ d.label }}</span>
              <span class="detail-value">{{ d.value }}</span>
            </div>
          </div>

          <!-- Note box -->
          <div v-if="s.note" class="info-box" :class="s.note.type === 'warning' ? 'warning' : ''">
            {{ s.note.text }}
          </div>

          <!-- Role permissions table -->
          <div v-if="s.roleTable" class="role-table-wrap">
            <table class="role-table">
              <thead>
                <tr>
                  <th>Feature</th>
                  <th class="col-dios">DIOS</th>
                  <th>Super Admin</th>
                  <th>Admin (HR Admin)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in [
                  ['Dashboard',            '✅','✅','✅'],
                  ['Employee Masterlist',  '✅','✅','✅'],
                  ['Birthday Celebrants',  '✅','✅','✅'],
                  ['Schedule Database',    '✅','✅','✅'],
                  ['Trainings',            '✅','✅','✅'],
                  ['Departments',          '✅','✅','✅'],
                  ['DTR Transmittal',      '✅','✅','✅'],
                  ['Audit & Transmittal',  '✅','✅','✅'],
                  ['Leave Management',     '✅','✅','✅'],
                  ['Travel Orders',        '✅','✅','✅'],
                  ['Verification',         '✅','✅','✅'],
                  ['Tracking & Receiving', '✅','✅','✅'],
                  ['Signatories',          '✅','✅','✅'],
                  ['AI Scanning Tools',    '✅','✅','✅'],
                  ['Audit History',        '✅','👁 View only','❌'],
                  ['Version History',      '✅','👁 View only','❌'],
                  ['Account Management',   '✅','❌','❌'],
                  ['User Manual',          '✅','✅','✅'],
                ]" :key="row[0]">
                  <td class="feature-col">{{ row[0] }}</td>
                  <td v-for="(v,i) in row.slice(1)" :key="i" class="perm-col" :class="v.startsWith('✅') ? 'yes' : v.startsWith('❌') ? 'no' : 'partial'">{{ v }}</td>
                </tr>
              </tbody>
            </table>
            <div class="role-legend">
              <span class="legend-item dios-badge">DIOS</span> — Head of system, full access to all features including Account Management<br>
              <span class="legend-item sa-badge">Super Admin</span> — Can manage HR data; view-only on Audit/Version History; no Account Management<br>
              <span class="legend-item admin-badge">Admin (HR Admin)</span> — Can manage HR data; no access to Audit History, Version History, or Account Management
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page { padding: 24px; height: calc(100vh - 60px); box-sizing: border-box; }
.manual-layout { display: grid; grid-template-columns: 270px 1fr; gap: 20px; height: 100%; }

/* Nav */
.manual-nav { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow-y: auto; padding: 8px 0; }
.manual-nav-title { padding: 14px 16px 6px; font-size: 14px; font-weight: 800; color: #1a3a5c; border-bottom: 1px solid #f0f4f8; display: flex; align-items: center; gap: 8px; }
.manual-title-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; flex-shrink: 0; }
.manual-title-icon :deep(svg) { width: 18px; height: 18px; fill: #1a3a5c; }
.manual-nav-role { padding: 6px 16px 10px; font-size: 11px; color: #888; border-bottom: 1px solid #f0f4f8; margin-bottom: 4px; }
.manual-nav-role strong { color: #1a6b3c; }
.nav-item { width: 100%; display: flex; align-items: center; gap: 10px; padding: 8px 16px; background: none; border: none; cursor: pointer; font-size: 12px; color: #555; text-align: left; transition: background 0.15s; }
.nav-item:hover { background: #f0f9f4; color: #1a6b3c; }
.nav-item.active { background: #e8f5ee; color: #1a6b3c; font-weight: 700; border-left: 3px solid #1a6b3c; }
.nav-icon { width: 18px; height: 18px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.nav-icon :deep(svg) { width: 16px; height: 16px; fill: #555; }
.nav-item:hover .nav-icon :deep(svg) { fill: #1a6b3c; }
.nav-item.active .nav-icon :deep(svg) { fill: #1a6b3c; }
.nav-label { flex: 1; line-height: 1.3; text-align: left; }

/* Content */
.manual-content { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 28px 32px; overflow-y: auto; }
.section-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 2px solid #f0f4f8; }
.section-icon { width: 36px; height: 36px; flex-shrink: 0; margin-top: 2px; display: flex; align-items: center; justify-content: center; }
.section-icon :deep(svg) { width: 32px; height: 32px; fill: #1a3a5c; }
.section-header h2 { margin: 0 0 8px; font-size: 20px; color: #1a3a5c; }
.section-roles { display: flex; flex-wrap: wrap; gap: 4px; }
.role-chip { padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: 700; }
.role-super-admin { background: #e8f5ee; color: #1a6b3c; }
.role-admin       { background: #e8f0fe; color: #1a3a5c; }
.role-it          { background: #f5eef8; color: #8e44ad; }
.role-section-admin { background: #fef3e2; color: #e67e22; }
.role-dios        { background: #ebf5fb; color: #2980b9; }
.section-text { font-size: 14px; color: #444; line-height: 1.8; margin-bottom: 16px; }

/* Details table */
.details-table { border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; margin-bottom: 14px; }
.detail-row { display: flex; border-bottom: 1px solid #f0f4f8; }
.detail-row:last-child { border-bottom: none; }
.detail-label { min-width: 180px; padding: 9px 14px; font-size: 12px; font-weight: 700; color: #1a3a5c; background: #f8f9fa; border-right: 1px solid #e9ecef; flex-shrink: 0; }
.detail-value { padding: 9px 14px; font-size: 13px; color: #444; line-height: 1.5; }

/* Info boxes */
.info-box { background: #e8f5ee; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #1a6b3c; line-height: 1.6; margin-top: 8px; }
.info-box.warning { background: #fff8e1; border-color: #ffc107; color: #856404; }

/* Role permissions table */
.role-table-wrap { overflow-x: auto; margin-top: 8px; }
.role-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.role-table thead tr { background: #1a3a5c; color: #fff; }
.role-table th { padding: 10px 12px; text-align: center; font-weight: 600; white-space: nowrap; }
.role-table th:first-child { text-align: left; }
.role-table th.col-dios { background: #1a5c8b; }
.role-table td { padding: 8px 12px; border-bottom: 1px solid #f0f4f8; text-align: center; }
.role-table tbody tr:hover { background: #f9fafb; }
.feature-col { text-align: left !important; font-weight: 600; color: #1a3a5c; }
.perm-col.yes     { color: #27ae60; }
.perm-col.no      { color: #e74c3c; }
.perm-col.partial { color: #e67e22; font-size: 11px; }

/* Role legend */
.role-legend { margin-top: 14px; padding: 12px 16px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #555; line-height: 2; }
.legend-item { display: inline-block; padding: 2px 10px; border-radius: 10px; font-weight: 700; font-size: 11px; margin-right: 4px; }
.dios-badge  { background: #ebf5fb; color: #2980b9; }
.sa-badge    { background: #e8f5ee; color: #1a6b3c; }
.admin-badge { background: #e8f0fe; color: #1a3a5c; }
</style>
