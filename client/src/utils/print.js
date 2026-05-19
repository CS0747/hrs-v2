/**
 * Print Utility Functions
 * Provides print functionality for various modules
 */

/**
 * Generate print-friendly HTML for a table
 * @param {Object} options - Print configuration
 * @param {string} options.title - Report title
 * @param {Array} options.headers - Table headers
 * @param {Array} options.data - Table data rows
 * @param {Function} options.formatRow - Function to format each row
 * @param {Object} options.filters - Applied filters (optional)
 * @param {string} options.dateRange - Date range text (optional)
 */
export function printTable({ title, headers, data, formatRow, filters = {}, dateRange = '' }) {
  const now = new Date().toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })

  // Build filter summary
  let filterSummary = ''
  const activeFilters = Object.entries(filters).filter(([_, value]) => value)
  if (activeFilters.length > 0) {
    filterSummary = '<div class="filter-summary"><strong>Filters Applied:</strong> ' +
      activeFilters.map(([key, value]) => `${key}: ${value}`).join(', ') +
      '</div>'
  }

  // Build date range
  let dateRangeHtml = ''
  if (dateRange) {
    dateRangeHtml = `<div class="date-range"><strong>Period:</strong> ${dateRange}</div>`
  }

  // Build table headers
  const headerHtml = headers.map(h => `<th>${h}</th>`).join('')

  // Build table rows
  const rowsHtml = data.map((row, index) => {
    const cells = formatRow(row, index)
    return `<tr>${cells.map(cell => `<td>${cell}</td>`).join('')}</tr>`
  }).join('')

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${title}</title>
      <style>
        @page {
          size: A4 landscape;
          margin: 15mm;
        }
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }
        body {
          font-family: 'Segoe UI', Arial, sans-serif;
          font-size: 10pt;
          color: #333;
          padding: 20px;
        }
        .header {
          text-align: center;
          margin-bottom: 20px;
          border-bottom: 2px solid #1a3a5c;
          padding-bottom: 15px;
        }
        .header h1 {
          font-size: 18pt;
          color: #1a3a5c;
          margin-bottom: 5px;
        }
        .header .subtitle {
          font-size: 11pt;
          color: #666;
          margin-bottom: 3px;
        }
        .header .timestamp {
          font-size: 9pt;
          color: #888;
        }
        .filter-summary, .date-range {
          background: #f8f9fa;
          padding: 8px 12px;
          margin-bottom: 10px;
          border-left: 3px solid #1a3a5c;
          font-size: 9pt;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
        }
        th {
          background: #1a3a5c;
          color: white;
          padding: 8px 6px;
          text-align: left;
          font-weight: 600;
          font-size: 9pt;
          border: 1px solid #0d2a3c;
        }
        td {
          padding: 6px;
          border: 1px solid #ddd;
          font-size: 9pt;
        }
        tr:nth-child(even) {
          background: #f9fafb;
        }
        .footer {
          margin-top: 20px;
          padding-top: 10px;
          border-top: 1px solid #ddd;
          text-align: center;
          font-size: 8pt;
          color: #888;
        }
        .record-count {
          margin-top: 10px;
          font-weight: 600;
          color: #1a3a5c;
        }
        @media print {
          body {
            padding: 0;
          }
          .no-print {
            display: none;
          }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>${title}</h1>
        <div class="subtitle">General Emilio Aguinaldo Memorial Hospital</div>
        <div class="subtitle">Human Resource Information System</div>
        <div class="timestamp">Generated: ${now}</div>
      </div>
      ${filterSummary}
      ${dateRangeHtml}
      <div class="record-count">Total Records: ${data.length}</div>
      <table>
        <thead>
          <tr>${headerHtml}</tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
      </table>
      <div class="footer">
        <p>© ${new Date().getFullYear()} GEAMH HRIS - Confidential Document</p>
        <p>This report was generated electronically and is valid without signature</p>
      </div>
    </body>
    </html>
  `

  // Open print window
  const printWindow = window.open('', '_blank', 'width=1200,height=800')
  if (printWindow) {
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.focus()

    // Wait for content to load, then print
    setTimeout(() => {
      printWindow.print()
    }, 250)
  } else {
    alert('Please allow popups to print reports')
  }
}

/**
 * Print employee list
 */
export function printEmployees(employees, filters = {}) {
  printTable({
    title: 'Employee Masterlist Report',
    headers: ['#', 'Employee No', 'Name', 'Department', 'Position', 'Status', 'Employment Type'],
    data: employees,
    formatRow: (emp, index) => [
      index + 1,
      emp.employeeNo || '—',
      `${emp.lastName}, ${emp.firstName} ${emp.middleName || ''}`.trim(),
      emp.department || '—',
      emp.position || '—',
      emp.status || 'Active',
      emp.employmentType || '—'
    ],
    filters
  })
}

/**
 * Print leave records
 */
export function printLeaveRecords(records, filters = {}) {
  printTable({
    title: 'Leave Records Report',
    headers: ['#', 'Employee', 'Leave Type', 'Date From', 'Date To', 'Days', 'Status', 'Approved By'],
    data: records,
    formatRow: (rec, index) => [
      index + 1,
      rec.employeeName || '—',
      rec.leaveType || '—',
      rec.dateFrom || '—',
      rec.dateTo || '—',
      rec.days || '0',
      rec.status || 'Pending',
      rec.approvedBy || '—'
    ],
    filters
  })
}

/**
 * Print travel orders
 */
export function printTravelOrders(orders, filters = {}) {
  printTable({
    title: 'Travel Orders Report',
    headers: ['#', 'Employee', 'Destination', 'Purpose', 'Date From', 'Date To', 'Days', 'Status'],
    data: orders,
    formatRow: (order, index) => [
      index + 1,
      order.employeeName || '—',
      order.destination || '—',
      order.purpose || '—',
      order.dateFrom || '—',
      order.dateTo || '—',
      order.days || '0',
      order.status || 'Pending'
    ],
    filters
  })
}

/**
 * Print DTR records
 */
export function printDTRRecords(records, filters = {}) {
  printTable({
    title: 'DTR Transmittal Report',
    headers: ['#', 'Employee', 'Period', 'Department', 'Type', 'Status', 'Submitted', 'Verified By'],
    data: records,
    formatRow: (rec, index) => [
      index + 1,
      rec.employeeName || '—',
      rec.period || '—',
      rec.department || '—',
      rec.transmittalType || '—',
      rec.status || 'Pending',
      rec.dateSubmitted || '—',
      rec.verifiedBy || '—'
    ],
    filters
  })
}

/**
 * Print tracking records
 */
export function printTrackingRecords(records, filters = {}, direction = 'Receiving') {
  printTable({
    title: `Document Tracking Report - ${direction}`,
    headers: ['#', 'Document Type', 'Doc No', 'From Office', 'To Office', 'Date Forwarded', 'Date Received', 'Status'],
    data: records,
    formatRow: (rec, index) => [
      index + 1,
      rec.docType || '—',
      rec.docNo || '—',
      rec.fromOffice || '—',
      rec.toOffice || '—',
      rec.dateForwarded || '—',
      rec.dateReceived || '—',
      rec.status || 'Pending'
    ],
    filters
  })
}

/**
 * Print trainings
 */
export function printTrainings(trainings, filters = {}) {
  printTable({
    title: 'Trainings Report',
    headers: ['#', 'Title', 'Category', 'Instructor', 'Venue', 'Start Date', 'End Date', 'Participants', 'Status'],
    data: trainings,
    formatRow: (training, index) => [
      index + 1,
      training.title || '—',
      training.category || '—',
      training.instructor || '—',
      training.venue || '—',
      training.startDate || '—',
      training.endDate || '—',
      `${training.enrolledCount || 0}/${training.maxParticipants || 0}`,
      training.status || 'Upcoming'
    ],
    filters
  })
}

/**
 * Print training attendees list
 */
export function printTrainingAttendees(training, participants = []) {
  const now = new Date().toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })

  // Build participant rows
  const rowsHtml = participants.map((p, index) => `
        <tr>
            <td style="text-align:center;">${index + 1}</td>
            <td>${p.last_name || ''}, ${p.first_name || ''}</td>
            <td>${p.position || '—'}</td>
            <td>${p.department || '—'}</td>
        </tr>
    `).join('')

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Training Attendees List</title>
      <style>
        @page {
          size: A4 portrait;
          margin: 15mm;
        }
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }
        body {
          font-family: 'Arial', sans-serif;
          font-size: 11pt;
          color: #333;
          padding: 20px;
        }
        .header {
          text-align: center;
          margin-bottom: 20px;
          border-bottom: 3px solid #27ae60;
          padding-bottom: 15px;
        }
        .logo {
          width: 80px;
          height: 80px;
          margin: 0 auto 10px;
        }
        .header h1 {
          font-size: 16pt;
          color: #1a3a5c;
          margin-bottom: 3px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        .header .subtitle {
          font-size: 10pt;
          color: #666;
          margin-bottom: 15px;
        }
        .header .doc-title {
          font-size: 13pt;
          font-weight: bold;
          color: #27ae60;
          text-transform: uppercase;
          letter-spacing: 1px;
          margin-top: 10px;
        }
        .training-info {
          background: #f8f9fa;
          border: 2px solid #27ae60;
          border-radius: 8px;
          padding: 15px;
          margin-bottom: 20px;
        }
        .info-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 10px;
        }
        .info-item {
          display: flex;
          gap: 8px;
          font-size: 10pt;
        }
        .info-label {
          font-weight: bold;
          color: #1a3a5c;
          min-width: 120px;
        }
        .info-value {
          color: #333;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
        }
        th {
          background: #27ae60;
          color: white;
          padding: 10px 8px;
          text-align: left;
          font-weight: 600;
          font-size: 10pt;
          border: 1px solid #1e8449;
        }
        td {
          padding: 8px;
          border: 1px solid #ddd;
          font-size: 10pt;
        }
        tr:nth-child(even) {
          background: #f9fafb;
        }
        .footer {
          margin-top: 30px;
          padding-top: 15px;
          border-top: 2px solid #ddd;
          text-align: center;
          font-size: 8pt;
          color: #888;
        }
        .signature-section {
          margin-top: 40px;
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 40px;
        }
        .signature-box {
          text-align: center;
        }
        .signature-line {
          border-top: 1px solid #333;
          margin-top: 50px;
          padding-top: 5px;
          font-size: 10pt;
          font-weight: bold;
        }
        .signature-label {
          font-size: 9pt;
          color: #666;
          margin-top: 3px;
        }
        @media print {
          body {
            padding: 0;
          }
          .no-print {
            display: none;
          }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <img src="${window.location.origin}/GEAMH LOGO.png" alt="GEAMH Logo" class="logo" />
        <h1>General Emilio Aguinaldo Memorial Hospital</h1>
        <div class="subtitle">Human Resource Information System</div>
        <div class="doc-title">Training Attendees List</div>
      </div>

      <div class="training-info">
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Training Title:</span>
            <span class="info-value">${training.title || '—'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Date:</span>
            <span class="info-value">${training.dateFrom || '—'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Category:</span>
            <span class="info-value">${training.category || '—'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Venue:</span>
            <span class="info-value">${training.venue || '—'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Instructor:</span>
            <span class="info-value">${training.instructor || '—'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Total Participants:</span>
            <span class="info-value">${participants.length} / ${training.maxParticipants || 0}</span>
          </div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width:50px;text-align:center;">No.</th>
            <th>Name</th>
            <th>Position</th>
            <th>Department</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml || '<tr><td colspan="4" style="text-align:center;padding:20px;color:#aaa;">No participants enrolled</td></tr>'}
        </tbody>
      </table>

      <div class="signature-section">
        <div class="signature-box">
          <div class="signature-line">_______________________________</div>
          <div class="signature-label">Prepared By</div>
        </div>
        <div class="signature-box">
          <div class="signature-line">_______________________________</div>
          <div class="signature-label">Noted By</div>
        </div>
      </div>

      <div class="footer">
        <p>© ${new Date().getFullYear()} GEAMH HRIS - Confidential Document</p>
        <p>Generated: ${now}</p>
      </div>
    </body>
    </html>
  `

  // Open print window
  const printWindow = window.open('', '_blank', 'width=900,height=700')
  if (printWindow) {
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.focus()

    // Wait for content to load, then print
    setTimeout(() => {
      printWindow.print()
    }, 250)
  } else {
    alert('Please allow popups to print reports')
  }
}

