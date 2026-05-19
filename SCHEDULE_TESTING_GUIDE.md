# Schedule Management System - Testing Guide

## Quick Start Testing

### Prerequisites
1. Ensure database migration is complete (shift_legends table exists)
2. Server is running at `http://localhost/hrs-v2/`
3. Client build is deployed to production path

### Test Sequence

---

## Test 1: Basic Form Access ✅

**Steps:**
1. Navigate to Schedule Database page
2. Click "Add Schedule" button
3. Verify modal opens with new ScheduleForm component

**Expected Results:**
- Modal displays with "Add Schedule" title
- ScheduleForm component loads
- Employee search input is visible
- Time picker is visible
- Shift selector with colors is visible
- Calendar date picker is visible

---

## Test 2: Employee Selection ✅

**Steps:**
1. Click on employee search input
2. Type employee name or number
3. Select an employee from dropdown

**Expected Results:**
- Dropdown shows filtered employees
- Employee No, Name, and Department display
- On selection:
  - Employee name field populates (readonly)
  - Department field populates (readonly)
  - Department-specific shift legends load

---

## Test 3: Shift Selection ✅

**Steps:**
1. After selecting employee, view shift selector
2. Click different shift options
3. Observe color indicators

**Expected Results:**
- Standard department shows: 85 (Black), OFF (Red outline)
- Nursing department shows: 62 (Blue), 210 (Green), 106 (Red), 610 (Split), 26 (Split), 85, OFF
- Selected shift highlights with border
- Shift code and time range display

---

## Test 4: Time Picker ✅

**Steps:**
1. Click start time dropdown
2. Select hour and minute
3. Select AM/PM
4. Repeat for end time

**Expected Results:**
- Time dropdowns work smoothly
- 12-hour format displays correctly
- Shift auto-detection works (if matching legend)
- Time range updates in shift selector

---

## Test 5: Bulk Date Selection ✅

**Steps:**
1. View the calendar date picker
2. Click individual dates to select
3. Click "Select All Month" button
4. Click "Clear" button

**Expected Results:**
- Individual dates toggle selection (blue background)
- Selected count updates
- "Select All Month" selects all dates in current month
- "Clear" removes all selections
- Weekend dates show in gray
- Today shows with border

---

## Test 6: Form Submission (Bulk) ✅

**Steps:**
1. Fill complete form:
   - Select employee
   - Select shift
   - Set time range
   - Select 5 dates in calendar
   - Add remarks (optional)
2. Click "Save" button

**Expected Results:**
- Form submits successfully
- 5 schedule records created (one per date)
- Success notification appears
- Modal closes
- Schedules appear in monitoring dashboard

---

## Test 7: Monitoring Dashboard ✅

**Steps:**
1. Click "Show Monitoring" button in toolbar
2. Observe dashboard slide down
3. View summary statistics
4. Expand/collapse department sections

**Expected Results:**
- Dashboard slides down smoothly
- Summary cards show correct counts (Total, Submitted, Pending, Missing)
- Departments are grouped
- Each department shows schedule count and mini stats
- Click department header to expand/collapse

---

## Test 8: Dashboard Filtering ✅

**Steps:**
1. In monitoring dashboard, select department filter
2. Select shift filter
3. Select status filter
4. Type in employee search

**Expected Results:**
- Filters apply immediately
- Schedule list updates
- Summary statistics update
- "Clear Filters" button appears
- Click "Clear Filters" resets all

---

## Test 9: Schedule Editing from Dashboard ✅

**Steps:**
1. In monitoring dashboard, click a schedule row
2. Verify edit modal opens
3. Modify schedule details
4. Save changes

**Expected Results:**
- Edit modal opens with schedule data pre-filled
- Form shows existing values
- Changes save successfully
- Dashboard updates with new data

---

## Test 10: Print Department Schedule ✅

**Steps:**
1. Apply filters (optional): select department
2. Click "Print Department" button
3. Verify print preview opens

**Expected Results:**
- New window opens with print preview
- GEAMH header displays
- Department and period info shows
- Table displays all filtered schedules
- Shift badges show with colors
- Shift legend displays at bottom
- Print dialog appears automatically

---

## Test 11: Print Transmittal Report ✅

**Steps:**
1. Click "Print Transmittal" button
2. Verify print preview opens

**Expected Results:**
- New window opens with print preview
- GEAMH header displays
- Period information shows
- Department-wise table displays:
  - Page No.
  - Department name
  - Staff count
  - Submitted count
  - Date submitted
  - Remarks
- Summary section shows:
  - Total departments
  - Total staff
  - Total submitted
  - Completion rate %
- Signature blocks display (Prepared By, Noted By)
- Print dialog appears automatically

---

## Test 12: Shift Color Display ✅

**Steps:**
1. Create schedules with different shifts
2. View in monitoring dashboard
3. Print department schedule

**Expected Results:**
- Each shift displays correct color:
  - 85: Black
  - 62: Blue
  - 210: Green
  - 106: Red
  - 610: Blue+Green split
  - 26: Green+Red split
  - OFF: Red outline (transparent background)
- Colors consistent across:
  - Form shift selector
  - Monitoring dashboard table
  - Print preview

---

## Test 13: Legacy Schedule Compatibility ✅

**Steps:**
1. View existing old schedules (with days array)
2. Verify they still display in calendar
3. Edit an old schedule
4. Save changes

**Expected Results:**
- Old schedules display correctly
- Calendar shows legacy format schedules
- Edit form loads legacy data
- Can update legacy schedules
- New format and legacy format coexist

---

## Test 14: Empty States ✅

**Steps:**
1. Clear all filters
2. Apply filter with no results
3. View monitoring dashboard with no schedules

