# Schedule Management System - Quick Start Guide

## 🚀 5-Minute Quick Start

### What's New?
✨ Enhanced schedule form with visual shift selection  
✨ Bulk date assignment (select multiple dates at once)  
✨ Real-time monitoring dashboard  
✨ Professional printing with color-coded shifts  

---

## For End Users

### Creating a Schedule

1. **Open Schedule Database**
   - Navigate to Schedule Database page
   - Click "Add Schedule" button

2. **Select Employee**
   - Type employee name or number in search box
   - Click employee from dropdown
   - Name and department auto-fill

3. **Set Time**
   - Select start time (hour, minute, AM/PM)
   - Select end time
   - Shift auto-detects based on time

4. **Choose Shift**
   - Click shift option (shows color indicator)
   - Standard: 85 (Black), OFF (Red outline)
   - Nursing: 62 (Blue), 210 (Green), 106 (Red), etc.

5. **Select Dates**
   - Click dates in calendar to select
   - Or click "Select All Month"
   - Count shows: "X date(s) selected"

6. **Add Remarks** (Optional)
   - Type any notes in remarks field

7. **Save**
   - Click "Save" button
   - Schedules created for all selected dates

### Viewing Schedules

**Calendar View:**
- Week or Month toggle
- Navigate with arrows
- Click schedule block to edit

**Monitoring Dashboard:**
- Click "Show Monitoring" button
- View summary statistics
- Filter by department, shift, status
- Search by employee name
- Click row to edit

### Printing

**Department Schedule:**
- Apply filters (optional)
- Click "Print Department"
- Print preview opens
- Click Print

**Transmittal Report:**
- Click "Print Transmittal"
- Print preview opens
- Shows department summary
- Click Print

---

## For Developers

### Component Structure
```
ScheduleDatabase.vue (Main View)
├── ScheduleForm.vue (Modal Form)
│   ├── TimePicker.vue (Time Selection)
│   └── Legend Store (Shift Colors)
├── MonitoringDashboard.vue (Monitoring)
│   └── Legend Store (Shift Colors)
└── Print Functions (Printing)
    └── Legend Store (Shift Colors)
```

### Key Files
```
client/src/
├── views/schedule/
│   └── ScheduleDatabase.vue (Main view - modified)
├── components/schedule/
│   ├── ScheduleForm.vue (NEW)
│   ├── MonitoringDashboard.vue (NEW)
│   ├── TimePicker.vue (Phase 3)
│   └── ShiftLegend.vue (Phase 3)
├── stores/
│   ├── schedule.js (Enhanced)
│   └── legend.js (NEW)
└── utils/
    └── print.js (Enhanced)
```

### API Endpoints
```javascript
// Schedules
GET    /api/schedule.php
POST   /api/schedule.php
PUT    /api/schedule.php?id=X
DELETE /api/schedule.php?id=X

// Legends
GET    /api/shift_legends.php
GET    /api/shift_legends.php?department=Nursing
```

### Store Methods
```javascript
// Schedule Store
store.addScheduleWithDates(scheduleData, ['2026-05-20', '2026-05-21'])
store.getSchedulesForDate('2026-05-20')
store.getSchedulesByDepartment('Nursing')
store.getShiftColor('62', 'Nursing')
store.getStatusSummary('Nursing')

// Legend Store
legendStore.fetchLegends('Nursing')
legendStore.getColorForShift('62', 'Nursing')
legendStore.getLegendsForDepartment('Nursing')
legendStore.formatShiftDisplay('62', 'Nursing')
```

### Print Functions
```javascript
import { 
  printIndividualSchedule, 
  printDepartmentSchedule, 
  printTransmittalReport 
} from '@/utils/print'

// Individual
printIndividualSchedule(schedule, { includeLegend: true })

// Department
printDepartmentSchedule(schedules, { 
  department: 'Nursing',
  dateRange: 'May 2026'
})

// Transmittal
printTransmittalReport(departments, {
  periodStart: '2026-05-01',
  periodEnd: '2026-05-31'
})
```

---

## Shift Codes Reference

### Standard Shifts
| Code | Time Range | Color | Department |
|------|------------|-------|------------|
| 85 | 8:00 AM - 5:00 PM | Black | All |
| OFF | Off Duty | Red Outline | All |

### Nursing Shifts
| Code | Time Range | Color | Department |
|------|------------|-------|------------|
| 62 | 6:00 AM - 2:00 PM | Blue | Nursing |
| 210 | 2:00 PM - 10:00 PM | Green | Nursing |
| 106 | 10:00 PM - 6:00 AM | Red | Nursing |
| 610 | 6:00 AM - 10:00 PM | Blue+Green | Nursing |
| 26 | 2:00 PM - 6:00 AM | Green+Red | Nursing |