/**
 * Print schedules
 */
export function printSchedules(schedules, filters = {}) {
  printTable({
    title: 'Employee Schedules Report',
    headers: ['#', 'Employee No', 'Employee Name', 'Department', 'Shift', 'Shift Time', 'Days', 'Effective Date'],
    data: schedules,
    formatRow: (sched, index) => [
      index + 1,
      sched.employeeNo || '—',
      sched.employeeName || '—',
      sched.department || '—',
      sched.shift || '—',
      sched.shiftTime || '—',
      (sched.days || []).join(', ') || '—',
      sched.effectiveDate || '—'
    ],
    filters
  })
}

/**
 * Print birthday celebrants
 */
export function printBirthdayCelebrants(celebrants, month = '') {
  printTable({
    title: `Birthday Celebrants Report${month ? ` - ${month}` : ''}`,
    headers: ['#', 'Employee No', 'Name', 'Department', 'Birthday', 'Age', 'Contact'],
    data: celebrants,
    formatRow: (emp, index) => [
      index + 1,
      emp.employeeNo || '—',
      `${emp.lastName}, ${emp.firstName}`,
      emp.department || '—',
      emp.birthday || '—',
      emp.age || '—',
      emp.contactNumber || '—'
    ],
    filters: month ? { Month: month } : {}
  })
}

