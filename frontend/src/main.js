import { createApp } from 'vue'
import './style.css'
import './global-design-system.css'
import App from './App.vue'
import ElementPlus from 'element-plus'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
// import { library } from '@fortawesome/fontawesome-svg-core'
// import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
// import { faUserDoctor, faStethoscope, faPills, faHospitalAlt } from '@fortawesome/free-solid-svg-icons'
import { createPinia } from 'pinia'
import router from './router'

const app = createApp(App)

// Register Element Plus Icons
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

// Add icons to the library
//library.add(faUserDoctor, faStethoscope, faPills)

// Register Font Awesome component
//app.component('font-awesome-icon', FontAwesomeIcon)

app.use(ElementPlus)
app.use(createPinia())
app.use(router)

app.mount('#app')
