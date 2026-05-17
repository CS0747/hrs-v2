import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

export function useLiveNotifications(options = {}) {
    const auth = useAuthStore()
    const notifications = useNotificationStore()

    // Configuration
    const config = {
        pollInterval: options.pollInterval || 5000, // 5 seconds for real-time feel
        showToasts: options.showToasts !== false, // Show toast notifications by default
        autoMarkRead: options.autoMarkRead || false, // Auto mark as read when viewed
    }

    const pollingInterval = ref(null)
    const lastNotificationId = ref(0)
    const unreadCount = ref(0)
    const allNotifications = ref([])
    const isLoading = ref(false)

    // Fetch all notifications
    async function fetchNotifications(unreadOnly = false) {
        if (!auth.currentUser) return

        isLoading.value = true
        try {
            const url = `http://localhost/hrs-v2/server/api/notifications.php?action=list${unreadOnly ? '&unread_only=true' : ''}`
            const res = await auth.apiFetch(url)
            const data = await res.json()

            if (Array.isArray(data.notifications)) {
                allNotifications.value = data.notifications

                // Update last notification ID
                if (data.notifications.length > 0) {
                    lastNotificationId.value = Math.max(...data.notifications.map(n => n.id))
                }
            }
        } catch (e) {
            console.error('Failed to fetch notifications:', e)
        } finally {
            isLoading.value = false
        }
    }

    // Poll for new notifications (real-time)
    async function pollNewNotifications() {
        if (!auth.currentUser) return

        try {
            const res = await auth.apiFetch(
                `http://localhost/hrs-v2/server/api/notifications.php?action=poll&last_id=${lastNotificationId.value}`
            )
            const data = await res.json()

            if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                // Add new notifications to the list
                allNotifications.value = [...data.notifications, ...allNotifications.value]

                // Update last notification ID
                lastNotificationId.value = Math.max(...data.notifications.map(n => n.id))

                // Show toast notifications for new items
                if (config.showToasts) {
                    data.notifications.forEach(notif => {
                        const toastType = getToastType(notif.type)
                        notifications.add({
                            type: toastType,
                            title: notif.title,
                            message: notif.message,
                            duration: 6000,
                        })
                    })
                }

                // Update unread count
                await fetchUnreadCount()
            }
        } catch (e) {
            console.error('Failed to poll notifications:', e)
        }
    }

    // Get unread count
    async function fetchUnreadCount() {
        if (!auth.currentUser) return

        try {
            const res = await auth.apiFetch('http://localhost/hrs-v2/server/api/notifications.php?action=count_unread')
            const data = await res.json()
            unreadCount.value = data.count || 0
        } catch (e) {
            console.error('Failed to fetch unread count:', e)
        }
    }

    // Mark notification as read
    async function markAsRead(notificationId) {
        try {
            await auth.apiFetch('http://localhost/hrs-v2/server/api/notifications.php?action=mark_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })

            // Update local state
            const notif = allNotifications.value.find(n => n.id === notificationId)
            if (notif) {
                notif.is_read = 1
                notif.read_at = new Date().toISOString()
            }

            await fetchUnreadCount()
        } catch (e) {
            console.error('Failed to mark notification as read:', e)
        }
    }

    // Mark all as read
    async function markAllAsRead() {
        try {
            await auth.apiFetch('http://localhost/hrs-v2/server/api/notifications.php?action=mark_all_read', {
                method: 'POST',
            })

            // Update local state
            allNotifications.value.forEach(notif => {
                notif.is_read = 1
                notif.read_at = new Date().toISOString()
            })

            unreadCount.value = 0
        } catch (e) {
            console.error('Failed to mark all as read:', e)
        }
    }

    // Delete notification
    async function deleteNotification(notificationId) {
        try {
            await auth.apiFetch(`http://localhost/hrs-v2/server/api/notifications.php?action=delete&id=${notificationId}`, {
                method: 'DELETE',
            })

            // Remove from local state
            allNotifications.value = allNotifications.value.filter(n => n.id !== notificationId)

            await fetchUnreadCount()
        } catch (e) {
            console.error('Failed to delete notification:', e)
        }
    }

    // Clear all notifications
    async function clearAll() {
        try {
            await auth.apiFetch('http://localhost/hrs-v2/server/api/notifications.php?action=clear_all', {
                method: 'DELETE',
            })

            allNotifications.value = []
            unreadCount.value = 0
        } catch (e) {
            console.error('Failed to clear notifications:', e)
        }
    }

    // Helper to get toast type from notification type
    function getToastType(notificationType) {
        const typeMap = {
            password_reset: 'info',
            leave_request: 'warning',
            travel_order: 'warning',
            employee_added: 'success',
            training_added: 'info',
            audit_log: 'info',
        }
        return typeMap[notificationType] || 'info'
    }

    // Computed properties
    const unreadNotifications = computed(() =>
        allNotifications.value.filter(n => n.is_read === 0)
    )

    const readNotifications = computed(() =>
        allNotifications.value.filter(n => n.is_read === 1)
    )

    // Start polling
    function startPolling() {
        if (pollingInterval.value) return // Already polling

        // Initial fetch
        fetchNotifications()
        fetchUnreadCount()

        // Poll for new notifications at configured interval
        pollingInterval.value = setInterval(() => {
            pollNewNotifications()
        }, config.pollInterval)
    }

    // Stop polling
    function stopPolling() {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value)
            pollingInterval.value = null
        }
    }

    // Auto-start on mount, auto-stop on unmount
    onMounted(() => {
        if (auth.currentUser) {
            startPolling()
        }
    })

    onUnmounted(() => {
        stopPolling()
    })

    return {
        // State
        allNotifications,
        unreadNotifications,
        readNotifications,
        unreadCount,
        isLoading,

        // Methods
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        clearAll,
        startPolling,
        stopPolling,
        pollNewNotifications,
    }
}