/**
 * Print payroll records
 */
export function printPayrollRecords(records, filters = {}) {
  printTable({
    title: 'Payroll Records Report',
    headers: ['#', 'Employee', 'Period', 'Basic Pay', 'Deductions', 'Net Pay', 'Status'],
    data: records,
    formatRow: (rec, index) => [
      index + 1,
      rec.employeeName || '—',
      rec.period || '—',
      rec.basicPay ? `₱${parseFloat(rec.basicPay).toLocaleString('en-PH', { minimumFractionDigits: 2 })}` : '—',
      rec.totalDeductions ? `₱${parseFloat(rec.totalDeductions).toLocaleString('en-PH', { minimumFractionDigits: 2 })}` : '—',
      rec.netPay ? `₱${parseFloat(rec.netPay).toLocaleString('en-PH', { minimumFractionDigits: 2 })}` : '—',
      rec.status || 'Pending'
    ],
    filters
  })
}

/**
 * Print audit logs
 */
export function printAuditLogs(logs, filters = {}) {
  printTable({
    title: 'Audit History Report',
    headers: ['#', 'Timestamp', 'User', 'Action', 'Module', 'Details', 'Status'],
    data: logs,
    formatRow: (log, index) => [
      index + 1,
      new Date(log.timestamp).toLocaleString('en-PH'),
      log.userName || '—',
      log.action || '—',
      log.module || '—',
      (log.details || '—').substring(0, 50) + (log.details?.length > 50 ? '...' : ''),
      log.status || 'OK'
    ],
    filters
  })
}

