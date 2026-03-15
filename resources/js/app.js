import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import './bootstrap';
import { createApp, h } from 'vue';
import { RouterView } from 'vue-router';
import router from './router';
import './styles/theme.css';

const app = createApp({
    render: () => h(RouterView),
});

app.use(router);

router.isReady().then(() => {
    app.mount('#app');
});