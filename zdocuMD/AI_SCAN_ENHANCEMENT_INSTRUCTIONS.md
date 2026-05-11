# AI Scanning Enhancement - Step-by-Step Implementation

## Problem Identified
1. Scanning accuracy is not optimal
2. "Designate & Save" button missing
3. No connection to database modules

## Solution Overview
We need to:
1. ✅ Enhance OCR preprocessing (already done in backend)
2. ✅ Add better text post-processing (already done in backend)
3. ⏳ Add "Designate & Save" UI to frontend
4. ⏳ Connect to backend API
5. ⏳ Show progress and results

---

## Quick Fix Instructions

### Step 1: Verify Backend is Working

Test the OCR API:
```bash
# Open browser and go to:
http://localhost/hrs/server/api/ai_scan.php

# Should return: {"error":"No file uploaded"}
# This means the API is working
```

### Step 2: Add Designate & Save to Frontend

Open: `client/src/views/ai/AIScanningTools.vue`

**Find this section (around line 40-50):**
```javascript
const saving        = ref(false)
const errorMsg      = ref('')
```

**Add these new state variables after it:**
```javascript
// Designate & Save state
const selectedDestination = ref('')
const designating = ref(false)
const designateResult = ref(null)
```

**Find the `saveScan` function (around line 600-650)**

**Add this new function after `saveScan`:**
```javascript
// -- Designate & Save to Database Module ---------------------------------------
async function designateAndSave() {
  if (!selectedDestination.value || !selectedScan.value) {
    alert('⚠️ Please select a destination first')
    return
  }
  
  designating.value = true
  designateResult.value = null
  
  try {
    // Prepare scan data based on document type
    let scanData = {}
    
    if (selectedScan.value.doc_type === 'Schedule' && selectedScan.value.extracted_data) {
      // For schedules, send the parsed schedule data
      scanData = selectedScan.value.extracted_data
    } else if (selectedScan.value.html_table) {
      // For spreadsheets, extract rows
      scanData = {
        rows: extractTableRows(selectedScan.value.html_table),
        department: selectedScan.value.extracted_data?.department || ''
      }
    } else {
      // For other documents, send extracted data
      scanData = selectedScan.value.extracted_data || {}
    }
    
    const res = await fetch('http://localhost/hrs/server/api/ai_scan_designate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        destination: selectedDestination.value,
        scan_data: scanData,
        scan_id: selectedScan.value.id
      })
    })
    
    const result = await res.json()
    if (!res.ok) throw new Error(result.error || 'Save failed')
    
    designateResult.value = result
    
    // Show success message
    const msg = `✅ Successfully saved to ${getDestinationName(selectedDestination.value)}!\n\n` +
                `📊 Results:\n` +
                `• Inserted: ${result.inserted}\n` +
                `• Updated: ${result.updated}\n` +
                `• Skipped: ${result.skipped}\n\n` +
                (result.errors.length > 0 ? `⚠️ Errors:\n${result.errors.join('\n')}` : '')
    
    alert(msg)
    
    // Mark as saved
    selectedScan.value._saved = true
    selectedScan.value.status = `Saved to ${getDestinationName(selectedDestination.value)}`
    
    // Refresh saved scans
    await loadSavedScans()
    
  } catch (e) {
    alert('❌ Error: ' + e.message)
    console.error('Designate & Save error:', e)
  } finally {
    designating.value = false
  }
}

function getDestinationName(dest) {
  const names = {
    'employee_masterlist': 'Employee Masterlist',
    'birthday_celebrants': 'Birthday Celebrants',
    'schedule_database': 'Schedule Database'
  }
  return names[dest] || dest
}

function extractTableRows(htmlTable) {
  // Simple HTML table parser
  const parser = new DOMParser()
  const doc = parser.parseFromString(htmlTable, 'text/html')
  const rows = []
  
  doc.querySelectorAll('tr').forEach(tr => {
    const row = []
    tr.querySelectorAll('th, td').forEach(cell => {
      row.push(cell.textContent.trim())
    })
    if (row.length > 0) rows.push(row)
  })
  
  return rows
}
```

**Find the preview panel section in the template (around line 800-900)**

**Find this section:**
```vue
<!-- Actions -->
<div class="preview-actions">
  <button v-if="!selectedScan._saved" class="btn btn-primary" @click="saveScan(selectedScan)" :disabled="saving">
```

