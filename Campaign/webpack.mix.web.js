let mix = require('laravel-mix');
let publicPath = "public/assets/vendor/";

// we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js

mix
    .webpackConfig({
        watchOptions: {
            ignored: /node_modules/,
        }
    })
    .options({
        publicPath: publicPath,
        resourceRoot: "/assets/vendor/"
    })
    .js("resources/assets/js/app.js", "js/app.js")
    .js("resources/assets/js/banner.js", "js/banner.js")
    .copy("resources/assets/js/remplib.js", publicPath + "js/remplib.js")
    .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
    .sass("resources/assets/sass/app.scss", "css/app.css")
    .extract([
        "./resources/assets/js/bootstrap.js",
        "jquery",
        "jquery-placeholder",
        "bootstrap",
        "bootstrap-select",
        "vue",
        "animate.css",
        "autosize",
        "datatables.net",
        "datatables.net-rowgroup",
        "google-material-color",
        "malihu-custom-scrollbar-plugin",
        "node-waves",
        "bootstrap-notify",
        "./resources/assets/js/farbtastic.js",
    ])
    .autoload({
        "jquery": ['$', 'jQuery', "window.jQuery"],
        "node-waves": ["Waves", "window.Waves"],
        "autosize": ["autosize", "window.autosize"],
        "vue": ["Vue", "window.Vue"],
    })
    .version();