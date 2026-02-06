import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';

// Import Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap';

// Import Dreams EMR Design System
import '../css/dreams-emr.css';

// Import permission directives
import { canDirective, canAllDirective, roleDirective } from './directives/permission';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Register permission directives
app.directive('can', canDirective);
app.directive('can-all', canAllDirective);
app.directive('role', roleDirective);

app.mount('#app');
