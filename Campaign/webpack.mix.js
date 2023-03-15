const mix = require('laravel-mix')

mix.override((config) => {
    delete config.watchOptions;
}).webpackConfig({
    resolve: {
        symlinks: false,
    },
    stats: {
        children: true,
    }
}).version();

require('laravel-mix-polyfill');

if (process.env.REMP_TARGET === 'lib') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .options({
            publicPath: "public/assets/lib/",
            resourceRoot: "/assets/lib/",
            postCss: [
                require('autoprefixer'),
            ],
        })
        .js("resources/assets/js/banner.js", "js/banner.js")
        .js("resources/assets/js/remplib.js", "js/remplib.js")
        .js("resources/assets/js/bannerSelector.js", "js/bannerSelector.js")
        .vue()
        .polyfill({
            enabled: true,
            useBuiltIns: "usage",
            targets: {"ie": 11},
            debug: false,
        });
} else {
    mix
        .options({
            publicPath: "public/assets/vendor/",
            resourceRoot: "/assets/vendor/",
            postCss: [
                require('autoprefixer'),
            ],
        })
        .js("resources/assets/js/app.js", "js/app.js")
        .js("resources/assets/js/banner.js", "js/banner.js")
        .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
        .sass("resources/assets/sass/app.scss", "css/app.css")
        .less("node_modules/bootstrap-sweetalert/lib/sweet-alert-combine.less", "css/app.css")
        .vue()
        .extract();
}
