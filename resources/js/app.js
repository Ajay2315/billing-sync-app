import './bootstrap';

import Swal from 'sweetalert2/dist/sweetalert2.js';
import 'sweetalert2/dist/sweetalert2.css';
import { createApp } from "vue";
import router from '../router';
import App from '../components/App.vue';
//import class user
import { Buffer } from 'buffer'

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
})

window.toast = toast
window.Swal = Swal
window.Buffer = Buffer

createApp(App).use(router).mount('#app')

