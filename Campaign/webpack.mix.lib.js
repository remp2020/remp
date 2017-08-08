let mix = require('laravel-mix');
let publicPath = "public/assets/showtime/";

// we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js

mix
    .webpackConfig({
        watchOptions: {
            ignored: /node_modules/,
        }
    })
    .options({
        publicPath: publicPath,
        resourceRoot: "/assets/showtime/"
    })
    .js([
        "resources/assets/js/banner.js",
    ], "js/banner.js")
    .version();