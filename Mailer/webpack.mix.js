let mix = require("laravel-mix");
let publicPath = "www/assets/vendor/";

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
    .js("resources/js/app.js", "js/app.js")
    .sass("resources/sass/vendor.scss", "css/vendor.css")
    .sass("resources/sass/app.scss", "css/app.css")
    .extract([
        "./resources/js/bootstrap.js",
        "jquery",
        "bootstrap",
        "nette-forms",
        "nette.ajax.js",
        "animate.css",
        "autosize",
        "bootstrap-select",
        "datatables.net",
        "datatables.net-rowgroup",
        "google-material-color",
        "jquery-placeholder",
        "malihu-custom-scrollbar-plugin",
        "moment",
        "node-waves",
        "easy-pie-chart/dist/jquery.easypiechart.js",
        "bootstrap-notify",
        "eonasdan-bootstrap-datetimepicker",
        "codemirror",
        "codemirror/mode/htmlmixed/htmlmixed.js",
        "vue",
        "salvattore/dist/salvattore.js"
    ])
    .autoload({
        "jquery": ['$', 'jQuery', "window.jQuery"],
        "node-waves": ["Waves", "window.Waves"],
        "autosize": ["autosize", "window.autosize"],
        "vue": ["Vue", "window.vue"],
        "moment": ["Moment", "window.Moment"]
    })
    .version();
