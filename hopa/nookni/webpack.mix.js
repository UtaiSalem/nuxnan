const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/libs.js', 'public/js/libs.min.js')
    .postCss('resources/css/app.css', 'public/css/app.css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    .sass('resources/sass/libs.scss','public/css/libs.min.css')
    .options({
        processCssUrls: false
    });

// Copy vendor assets that don't need compilation
mix.copyDirectory('node_modules/apexcharts/dist/apexcharts.min.js', 'public/js/apexcharts.min.js')
    .copyDirectory('node_modules/swiper/swiper-bundle.min.css', 'public/css/swiper-bundle.min.css')
    .copyDirectory('node_modules/swiper/swiper-bundle.min.js', 'public/js/swiper-bundle.min.js')
    .copyDirectory('node_modules/nouislider/dist/nouislider.min.css', 'public/css/nouislider.min.css')
    .copyDirectory('node_modules/nouislider/dist/nouislider.min.js', 'public/js/nouislider.min.js')
    .copyDirectory('node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'public/css/dataTables.bootstrap5.min.css')
    .copyDirectory('node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js', 'public/js/dataTables.bootstrap5.min.js')
    .copyDirectory('node_modules/sweetalert2/dist/sweetalert2.min.css', 'public/css/sweetalert2.min.css')
    .copyDirectory('node_modules/sweetalert2/dist/sweetalert2.min.js', 'public/js/sweetalert2.min.js');