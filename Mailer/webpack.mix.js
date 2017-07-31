let mix = require("laravel-mix");
let publicPath = "www/assets/vendor/";

mix.options({
        publicPath: publicPath,
        resourceRoot: "/assets/vendor/"
    })
    .copy("node_modules/ckeditor/adapters", publicPath + "js/ckeditor/adapters")
    .copy("node_modules/ckeditor/lang", publicPath + "js/ckeditor/lang")
    .copy("node_modules/ckeditor/plugins", publicPath + "js/ckeditor/plugins")
    .copy("node_modules/ckeditor/skins", publicPath + "js/ckeditor/skins")
    .copy([
        "node_modules/ckeditor/styles.js",
        "node_modules/ckeditor/contents.css"
    ], publicPath + "js/ckeditor")
    .js([
        "resources/js/functions.js",
        "resources/js/actions.js",
        "resources/js/datatables.js",
        "resources/js/charts.js"
    ], 'js')
    .js("resources/js/init.js", "js/init.js")
    .sass("resources/sass/vendor.scss", "css/vendor.css")
    .sass("resources/sass/app.scss", "css/app.css")
    .extract([
        "animate.css",
        "autosize",
        "bootstrap",
        "bootstrap-select",
        "ckeditor",
        "datatables.net",
        "datatables.net-rowgroup",
        "google-material-color",
        "jquery-placeholder",
        "malihu-custom-scrollbar-plugin",
        "node-waves",
        "easy-pie-chart/dist/jquery.easypiechart.js",
        "bootstrap-notify",
        "eonasdan-bootstrap-datetimepicker"
    ])
    .autoload({
        "jquery": ['$', 'jQuery', "window.jQuery"],
        "node-waves": ["Waves", "window.Waves"],
        "autosize": ["autosize", "window.autosize"]
    })
    .version();