---

## Common Tasks

### Bulk Schedule Assignment
```
1. Select employee
2. Choose shift
3. Select multiple dates (e.g., all weekdays in May)
4. Click Save
→ Creates 20+ schedules in one submission
```

### Department Monitoring
```
1. Click "Show Monitoring"
2. Select department filter
3. View summary stats
4. Expand department section
5. Review all schedules
```

### Monthly Report
```
1. Filter by department
2. Click "Print Department"
3. Print preview shows all schedules
4. Print or Save as PDF
```

### Transmittal Submission
```
1. Ensure all schedules submitted
2. Click "Print Transmittal"
3. Review completion rate
4. Print for HR submission
```

---

## Troubleshooting

### Form Issues
**Problem:** Form doesn't open  
**Solution:** Check permissions, refresh page

**Problem:** Employee search not working  
**Solution:** Verify employee data loaded

**Problem:** Shift colors not showing  
**Solution:** Check shift_legends table has data

### Dashboard Issues
**Problem:** Dashboard empty  
**Solution:** Check if schedules exist, clear filters

**Problem:** Filters not working  
**Solution:** Refresh page, check console

### Print Issues
**Problem:** Print window blocked  
**Solution:** Allow popups in browser

**Problem:** Colors not printing  
**Solution:** Enable "Background graphics" in print settings

**Problem:** Print preview blank  
**Solution:** Check data exists, try different browser

---

## Tips & Tricks

### Efficient Scheduling
- Use "Select All Month" for regular schedules
- Deselect weekends if needed
- Add remarks for special notes
- Use bulk assignment for recurring patterns

### Monitoring
- Keep dashboard open for real-time view
- Use filters to focus on specific departments
- Click rows for quick edits
- Check summary stats regularly

### Printing
- Apply filters before printing
- Use landscape for department schedules
- Use portrait for transmittal reports
- Save as PDF for digital records

---

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| Ctrl+N | New Schedule (when focused) |
| Esc | Close Modal |
| Tab | Navigate Form Fields |
| Enter | Submit Form |
| Ctrl+P | Print (browser default) |

---

## Status Indicators

### Schedule Status
- 🟢 **Submitted** - Schedule submitted and confirmed
- 🟡 **Pending** - Schedule created but not submitted
- 🔴 **Missing** - Schedule required but not created

### Dashboard Colors
- **Green Cards** - Submitted schedules
- **Yellow Cards** - Pending schedules
- **Red Cards** - Missing schedules
- **Blue Cards** - Total schedules

---

## Best Practices

### For HR Staff
1. Create schedules at start of month
2. Use bulk assignment for efficiency
3. Review monitoring dashboard daily
4. Print transmittal reports weekly
5. Keep remarks updated

### For Department Heads
1. Review department schedules regularly
2. Ensure all staff have schedules
3. Monitor submission status
4. Print department schedules for reference
5. Coordinate with HR on changes

### For System Admins
1. Maintain shift legends
2. Monitor system performance
3. Backup schedule data regularly
4. Review audit logs
5. Update documentation

---

## Quick Reference

### Build Info
- **Build Time:** 599ms
- **Status:** Production Ready
- **Version:** Phase 4-6 Complete

### Documentation
- `SCHEDULE_UI_COMPLETE.md` - Full details
- `SCHEDULE_TESTING_GUIDE.md` - Testing procedures
- `PHASE_4-6_SUMMARY.md` - Implementation summary
- `QUICK_START_SCHEDULE.md` - This guide

### Support
- Check console for errors
- Review documentation
- Verify database schema
- Contact IT support

---

## Success Checklist

### For First-Time Users
- [ ] Can open Schedule Database page
- [ ] Can click "Add Schedule" button
- [ ] Can search and select employee
- [ ] Can select shift with colors
- [ ] Can select multiple dates
- [ ] Can save schedule successfully
- [ ] Can view in monitoring dashboard
- [ ] Can print department schedule

### For Power Users
- [ ] Can create 20+ schedules at once
- [ ] Can filter monitoring dashboard
- [ ] Can edit schedules quickly
- [ ] Can print transmittal reports
- [ ] Can use keyboard shortcuts
- [ ] Can troubleshoot common issues

---

**Need Help?**
- 📖 Read full documentation
- 🧪 Run test cases
- 🐛 Check troubleshooting section
- 💬 Contact support

**Happy Scheduling! 🎉**
