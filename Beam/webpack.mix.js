const mix = require('laravel-mix');

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

if (process.env.REMP_TARGET === 'iota') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .options({
            publicPath: "public/assets/iota/",
            resourceRoot: "/vendor/remp/beam/assets/iota/",
            postCss: [
                require('autoprefixer'),
            ],
        })
        .js("vendor/remp/beam-module/resources/assets/js/iota.js", "js/iota.js")
        .vue()
} else if (process.env.REMP_TARGET === 'lib') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .options({
            publicPath: "public/assets/lib/",
            resourceRoot: "/vendor/remp/beam/assets/lib/",
            postCss: [
                require('autoprefixer'),
            ],
        })
        .js("vendor/remp/beam-module/resources/assets/js/remplib.js", "js/remplib.js")
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
            publicPath: "public/vendor/beam",
            resourceRoot: "../",
            postCss: [
                require('autoprefixer'),
            ],
        })
        .js("vendor/remp/beam-module/resources/assets/js/app.js", "js/app.js")
        .js("vendor/remp/beam-module/resources/assets/js/remplib.js", "js/remplib.js")
        .sass("vendor/remp/beam-module/resources/assets/sass/vendor.scss", "css/vendor.css")
        .sass("vendor/remp/beam-module/resources/assets/sass/app.scss", "css/app.css")
        .vue()
        .extract();
}
