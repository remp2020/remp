let mix = require('laravel-mix');
let publicPath = "public/assets/vendor/";

mix.options({
    publicPath: publicPath,
    resourceRoot: "/assets/vendor/"
})
    .js([
        "resources/assets/js/functions.js",
        "resources/assets/js/actions.js",
        "resources/assets/js/datatables.js",
        "resources/assets/js/remplib.js",
        "resources/assets/js/banner.js"
    ], "js/app.js")
    .js("resources/assets/js/banner.js", "js/banner.js")
    .js("resources/assets/js/remplib.js", "js/remplib.js")
    .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
    .sass("resources/assets/sass/app.scss", "css/app.css")
    .sass("resources/assets/sass/banner.scss", "css/banner.css")
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
        "vue": ["vue", "window.vue"]
    })
    .version();