import { createRouter, createWebHistory } from 'vue-router';

import index from '../components/dashboard/index.vue';
import notFound from '../components/notFound.vue';
const routes = [
    { path: '/', component: index, name: 'index', },

    {
        path: '/:pathMatch(.*)*',
        component: notFound
    }
];

const router = createRouter({ 
    history: createWebHistory(process.env.BASE_URL),
    routes,
})

export default router;