import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import AppSelect from './components/AppSelect.vue'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.component('AppSelect', AppSelect)
app.mount('#app')
