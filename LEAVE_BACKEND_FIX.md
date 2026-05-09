# Leave Management Backend Fix

## Issue
The Leave Management table was showing empty employee names and missing data fields because:
1. Backend API was returning snake_case field names (`employee_name`, `leave_type`, etc.)
2. Frontend was expecting camelCase field names (`employeeName`, `leaveType`, etc.)
3. Field name mismatch caused data not to display properly

## Solution Applied

### 1. Backend API Update (`server/api/leave.php`)
**Changed:** GET endpoint now maps database snake_case fields to frontend camelCase format

**Before:**
```php
$result = $conn->query('SELECT * FROM leave_records ORDER BY date_from DESC');
sendJson($result->fetch_all(MYSQLI_ASSOC));
```

**After:**
```php
$result = $conn->query('SELECT * FROM leave_records ORDER BY date_from DESC');
$rows = $result->fetch_all(MYSQLI_ASSOC);
// Map all rows to camelCase
$mapped = array_map(function($row) {
    return [
        'id' => (int)$row['id'],
        'employeeId' => $row['employee_id'] ? (int)$row['employee_id'] : null,
        'employeeNo' => $row['employee_no'],
        'employeeName' => $row['employee_name'],
        'department' => $row['department'],
        'leaveType' => $row['leave_type'],
        'dateFrom' => $row['date_from'],
        'dateTo' => $row['date_to'],
        'days' => (float)$row['days'],
        'reason' => $row['reason'],
        'status' => $row['status'],
        'approvedBy' => $row['approved_by'],
        'dateApproved' => $row['date_approved'],
        'remarks' => $row['remarks']
    ];
}, $rows);
sendJson($mapped);
```

### 2. Frontend Store Update (`client/src/stores/leave.js`)
**Changed:** Explicitly map response fields to ensure consistency

```javascript
leaveRecords.value = data.map(record => ({
    id: record.id,
    employeeId: record.employeeId,
    employeeNo: record.employeeNo,
    employeeName: record.employeeName,
    department: record.department,
    leaveType: record.leaveType,
    dateFrom: record.dateFrom,
    dateTo: record.dateTo,
    days: record.days,
    reason: record.reason,
    status: record.status,
    approvedBy: record.approvedBy,
    dateApproved: record.dateApproved,
    remarks: record.remarks
}))
```

### 3. Sample Data (`server/seed_leave_records.sql`)
**Created:** SQL file with 10 sample leave records for testing

**To populate sample data:**
1. Open phpMyAdmin
2. Select `geamh_hris` database
3. Go to SQL tab
4. Copy and paste contents of `server/seed_leave_records.sql`
5. Click "Go" to execute

## Field Mapping Reference

| Database (snake_case) | Frontend (camelCase) | Type |
|----------------------|---------------------|------|
| id | id | int |
| employee_id | employeeId | int/null |
| employee_no | employeeNo | string |
| employee_name | employeeName | string |
| department | department | string |
| leave_type | leaveType | string |
| date_from | dateFrom | string (date) |
| date_to | dateTo | string (date) |
| days | days | float |
| reason | reason | string |
| status | status | string |
| approved_by | approvedBy | string |
| date_approved | dateApproved | string (date) |
| remarks | remarks | string |

## Testing Steps

1. **Verify Backend Response:**
   - Open browser console
   - Go to Network tab
   - Navigate to Leave Management page
   - Check the response from `leave.php` API call
   - Verify fields are in camelCase format

2. **Verify Frontend Display:**
   - Employee names should display in the table
   - All columns should show data
   - Search and filters should work properly

3. **Test CRUD Operations:**
   - Add new leave record using employee dropdown
   - Edit existing record
   - Delete record
   - All operations should work correctly

## Files Modified
- `server/api/leave.php` - Added field mapping in GET endpoint
- `client/src/stores/leave.js` - Added explicit field mapping in fetchRecords()
- `server/seed_leave_records.sql` - Created sample data file

## Notes
- Backend POST/PUT operations already use correct snake_case for database
- Only GET operations needed camelCase mapping for frontend
- Employee dropdown integration works with this fix
- All existing functionality preserved
