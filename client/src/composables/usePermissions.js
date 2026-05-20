import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { API_ENDPOINTS } from '@/config/api'


const PERM_API = API_ENDPOINTS.MODULE_PERMISSIONS

// Global permissions cache
const permissionsCache = ref(null)
const permissionsLoading = ref(false)

export function usePermissions() {
    const auth = useAuthStore()

    // Load all permissions from backend
    async function loadPermissions() {
        if (permissionsCache.value) return permissionsCache.value
        if (permissionsLoading.value) {
            // Wait for existing load to complete
            await new Promise(resolve => {
                const check = setInterval(() => {
                    if (!permissionsLoading.value) {
                        clearInterval(check)
                        resolve()
                    }
                }, 50)
            })
            return permissionsCache.value
        }

        permissionsLoading.value = true
        try {
            const res = await auth.apiFetch(PERM_API)
            const data = await res.json()
            permissionsCache.value = data.permissions || {}
            return permissionsCache.value
        } catch (e) {
            console.error('Failed to load permissions:', e)
            permissionsCache.value = {}
            return {}
        } finally {
            permissionsLoading.value = false
        }
    }

    // Check if current user has permission for a module/action
    function hasPermission(module, action) {
        const role = auth.userRole

        // DIOS has all permissions
        if (role === 'DIOS') return true

        if (!permissionsCache.value) return false

        // Check permission in cache
        const modulePerms = permissionsCache.value[module]
        if (!modulePerms) return false

        const rolePerms = modulePerms[role]
        if (!rolePerms) return false

        const granted = rolePerms[action]
        if (granted === undefined) return false

        return granted === true
    }

    // Computed helper for common checks
    const can = computed(() => ({
        view: (module) => hasPermission(module, 'View'),
        add: (module) => hasPermission(module, 'Add'),
        edit: (module) => hasPermission(module, 'Edit'),
        delete: (module) => hasPermission(module, 'Delete'),
        export: (module) => hasPermission(module, 'Export'),
        approve: (module) => hasPermission(module, 'Approve'),
        verify: (module) => hasPermission(module, 'Verify'),
    }))

    return {
        loadPermissions,
        hasPermission,
        can,
        permissionsCache,
    }
}
