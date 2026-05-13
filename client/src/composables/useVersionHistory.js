/**
 * useVersionHistory
 * Central composable for tracking add/update/delete actions across all modules.
 * Persists to the audit_logs table via auth.addLog().
 */
import { useAuthStore } from '@/stores/auth'

// Module → DB table mapping
const MODULE_TABLE = {
    Employee: 'employees',
    Leave: 'leave_records',
    Schedule: 'schedules',
    Training: 'trainings',
    DTR: 'dtr_records',
    Signatory: 'signatories',
    Tracking: 'document_tracking',
    'T.O.': 'travel_orders',
}

export function useVersionHistory() {
    const auth = useAuthStore()

    /**
     * Track a CREATE action.
     * @param {string} module  - e.g. 'Employee', 'Leave', 'Payroll'
     * @param {object} record  - the newly created record (must have .id)
     * @param {string} label   - human-readable record label e.g. "Dela Cruz, Juan"
     */
    function trackCreate(module, record, label) {
        auth.addLog(
            `${module} Added`,
            module,
            `${label} was added.`,
            {
                actionType: 'CREATE',
                table: MODULE_TABLE[module] ?? module.toLowerCase(),
                recordId: record?.id ?? null,
                newValues: record,
            }
        )
    }

    /**
     * Track an UPDATE action.
     * @param {string} module     - module name
     * @param {object} oldRecord  - snapshot before the change
     * @param {object} newRecord  - snapshot after the change
     * @param {string} label      - human-readable record label
     */
    function trackUpdate(module, oldRecord, newRecord, label) {
        auth.addLog(
            `${module} Updated`,
            module,
            `${label} was updated.`,
            {
                actionType: 'UPDATE',
                table: MODULE_TABLE[module] ?? module.toLowerCase(),
                recordId: newRecord?.id ?? oldRecord?.id ?? null,
                oldValues: oldRecord,
                newValues: newRecord,
            }
        )
    }

    /**
     * Track a DELETE action.
     * @param {string} module  - module name
     * @param {object} record  - the record being deleted
     * @param {string} label   - human-readable record label
     */
    function trackDelete(module, record, label) {
        auth.addLog(
            `${module} Deleted`,
            module,
            `${label} was deleted.`,
            {
                actionType: 'DELETE',
                table: MODULE_TABLE[module] ?? module.toLowerCase(),
                recordId: record?.id ?? null,
                oldValues: record,
            }
        )
    }

    return { trackCreate, trackUpdate, trackDelete }
}
