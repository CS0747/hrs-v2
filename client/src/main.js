import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import AppSelect from './components/AppSelect.vue'
import { createAuthFetch } from './utils/api'
import { usePermissions } from './composables/usePermissions'

// Initialize fetch interceptor to add X-User-Id header to all API requests
createAuthFetch()

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.component('AppSelect', AppSelect)

// Preload permissions after pinia is initialized
const { loadPermissions } = usePermissions()
loadPermissions().catch(err => console.warn('Failed to preload permissions:', err))

app.mount('#app')
