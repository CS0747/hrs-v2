import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])

  function add(notification) {
    const id = Date.now() + Math.random()
    const newNotification = {
      id,
      type: notification.type || 'info', // success, error, warning, info
      title: notification.title || '',
      message: notification.message || '',
      duration: notification.duration !== undefined ? notification.duration : 4000,
      persistent: notification.persistent || false,
    }
    notifications.value.unshift(newNotification)

    // Auto-remove if not persistent
    if (!newNotification.persistent && newNotification.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, newNotification.duration)
    }

    return id
  }

  function remove(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  function success(message, title = 'Success', duration = 4000) {
    return add({ type: 'success', title, message, duration })
  }

  function error(message, title = 'Error', duration = 6000) {
    return add({ type: 'error', title, message, duration })
  }

  function warning(message, title = 'Warning', duration = 5000) {
    return add({ type: 'warning', title, message, duration })
  }

  function info(message, title = 'Info', duration = 4000) {
    return add({ type: 'info', title, message, duration })
  }

  function clear() {
    notifications.value = []
  }

  return {
    notifications,
    add,
    remove,
    success,
    error,
    warning,
    info,
    clear,
  }
})
