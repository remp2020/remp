const mix = require("laravel-mix");
const publicPath = "www/assets/vendor/";

mix
    .webpackConfig({
        resolve: {
            symlinks: false,
        },
        watchOptions: {
            ignored: [ /node_modules([\\]+|\/)+(?!remp)/ ]
        }
    })
    .options({
        publicPath: publicPath,
        resourceRoot: "/assets/vendor/",
        postCss: [
            require('autoprefixer'),
        ],
    })
    .js("resources/js/app.js", "js/app.js")
    .sass("resources/sass/vendor.scss", "css/vendor.css")
    .sass("resources/sass/app.scss", "css/app.css")
    .extract()
    .version();
