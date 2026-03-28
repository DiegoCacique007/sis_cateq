import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Swal from 'sweetalert2';
import Alpine from 'alpinejs';

// 1. Importamos Anime.js directamente
import anime from 'animejs';

// 2. Registramos las librerías globalmente
window.Alpine = Alpine;
window.Swal = Swal;
window.anime = anime;

Alpine.start();
