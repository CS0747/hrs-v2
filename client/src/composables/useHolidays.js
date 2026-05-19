/**
 * Holidays Composable
 * Manages Philippine holidays
 */

import { ref, computed } from 'vue'
import api from '@/utils/api'

export function useHolidays() {
    const holidays = ref([])
    const loading = ref(false)
    const error = ref(null)

    /**
     * Fetch holidays for a specific year and optional month
     * @param {number} year - Year to fetch holidays for
     * @param {number|null} month - Optional month (1-12)
     */
    async function fetchHolidays(year, month = null) {
        loading.value = true
        error.value = null

        try {
            const url = month
                ? `/holidays.php?year=${year}&month=${month}`
                : `/holidays.php?year=${year}`

            const response = await api.get(url)
            holidays.value = response
            return response
        } catch (err) {
            error.value = err.message || 'Failed to fetch holidays'
            console.error('Error fetching holidays:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Check if a date is a holiday
     * @param {Date|string} date - Date to check
     * @returns {object|null} Holiday object or null
     */
    function isHoliday(date) {
        const dateStr = typeof date === 'string' ? date : toDateString(date)
        return holidays.value.find(h => h.date === dateStr) || null
    }

    /**
     * Get holiday name for a date
     * @param {Date|string} date - Date to check
     * @returns {string|null} Holiday name or null
     */
    function getHolidayName(date) {
        const holiday = isHoliday(date)
        return holiday ? holiday.name : null
    }

    /**
     * Convert Date to YYYY-MM-DD string
     * @param {Date} date
     * @returns {string}
     */
    function toDateString(date) {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
    }

    /**
     * Get holidays map by date for quick lookup
     */
    const holidaysMap = computed(() => {
        const map = {}
        holidays.value.forEach(holiday => {
            map[holiday.date] = holiday
        })
        return map
    })

    return {
        holidays,
        loading,
        error,
        fetchHolidays,
        isHoliday,
        getHolidayName,
        holidaysMap
    }
}
