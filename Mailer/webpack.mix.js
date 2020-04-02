let mix = require("laravel-mix");
let publicPath = "www/assets/vendor/";

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
        resourceRoot: "/assets/vendor/"
    })
    .js("resources/js/app.js", "js/app.js")
    .sass("resources/sass/vendor.scss", "css/vendor.css")
    .sass("resources/sass/app.scss", "css/app.css")
    .extract()
    .autoload({
        "jquery": ['$', 'jQuery'],
        "node-waves": ["Waves"],
        "vue": ["Vue"],
        "moment": ["Moment"],
        "salvattore": ["salvattore"],
    })
    .version();
