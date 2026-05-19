/**
 * Legend Store
 * Manages shift legends for color-coded schedule display
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/utils/api'

export const useLegendStore = defineStore('legend', () => {
    // State
    const legends = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Computed: Group legends by department
    const departmentLegends = computed(() => {
        const grouped = {}

        legends.value.forEach(legend => {
            const dept = legend.department || 'Standard'
            if (!grouped[dept]) {
                grouped[dept] = []
            }
            grouped[dept].push(legend)
        })

        // Sort by display order within each department
        Object.keys(grouped).forEach(dept => {
            grouped[dept].sort((a, b) => a.displayOrder - b.displayOrder)
        })

        return grouped
    })

    // Computed: Standard legends (department = null)
    const standardLegends = computed(() => {
        return legends.value
            .filter(l => l.department === null)
            .sort((a, b) => a.displayOrder - b.displayOrder)
    })

    // Actions

    /**
     * Fetch all shift legends or department-specific legends
     * @param {string|null} department - Department name or null for all
     */
    async function fetchLegends(department = null) {
        loading.value = true
        error.value = null

        try {
            const url = department
                ? `/shift_legends.php?department=${encodeURIComponent(department)}`
                : '/shift_legends.php'

            const response = await api.get(url)
            legends.value = response
            return response
        } catch (err) {
            error.value = err.message || 'Failed to fetch legends'
            console.error('Error fetching legends:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Get legend for a specific shift code and department
     * @param {string} shiftCode - Shift code (62, 210, 106, etc.)
     * @param {string|null} department - Department name
     * @returns {object|null} Legend object or null
     */
    function getLegendForShift(shiftCode, department = null) {
        // First try to find department-specific legend
        if (department) {
            const deptLegend = legends.value.find(
                l => l.code === shiftCode && l.department === department
            )
            if (deptLegend) return deptLegend
        }

        // Fall back to standard legend (department = null)
        const standardLegend = legends.value.find(
            l => l.code === shiftCode && l.department === null
        )

        return standardLegend || null
    }

    /**
     * Get color for a specific shift code and department
     * @param {string} shiftCode - Shift code
     * @param {string|null} department - Department name
     * @returns {object} { primary: string, secondary: string|null }
     */
    function getColorForShift(shiftCode, department = null) {
        const legend = getLegendForShift(shiftCode, department)

        if (!legend) {
            // Default color if legend not found
            return { primary: '#000000', secondary: null }
        }

        return {
            primary: legend.colorPrimary,
            secondary: legend.colorSecondary
        }
    }

    /**
     * Get all legends for a specific department (including standard)
     * @param {string} department - Department name
     * @returns {array} Array of legend objects
     */
    function getLegendsForDepartment(department) {
        const deptLegends = legends.value.filter(l => l.department === department)
        const stdLegends = standardLegends.value

        // If department has specific legends, return those
        // Otherwise return standard legends
        return deptLegends.length > 0 ? deptLegends : stdLegends
    }

    /**
     * Add a new shift legend (Admin only)
     * @param {object} legendData - Legend data
     */
    async function addLegend(legendData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/shift_legends.php', legendData)

            // Refresh legends after adding
            await fetchLegends()

            return response
        } catch (err) {
            error.value = err.message || 'Failed to add legend'
            console.error('Error adding legend:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Update an existing shift legend (Admin only)
     * @param {number} id - Legend ID
     * @param {object} legendData - Updated legend data
     */
    async function updateLegend(id, legendData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.put(`/shift_legends.php?id=${id}`, legendData)

            // Refresh legends after updating
            await fetchLegends()

            return response
        } catch (err) {
            error.value = err.message || 'Failed to update legend'
            console.error('Error updating legend:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Deactivate a shift legend (Admin only)
     * @param {number} id - Legend ID
     */
    async function deleteLegend(id) {
        loading.value = true
        error.value = null

        try {
            const response = await api.delete(`/shift_legends.php?id=${id}`)

            // Refresh legends after deleting
            await fetchLegends()

            return response
        } catch (err) {
            error.value = err.message || 'Failed to delete legend'
            console.error('Error deleting legend:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Check if a shift code has split colors (multi-color)
     * @param {string} shiftCode - Shift code
     * @param {string|null} department - Department name
     * @returns {boolean}
     */
    function hasMultiColor(shiftCode, department = null) {
        const legend = getLegendForShift(shiftCode, department)
        return legend ? legend.colorSecondary !== null : false
    }

    /**
     * Format shift code with colors for display
     * @param {string} shiftCode - Shift code
     * @param {string|null} department - Department name
     * @returns {object} { code, colors: [primary, secondary?], timeRange }
     */
    function formatShiftDisplay(shiftCode, department = null) {
        const legend = getLegendForShift(shiftCode, department)

        if (!legend) {
            return {
                code: shiftCode,
                colors: ['#000000'],
                timeRange: 'Unknown'
            }
        }

        const colors = [legend.colorPrimary]
        if (legend.colorSecondary) {
            colors.push(legend.colorSecondary)
        }

        return {
            code: legend.code,
            colors,
            timeRange: legend.timeRange
        }
    }

    return {
        // State
        legends,
        loading,
        error,

        // Computed
        departmentLegends,
        standardLegends,

        // Actions
        fetchLegends,
        getLegendForShift,
        getColorForShift,
        getLegendsForDepartment,
        addLegend,
        updateLegend,
        deleteLegend,
        hasMultiColor,
        formatShiftDisplay
    }
})
