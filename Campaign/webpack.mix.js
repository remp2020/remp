let mix = require('laravel-mix').webpackConfig({
    watchOptions: {
        ignored: /node_modules/,
    }
}).version();

if (process.env.REMP_TARGET === 'lib') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .options({
            publicPath: "public/assets/lib/",
            resourceRoot: "/assets/lib/"
        })
        .js("resources/assets/js/banner.js", "js/banner.js")
        .js("resources/assets/js/remplib.js", "js/remplib.js")
} else {
    const publicPath = "public/assets/vendor/";
    mix
        .options({
            publicPath: publicPath,
            resourceRoot: "/assets/vendor/"
        })
        .js("resources/assets/js/app.js", "js/app.js")
        .js("resources/assets/js/banner.js", "js/banner.js")
        .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
        .sass("resources/assets/sass/app.scss", "css/app.css")
        .copy('resources/assets/maxmind/GeoLite2-Country.mmdb', publicPath + 'maxmind/GeoLite2-Country.mmdb')
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
            "eonasdan-bootstrap-datetimepicker",
            "google-material-color",
            "malihu-custom-scrollbar-plugin",
            "moment",
            "node-waves",
            "bootstrap-notify",
            "./resources/assets/js/farbtastic.js",
        ])
        .autoload({
            "jquery": ['$', 'jQuery', "window.jQuery"],
            "node-waves": ["Waves", "window.Waves"],
            "autosize": ["autosize", "window.autosize"],
            "vue": ["Vue", "window.Vue"],
            "moment": ["Moment", "window.Moment"],
        });
}
