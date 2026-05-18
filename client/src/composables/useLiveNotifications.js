import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { API_ENDPOINTS } from '@/config/api'

export function useLiveNotifications() {
    const auth = useAuthStore()
    const notifications = ref([])
    const loading = ref(false)
    const error = ref(null)
    let pollInterval = null

    const unreadCount = computed(() =>
        notifications.value.filter(n => !n.isRead).length
    )

    const unreadNotifications = computed(() =>
        notifications.value.filter(n => !n.isRead)
    )

    async function fetchNotifications() {
        if (!auth.isLoggedIn) return

        loading.value = true
        error.value = null

        try {
            const response = await auth.apiFetch(API_ENDPOINTS.NOTIFICATIONS)
            const data = await response.json()

            if (response.ok) {
                notifications.value = data.notifications.map(n => ({
                    id: n.id,
                    userId: n.user_id,
                    type: n.type,
                    title: n.title,
                    message: n.message,
                    referenceId: n.reference_id,
                    referenceType: n.reference_type,
                    link: n.link,
                    isRead: Boolean(n.is_read),
                    createdAt: n.created_at,
                    readAt: n.read_at
                }))
            }
        } catch (err) {
            error.value = err.message
            console.error('Failed to fetch notifications:', err)
        } finally {
            loading.value = false
        }
    }

    async function markAsRead(notificationId) {
        try {
            const response = await auth.apiFetch(
                `${API_ENDPOINTS.NOTIFICATIONS}?id=${notificationId}`,
                { method: 'PUT' }
            )

            if (response.ok) {
                const notification = notifications.value.find(n => n.id === notificationId)
                if (notification) {
                    notification.isRead = true
                    notification.readAt = new Date().toISOString()
                }
            }
        } catch (err) {
            console.error('Failed to mark notification as read:', err)
        }
    }

    async function markAllAsRead() {
        try {
            const response = await auth.apiFetch(
                `${API_ENDPOINTS.NOTIFICATIONS}?action=mark_all_read`,
                { method: 'PUT' }
            )

            if (response.ok) {
                notifications.value.forEach(n => {
                    n.isRead = true
                    n.readAt = new Date().toISOString()
                })
            }
        } catch (err) {
            console.error('Failed to mark all notifications as read:', err)
        }
    }

    async function deleteNotification(notificationId) {
        try {
            const response = await auth.apiFetch(
                `${API_ENDPOINTS.NOTIFICATIONS}?id=${notificationId}`,
                { method: 'DELETE' }
            )

            if (response.ok) {
                notifications.value = notifications.value.filter(n => n.id !== notificationId)
            }
        } catch (err) {
            console.error('Failed to delete notification:', err)
        }
    }

    function startPolling(intervalMs = 30000) {
        if (pollInterval) return

        fetchNotifications()
        pollInterval = setInterval(fetchNotifications, intervalMs)
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval)
            pollInterval = null
        }
    }

    onMounted(() => {
        if (auth.isLoggedIn) {
            startPolling()
        }
    })

    onUnmounted(() => {
        stopPolling()
    })

    return {
        notifications,
        unreadNotifications,
        allNotifications: notifications,
        unreadCount,
        loading,
        error,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        startPolling,
        stopPolling
    }
}