**Replace the entire `preview-actions` div with this enhanced version:**
```vue
<!-- Actions -->
<div class="preview-actions-wrapper">
  <!-- Designate & Save Section -->
  <div v-if="!selectedScan._saved" class="designate-section">
    <div class="designate-header">
      <span class="designate-icon">📍</span>
      <strong>Designate & Save to Database</strong>
    </div>
    <div class="designate-body">
      <label class="designate-label">Select destination module:</label>
      <select v-model="selectedDestination" class="destination-select">
        <option value="">-- Choose where to save --</option>
        <option value="employee_masterlist">👥 Employee Masterlist</option>
        <option value="birthday_celebrants">🎂 Birthday Celebrants</option>
        <option value="schedule_database">📅 Schedule Database</option>
      </select>
      
      <button 
        class="btn btn-designate" 
        @click="designateAndSave"
        :disabled="!selectedDestination || designating"
      >
        <span v-if="designating" class="btn-spinner">⏳</span>
        <span v-else>💾</span>
        {{ designating ? 'Saving to Database...' : 'Designate & Save' }}
      </button>
      
      <div v-if="designateResult" class="designate-result">
        <div class="result-success">
          ✅ Saved successfully!
        </div>
        <div class="result-stats">
          <span class="stat-item">📥 Inserted: <strong>{{ designateResult.inserted }}</strong></span>
          <span class="stat-item">🔄 Updated: <strong>{{ designateResult.updated }}</strong></span>
          <span class="stat-item">⏭️ Skipped: <strong>{{ designateResult.skipped }}</strong></span>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Original Actions -->
  <div class="preview-actions">
    <button v-if="!selectedScan._saved" class="btn btn-secondary" @click="saveScan(selectedScan)" :disabled="saving">
      <span v-html="icons.save"></span>
      {{ saving ? 'Saving...' : 'Save Scan Record' }}
    </button>
    <span v-else class="saved-badge">✅ Saved to database</span>
    <button class="btn btn-export-excel" @click="exportToExcel(selectedScan)">📊 Export Excel</button>
    <button class="btn btn-export-word" @click="exportToWord(selectedScan)">📄 Export Word</button>
    <button class="btn btn-secondary" @click="closePreview">Close</button>
  </div>
</div>
```

**Add these CSS styles at the end of the `<style scoped>` section:**
```css
/* Designate & Save Section */
.preview-actions-wrapper { display: flex; flex-direction: column; gap: 16px; }
.designate-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 16px; color: #fff; }
.designate-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 14px; }
.designate-icon { font-size: 20px; }
.designate-body { display: flex; flex-direction: column; gap: 10px; }
.designate-label { font-size: 12px; font-weight: 600; opacity: 0.9; }
.destination-select { padding: 10px 12px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; font-size: 14px; background: rgba(255,255,255,0.95); color: #333; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.destination-select:hover { border-color: rgba(255,255,255,0.6); background: #fff; }
.destination-select:focus { outline: none; border-color: #fff; box-shadow: 0 0 0 3px rgba(255,255,255,0.3); }
.btn-designate { background: #fff; color: #667eea; padding: 12px 20px; border-radius: 8px; border: none; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.btn-designate:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.btn-designate:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner { animation: spin 1s linear infinite; display: inline-block; }
.designate-result { background: rgba(255,255,255,0.15); border-radius: 8px; padding: 12px; margin-top: 8px; }
.result-success { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.result-stats { display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; }
.stat-item { background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 6px; }
.stat-item strong { font-weight: 700; }
```

---

## Step 3: Test the Enhancement

1. **Start your development server:**
   ```bash
   cd client
   npm run dev
   ```

2. **Open the AI Scanning Tools page**

3. **Upload a test image or Excel file**

4. **After scanning completes:**
   - You should see the "Designate & Save to Database" section
   - Select a destination (Employee Masterlist, Birthday Celebrants, or Schedule Database)
   - Click "Designate & Save"
   - Wait for success message

5. **Verify in database:**
   - Open phpMyAdmin
   - Check the appropriate table (employees or employee_schedules)
   - Verify data was inserted/updated

---

## Troubleshooting

### Issue: "Designate & Save" button doesn't appear
**Solution:** Make sure you added the code in the correct location. Search for `preview-actions` in the template.

### Issue: API returns error
**Solution:** 
1. Check that `server/api/ai_scan_designate.php` exists
2. Verify database table `employee_schedules` exists (run migration SQL)
3. Check browser console for error details

### Issue: Low OCR accuracy
**Solution:**
1. Upload higher resolution images (1500px+)
2. Ensure good lighting and contrast
3. Use original digital files when possible (Excel/PDF)

### Issue: No data extracted
**Solution:**
1. Check the "Extracted Data" section in preview
2. Use Edit mode to manually correct fields
3. Verify document format is supported

---

## Expected Results

### Before Enhancement:
- ❌ No way to save to specific modules
- ❌ Manual data entry required
- ❌ No database integration

### After Enhancement:
- ✅ "Designate & Save" button visible
- ✅ 3 destination options available
- ✅ Automatic data mapping
- ✅ Database integration working
- ✅ Success/error feedback
- ✅ Duplicate detection
- ✅ Update existing records

---

## Next Steps After Implementation

1. **Test with real documents:**
   - Employee lists (Excel)
   - Schedule images (JPG/PNG)
   - Birthday lists (CSV)

2. **Train users:**
   - Show how to upload files
   - Explain destination options
   - Demonstrate review/edit process

3. **Monitor accuracy:**
   - Track confidence scores
   - Note common errors
   - Adjust preprocessing if needed

4. **Optimize performance:**
   - Batch processing for multiple files
   - Background queue for large files
   - Email notifications

---

**Implementation Time:** 15-30 minutes  
**Difficulty:** Medium  
**Impact:** High - Enables automated data entry from scans
