let publicPath = "public/assets/vendor/";
let mix = require('laravel-mix').webpackConfig({
    watchOptions: {
        ignored: /node_modules/,
    }
}).options({
    publicPath: publicPath,
    resourceRoot: "/assets/vendor/"
}).version();

if (process.env.REMP_TARGET === 'lib') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .js("resources/assets/js/remplib.js", "js/remplib.js")

} else {
    mix
        .js("resources/assets/js/app.js", "js/app.js")
        .js("resources/assets/js/remplib.js", "js/remplib.js")
        .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
        .sass("resources/assets/sass/app.scss", "css/app.css")
        .extract([
            "./resources/assets/js/bootstrap.js",
            "animate.css",
            "autosize",
            "bootstrap",
            "bootstrap-select",
            "datatables.net",
            "datatables.net-rowgroup",
            "google-material-color",
            "jquery",
            "jquery-placeholder",
            "malihu-custom-scrollbar-plugin",
            "node-waves",
            "easy-pie-chart/dist/jquery.easypiechart.js",
            "bootstrap-notify",
            "eonasdan-bootstrap-datetimepicker",
            "vue"
        ])
        .autoload({
            "jquery": ['$', 'jQuery', "window.jQuery"],
            "node-waves": ["Waves", "window.Waves"],
            "autosize": ["autosize", "window.autosize"],
            "vue": ["vue", "window.vue"]
        });
}
