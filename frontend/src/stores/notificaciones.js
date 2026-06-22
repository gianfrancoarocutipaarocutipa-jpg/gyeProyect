import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificacionesStore = defineStore('notificaciones', () => {
  // State: list of notifications
  const notifications = ref([])

  // Actions
  /**
   * Add a new notification
   * @param {Object} notification - { type, message, timeout? }
   *   type: 'success' | 'error' | 'warning' | 'info'
   *   message: string
   *   timeout: optional milliseconds after which to auto-remove (default 5000)
   */
  function addNotification({ type, message, timeout = 5000 }) {
    const id = Date.now() + Math.random()
    const notification = {
      id,
      type,
      message,
      timeout
    }
    notifications.value.push(notification)

    // Auto-remove after timeout
    setTimeout(() => {
      removeNotification(id)
    }, timeout)

    return id
  }

  /**
   * Remove a notification by id
   * @param {String|Number} id
   */
  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  /**
   * Clear all notifications
   */
  function clearNotifications() {
    notifications.value = []
  }

  // Getters
  const getNotifications = () => notifications.value

  return {
    notifications,
    addNotification,
    removeNotification,
    clearNotifications,
    getNotifications
  }
})