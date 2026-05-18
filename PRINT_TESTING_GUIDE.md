# Print Functionality Testing Guide

## Quick Test Instructions

### 1. Employee Masterlist
**URL**: `/employees`
1. Apply filters (Status, Gender, etc.)
2. Click "🖨 Print" button
3. Verify:
   - Employee list displays correctly
   - Filters shown at top
   - All columns visible (Emp No, Name, Department, Position, Status, Employment Type)

### 2. Leave Management
**URL**: `/leave`
1. Filter by Leave Type or Status
2. Click "🖨 Print" button
3. Verify:
   - Leave records display
   - Date ranges correct
   - Status badges visible

### 3. Travel Orders
**URL**: `/travel-orders`
1. Apply filters (Status, Department, Date Range)
2. Click "🖨 Print" button
3. Verify:
   - Travel order details complete
   - Destination and purpose visible
   - Days calculated correctly

### 4. DTR Transmittal
**URL**: `/dtr`
1. Test both tabs: "DTR Records" and "Transmittal History"
2. Click "🖨 Print" on each tab
3. Verify:
   - Records tab: Shows current DTR records
   - History tab: Shows historical transmittals
   - Period and status correct

### 5. Tracking/Receiving
**URL**: `/tracking`
1. Test both tabs: "Receiving" and "Outgoing"
2. Click "🖨 Print" on each tab
3. Verify:
   - Receiving: Shows incoming documents
   - Outgoing: Shows outgoing documents
   - Document numbers and dates correct

### 6. Trainings Management
**URL**: `/trainings`
1. Filter by Category or Status
2. Click "🖨 Print" button
3. Verify:
   - Training titles and details
   - Participant counts
   - Dates and venues

### 7. Schedule Database
**URL**: `/schedule`
1. Filter by Department or Shift
2. Click "🖨 Print" button
3. Verify:
   - Employee schedules
   - Shift times
   - Days of week

### 8. Birthday Celebrants
**URL**: `/employees/birthdays`
1. Select month
2. Click "🖨 Print" button
3. Verify:
   - Birthday list for selected month
   - Ages calculated correctly
   - Contact information included

### 9. Payroll Masterlist
**URL**: `/payroll`
1. Filter by Period or Status
2. Click "🖨 Print" button
3. Verify:
   - Salary amounts formatted with ₱
   - Deductions shown
   - Net pay calculated correctly

### 10. Audit History
**URL**: `/admin/audit-history`
1. Filter by Module or Action
2. Click "🖨 Print" button
3. Verify:
   - Audit log entries
   - Timestamps formatted correctly
   - User actions visible

## Common Checks for All Modules

### Visual Checks
- [ ] GEAMH logo displays at top
- [ ] Report title is clear
- [ ] Timestamp shows current date/time
- [ ] Filter summary displays applied filters
- [ ] Record count matches filtered data
- [ ] Table headers are bold and styled
- [ ] Alternating row colors for readability
- [ ] Footer with copyright notice

### Functional Checks
- [ ] Print dialog opens automatically
- [ ] Print preview shows all data
- [ ] No data truncation
- [ ] All columns fit on page (landscape)
- [ ] Page breaks appropriately for long lists
- [ ] Print button doesn't cause errors

### Browser Compatibility
Test on:
- [ ] Chrome
- [ ] Firefox
- [ ] Edge
- [ ] Safari (if available)

### Popup Blocker Test
- [ ] If popup blocked, user sees warning message
- [ ] User can allow popups and retry

## Expected Print Format

```
┌─────────────────────────────────────────────────────────┐
│ [LOGO] General Emilio Aguinaldo Memorial Hospital      │
│        Human Resource Information System                │
├─────────────────────────────────────────────────────────┤
│ Report Title                                            │
│ Generated: MM/DD/YYYY, HH:MM:SS AM/PM                  │
│                                                         │
│ Filters Applied: Status: Active, Department: HR        │
│ Total Records: 25                                       │
│                                                         │
│ ┌──────┬──────────┬────────────┬──────────┐          │
│ │ Col1 │ Col2     │ Col3       │ Col4     │          │
│ ├──────┼──────────┼────────────┼──────────┤          │
│ │ Data │ Data     │ Data       │ Data     │          │
│ │ Data │ Data     │ Data       │ Data     │          │
│ └──────┴──────────┴────────────┴──────────┘          │
│                                                         │
│ © 2026 GEAMH HRIS - Confidential Document             │
│ This report was generated electronically               │
└─────────────────────────────────────────────────────────┘
```

## Troubleshooting

### Print button not visible
- Check user permissions for the module
- Verify button is in toolbar-right section

### Print window doesn't open
- Check browser popup blocker settings
- Allow popups for localhost/your domain

### Data not showing
- Verify filtered data exists
- Check console for JavaScript errors

### Formatting issues
- Ensure A4 landscape is selected in print settings
- Check margins are set to default

### Logo not displaying
- Verify `/GEAMH LOGO.png` exists in public folder
- Check image path in print utility

## Success Criteria

✅ All 11 modules have working print buttons
✅ Print dialogs open without errors
✅ Reports display correct filtered data
✅ Professional formatting maintained
✅ GEAMH branding consistent
✅ No console errors
✅ Works across major browsers

## Report Issues

If you find any issues:
1. Note the module name
2. Describe the problem
3. Include any console errors
4. Specify browser and version
5. Provide steps to reproduce

---
**Testing Status**: Ready for QA
**Last Updated**: $(Get-Date -Format "yyyy-MM-dd")
