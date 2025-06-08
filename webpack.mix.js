let mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

mix.browserSync({
    proxy: 'xxxx',
});

mix
.setPublicPath("dist")
.js("assets/js/app.js","js")
// .copy("assets/image","dist/image")
// .copy("assets/fonts","dist/fonts")
.sass("./assets/scss/app.scss", "/css")
.options({
    processCssUrls: false,
    postCss: [ tailwindcss('./tailwind.config.js') ],
});