export default {
  printTable,
  printEmployees,
  printLeaveRecords,
  printTravelOrders,
  printDTRRecords,
  printTrackingRecords,
  printTrainings,
  printTrainingAttendees,
  printSchedules,
  printBirthdayCelebrants,
  printPayrollRecords,
  printAuditLogs
}


/**
 * Enhanced Print Functions for Schedule Management
 */

import { useLegendStore } from '@/stores/legend'

// Get shift color from legend store
function getShiftColor(shiftCode, department = null) {
  const legendStore = useLegendStore()
  const colors = legendStore.getColorForShift(shiftCode, department)
  return colors.primary
}

// Get shift display info
function getShiftDisplay(shiftCode, department = null) {
  const legendStore = useLegendStore()
  return legendStore.formatShiftDisplay(shiftCode, department)
}

// Helper function to generate legend HTML
function getLegendHTML(department = null) {
  const legendStore = useLegendStore()
  const legends = legendStore.getLegendsForDepartment(department)

  return legends.map(legend => `
    <div class="legend-item">
      <div class="legend-color" style="background: ${legend.colorPrimary}; ${legend.code === 'OFF' ? 'background: transparent; border: 2px solid ' + legend.colorPrimary + ';' : ''}"></div>
      <span class="legend-text">${legend.code} - ${legend.timeRange}</span>
    </div>
  `).join('')
}

/**
 * Print Individual Schedule
 * @param {Object} schedule - Schedule object
 * @param {Object} options - Print options
 */