**Expected Results:**
- Empty state message displays
- Helpful hint shows ("Try adjusting filters...")
- No errors in console
- UI remains stable

---

## Test 15: Responsive Behavior ✅

**Steps:**
1. Resize browser window
2. Test on different screen sizes
3. Verify modal responsiveness

**Expected Results:**
- Monitoring dashboard adapts to width
- Modal remains centered
- Form fields wrap appropriately
- Calendar remains usable
- Print layout maintains structure

---

## Browser Compatibility Testing

### Chrome/Edge ✅
- [ ] All features work
- [ ] Print colors display
- [ ] Transitions smooth

### Firefox ✅
- [ ] All features work
- [ ] Print colors display
- [ ] Transitions smooth

### Safari ✅
- [ ] All features work
- [ ] Print colors display (may need -webkit-print-color-adjust)
- [ ] Transitions smooth

---

## Performance Testing

### Load Time ✅
- [ ] Page loads in < 2 seconds
- [ ] Monitoring dashboard renders in < 500ms
- [ ] Form opens instantly

### Build Time ✅
- [x] Build completes in < 600ms (Current: 599ms)

### Data Handling ✅
- [ ] Handles 100+ schedules smoothly
- [ ] Filtering is instant
- [ ] No lag in calendar selection

---

## Error Handling Testing

### Network Errors ✅
- [ ] API failure shows error notification
- [ ] Form remains usable after error
- [ ] Retry mechanism works

### Validation Errors ✅
- [ ] Missing employee shows error
- [ ] Invalid time range shows error
- [ ] No dates selected shows warning

### Edge Cases ✅
- [ ] Duplicate schedule detection
- [ ] Overlapping time ranges
- [ ] Invalid date selection

---

## Accessibility Testing

### Keyboard Navigation ✅
- [ ] Tab through form fields
- [ ] Enter to submit
- [ ] Escape to close modal
- [ ] Arrow keys in dropdowns

### Screen Reader ✅
- [ ] Form labels read correctly
- [ ] Button purposes clear
- [ ] Status messages announced

---

## Security Testing

### Input Validation ✅
- [ ] SQL injection prevention
- [ ] XSS prevention in remarks field
- [ ] CSRF token validation

### Permission Checks ✅
- [ ] Add button hidden without permission
- [ ] Edit restricted by permission
- [ ] Delete restricted by permission

---

## Regression Testing

### Existing Features ✅
- [ ] Calendar view still works
- [ ] Week/Month toggle works
- [ ] Mini calendar navigation works
- [ ] Legacy schedule display works
- [ ] Delete modal works
- [ ] Update confirmation works

---

## Test Results Template

```
Test Date: _______________
Tester: _______________
Environment: _______________

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Basic Form Access | ⬜ Pass ⬜ Fail | |
| 2 | Employee Selection | ⬜ Pass ⬜ Fail | |
| 3 | Shift Selection | ⬜ Pass ⬜ Fail | |
| 4 | Time Picker | ⬜ Pass ⬜ Fail | |
| 5 | Bulk Date Selection | ⬜ Pass ⬜ Fail | |
| 6 | Form Submission | ⬜ Pass ⬜ Fail | |
| 7 | Monitoring Dashboard | ⬜ Pass ⬜ Fail | |
| 8 | Dashboard Filtering | ⬜ Pass ⬜ Fail | |
| 9 | Schedule Editing | ⬜ Pass ⬜ Fail | |
| 10 | Print Department | ⬜ Pass ⬜ Fail | |
| 11 | Print Transmittal | ⬜ Pass ⬜ Fail | |
| 12 | Shift Color Display | ⬜ Pass ⬜ Fail | |
| 13 | Legacy Compatibility | ⬜ Pass ⬜ Fail | |
| 14 | Empty States | ⬜ Pass ⬜ Fail | |
| 15 | Responsive Behavior | ⬜ Pass ⬜ Fail | |

Overall Status: ⬜ Pass ⬜ Fail
```

---

## Bug Report Template

```
Bug ID: _______________
Date: _______________
Tester: _______________

**Summary:**
Brief description of the bug

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**
What should happen

**Actual Result:**
What actually happened

**Severity:**
⬜ Critical ⬜ High ⬜ Medium ⬜ Low

**Screenshots:**
Attach if applicable

**Browser/Environment:**
Browser: _______________
OS: _______________
Screen Size: _______________

**Console Errors:**
Paste any console errors here
```

---

## Quick Smoke Test (5 minutes)

For rapid verification after deployment:

1. ✅ Open Schedule Database page
2. ✅ Click "Add Schedule" - form opens
3. ✅ Select an employee - fields populate
4. ✅ Select a shift - colors display
5. ✅ Select 3 dates - count shows "3 date(s) selected"
6. ✅ Click "Save" - schedules created
7. ✅ Click "Show Monitoring" - dashboard appears
8. ✅ Click "Print Department" - print preview opens
9. ✅ Click schedule row - edit modal opens
10. ✅ Close modal - no errors

**If all 10 steps pass, deployment is successful! 🚀**

---

## Support & Troubleshooting

### Common Issues

**Issue:** Form doesn't open
- Check browser console for errors
- Verify component imports
- Check permissions

**Issue:** Shift colors don't display
- Verify shift_legends table has data
- Check legend store initialization
- Verify API endpoint accessible

**Issue:** Print doesn't work
- Allow browser popups
- Check print-color-adjust CSS support
- Verify print.js functions imported

**Issue:** Bulk dates don't save
- Check API payload format
- Verify specificDates array
- Check server-side bulk insert logic

---

## Contact

For issues or questions:
- Check console errors first
- Review SCHEDULE_UI_COMPLETE.md
- Check API responses in Network tab
- Verify database schema matches migration

**Happy Testing! 🎉**