export function printIndividualSchedule(schedule, options = {}) {
  const {
    title = 'Employee Schedule',
    includeLegend = true
  } = options

  const shiftDisplay = getShiftDisplay(schedule.shiftCode, schedule.department)
  const shiftColor = getShiftColor(schedule.shiftCode, schedule.department)

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${title}</title>
      <style>
        @page { size: A4 portrait; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #1a3a5c; padding-bottom: 15px; }
        .header img { height: 60px; margin-bottom: 10px; }
        .header h1 { font-size: 20pt; color: #1a3a5c; margin-bottom: 5px; }
        .header p { font-size: 10pt; color: #666; }
        
        .employee-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-label { font-weight: bold; width: 150px; color: #666; }
        .info-value { color: #1a3a5c; font-weight: 600; }
        
        .schedule-details { margin-bottom: 20px; }
        .schedule-details h3 { color: #1a3a5c; margin-bottom: 10px; border-bottom: 2px solid #e9ecef; padding-bottom: 5px; }
        
        .schedule-card { border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .schedule-card .date { font-size: 14pt; font-weight: bold; color: #1a3a5c; margin-bottom: 10px; }
        .schedule-card .shift-info { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .schedule-card .shift-badge { 
          padding: 6px 12px; 
          border-radius: 4px; 
          font-weight: bold; 
          color: #fff;
          background: ${shiftColor};
        }
        .schedule-card .time-range { font-size: 12pt; color: #555; }
        .schedule-card .status { 
          display: inline-block;
          padding: 4px 10px; 
          border-radius: 12px; 
          font-size: 10pt; 
          font-weight: bold;
        }
        .status-submitted { background: #eafaf1; color: #27ae60; }
        .status-pending { background: #fef9e7; color: #f39c12; }
        
        .legend { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .legend h4 { color: #1a3a5c; margin-bottom: 10px; font-size: 11pt; }
        .legend-items { display: flex; flex-wrap: wrap; gap: 10px; }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-color { width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; }
        .legend-text { font-size: 10pt; color: #666; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 9pt; color: #999; border-top: 1px solid #e9ecef; padding-top: 10px; }
        
        @media print {
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <img src="/client/public/GEAMH LOGO.png" alt="GEAMH Logo" onerror="this.style.display='none'">
        <h1>GEAMH HRIS</h1>
        <p>${title}</p>
      </div>
      
      <div class="employee-info">
        <div class="info-row">
          <span class="info-label">Employee Name:</span>
          <span class="info-value">${schedule.employeeName}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Employee No:</span>
          <span class="info-value">${schedule.employeeNo}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Department:</span>
          <span class="info-value">${schedule.department}</span>
        </div>
      </div>
      
      <div class="schedule-details">
        <h3>Schedule Details</h3>
        <div class="schedule-card">
          <div class="date">${new Date(schedule.scheduleDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</div>
          <div class="shift-info">
            <span class="shift-badge">${shiftDisplay.code}</span>
            <span class="time-range">${shiftDisplay.timeRange}</span>
          </div>
          <div>
            <span class="status status-${(schedule.status || 'pending').toLowerCase()}">${schedule.status || 'Pending'}</span>
          </div>
          ${schedule.remarks ? `<div style="margin-top: 10px; color: #666;"><strong>Remarks:</strong> ${schedule.remarks}</div>` : ''}
        </div>
      </div>
      
      ${includeLegend ? `
      <div class="legend">
        <h4>Shift Legend</h4>
        <div class="legend-items">
          ${getLegendHTML(schedule.department)}
        </div>
      </div>
      ` : ''}
      
      <div class="footer">
        Generated on ${new Date().toLocaleString('en-US')} | GEAMH HRIS Schedule Management System
      </div>
    </body>
    </html>
  `

  const printWindow = window.open('', '_blank')
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}

/**
 * Print Department Schedule
 * @param {Array} schedules - Array of schedule objects
 * @param {Object} options - Print options
 */
export function printDepartmentSchedule(schedules, options = {}) {
  const {
    title = 'Department Schedule',
    department = 'All Departments',
    dateRange = '',
    includeLegend = true
  } = options

  // Group by employee
  const byEmployee = {}
  schedules.forEach(schedule => {
    const key = schedule.employeeNo
    if (!byEmployee[key]) {
      byEmployee[key] = {
        employeeName: schedule.employeeName,
        employeeNo: schedule.employeeNo,
        department: schedule.department,
        schedules: []
      }
    }
    byEmployee[key].schedules.push(schedule)
  })

  const employees = Object.values(byEmployee)

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${title}</title>
      <style>
        @page { size: A4 landscape; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; }
        
        .header { text-align: center; margin-bottom: 15px; border-bottom: 3px solid #1a3a5c; padding-bottom: 10px; }
        .header img { height: 50px; margin-bottom: 8px; }
        .header h1 { font-size: 18pt; color: #1a3a5c; margin-bottom: 3px; }
        .header p { font-size: 9pt; color: #666; }
        
        .info-bar { display: flex; justify-content: space-between; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .info-item { font-size: 9pt; }
        .info-label { font-weight: bold; color: #666; }
        .info-value { color: #1a3a5c; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        thead { background: #1a3a5c; color: #fff; }
        th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 9pt; border: 1px solid #fff; }
        td { padding: 6px; border: 1px solid #e9ecef; font-size: 9pt; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        
        .emp-name { font-weight: 600; color: #1a3a5c; }
        .emp-no { font-size: 8pt; color: #888; font-family: monospace; }
        .shift-badge { 
          display: inline-block;
          padding: 3px 8px; 
          border-radius: 3px; 
          font-weight: bold; 
          font-size: 8pt;
          color: #fff;
        }
        .status-badge {
          display: inline-block;
          padding: 2px 6px;
          border-radius: 8px;
          font-size: 8pt;
          font-weight: bold;
        }
        .status-submitted { background: #eafaf1; color: #27ae60; }
        .status-pending { background: #fef9e7; color: #f39c12; }
        
        .legend { margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; page-break-inside: avoid; }
        .legend h4 { color: #1a3a5c; margin-bottom: 8px; font-size: 10pt; }
        .legend-items { display: flex; flex-wrap: wrap; gap: 8px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-color { width: 14px; height: 14px; border-radius: 50%; border: 1px solid #ddd; }
        .legend-text { font-size: 8pt; color: #666; }
        
        .footer { margin-top: 15px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #e9ecef; padding-top: 8px; }
        
        @media print {
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <img src="/client/public/GEAMH LOGO.png" alt="GEAMH Logo" onerror="this.style.display='none'">
        <h1>GEAMH HRIS</h1>
        <p>${title}</p>
      </div>
      
      <div class="info-bar">
        <div class="info-item">
          <span class="info-label">Department:</span>
          <span class="info-value">${department}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Period:</span>
          <span class="info-value">${dateRange || 'All Dates'}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Total Employees:</span>
          <span class="info-value">${employees.length}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Total Schedules:</span>
          <span class="info-value">${schedules.length}</span>
        </div>
      </div>
      
      <table>
        <thead>
          <tr>
            <th style="width: 15%;">Employee</th>
            <th style="width: 12%;">Date</th>
            <th style="width: 10%;">Shift</th>
            <th style="width: 18%;">Time</th>
            <th style="width: 10%;">Status</th>
            <th style="width: 35%;">Remarks</th>
          </tr>
        </thead>
        <tbody>
          ${employees.map(emp => emp.schedules.map(schedule => {
    const shiftDisplay = getShiftDisplay(schedule.shiftCode, schedule.department)
    const shiftColor = getShiftColor(schedule.shiftCode, schedule.department)
    return `
              <tr>
                <td>
                  <div class="emp-name">${emp.employeeName}</div>
                  <div class="emp-no">${emp.employeeNo}</div>
                </td>
                <td>${new Date(schedule.scheduleDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                <td><span class="shift-badge" style="background: ${shiftColor};">${shiftDisplay.code}</span></td>
                <td style="font-size: 8pt;">${shiftDisplay.timeRange}</td>
                <td><span class="status-badge status-${(schedule.status || 'pending').toLowerCase()}">${schedule.status || 'Pending'}</span></td>
                <td style="font-size: 8pt;">${schedule.remarks || '—'}</td>
              </tr>
            `
  }).join('')).join('')}
        </tbody>
      </table>
      
      ${includeLegend ? `
      <div class="legend">
        <h4>Shift Legend</h4>
        <div class="legend-items">
          ${getLegendHTML(department)}
        </div>
      </div>
      ` : ''}
      
      <div class="footer">
        Generated on ${new Date().toLocaleString('en-US')} | GEAMH HRIS Schedule Management System
      </div>
    </body>
    </html>
  `

  const printWindow = window.open('', '_blank')
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}

/**
 * Print Transmittal Report
 * @param {Array} departments - Array of department data
 * @param {Object} options - Print options
 */
export function printTransmittalReport(departments, options = {}) {
  const {
    title = 'Schedule Transmittal Report',
    periodStart = '',
    periodEnd = ''
  } = options

  const totalStaff = departments.reduce((sum, dept) => sum + dept.staffCount, 0)
  const totalSubmitted = departments.reduce((sum, dept) => sum + dept.submittedCount, 0)

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${title}</title>
      <style>
        @page { size: A4 portrait; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        
        .header { text-align: center; margin-bottom: 25px; border-bottom: 3px solid #1a3a5c; padding-bottom: 15px; }
        .header img { height: 70px; margin-bottom: 10px; }
        .header h1 { font-size: 22pt; color: #1a3a5c; margin-bottom: 5px; }
        .header p { font-size: 11pt; color: #666; }
        
        .period-info { text-align: center; margin-bottom: 20px; padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .period-info strong { color: #1a3a5c; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        thead { background: #1a3a5c; color: #fff; }
        th { padding: 12px 10px; text-align: left; font-weight: 600; border: 1px solid #fff; }
        td { padding: 10px; border: 1px solid #e9ecef; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        
        .summary { margin-bottom: 30px; padding: 15px; background: #e8f0fe; border-radius: 8px; border-left: 4px solid #1a3a5c; }
        .summary h3 { color: #1a3a5c; margin-bottom: 10px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .summary-label { font-weight: 600; color: #666; }
        .summary-value { font-weight: bold; color: #1a3a5c; font-size: 13pt; }
        
        .signatures { margin-top: 40px; display: flex; justify-content: space-around; }
        .signature-block { text-align: center; }
        .signature-line { border-top: 2px solid #333; width: 200px; margin: 40px auto 5px; }
        .signature-label { font-size: 10pt; color: #666; }
        .signature-name { font-weight: bold; color: #1a3a5c; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 9pt; color: #999; border-top: 1px solid #e9ecef; padding-top: 10px; }
        
        @media print {
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <img src="/client/public/GEAMH LOGO.png" alt="GEAMH Logo" onerror="this.style.display='none'">
        <h1>GEAMH HRIS</h1>
        <p>${title}</p>
      </div>
      
      ${periodStart && periodEnd ? `
      <div class="period-info">
        <strong>Period:</strong> ${new Date(periodStart).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} - ${new Date(periodEnd).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
      </div>
      ` : ''}
      
      <table>
        <thead>
          <tr>
            <th style="width: 10%;">Page No.</th>
            <th style="width: 25%;">Department</th>
            <th style="width: 15%;">Staff Count</th>
            <th style="width: 15%;">Submitted</th>
            <th style="width: 15%;">Date Submitted</th>
            <th style="width: 20%;">Remarks</th>
          </tr>
        </thead>
        <tbody>
          ${departments.map((dept, index) => `
            <tr>
              <td style="text-align: center;">${index + 1}</td>
              <td><strong>${dept.department}</strong></td>
              <td style="text-align: center;">${dept.staffCount}</td>
              <td style="text-align: center;"><strong>${dept.submittedCount}</strong></td>
              <td>${dept.dateSubmitted ? new Date(dept.dateSubmitted).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
              <td>${dept.remarks || '—'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      
      <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row">
          <span class="summary-label">Total Departments:</span>
          <span class="summary-value">${departments.length}</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Total Staff:</span>
          <span class="summary-value">${totalStaff}</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Total Submitted:</span>
          <span class="summary-value">${totalSubmitted}</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Completion Rate:</span>
          <span class="summary-value">${totalStaff > 0 ? Math.round((totalSubmitted / totalStaff) * 100) : 0}%</span>
        </div>
      </div>
      
      <div class="signatures">
        <div class="signature-block">
          <div class="signature-line"></div>
          <div class="signature-label">Prepared By</div>
          <div class="signature-name">HR Department</div>
        </div>
        <div class="signature-block">
          <div class="signature-line"></div>
          <div class="signature-label">Noted By</div>
          <div class="signature-name">Department Head</div>
        </div>
      </div>
      
      <div class="footer">
        Generated on ${new Date().toLocaleString('en-US')} | GEAMH HRIS Schedule Management System
      </div>
    </body>
    </html>
  `

  const printWindow = window.open('', '_blank')
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}
