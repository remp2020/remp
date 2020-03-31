let mix = require('laravel-mix').webpackConfig({
    resolve: {
        symlinks: false,
    },
    watchOptions: {
        ignored: [ /node_modules([\\]+|\/)+(?!remp)/ ]
    }
}).version();

mix
    .options({
        publicPath: "public/assets/vendor/",
        resourceRoot: "/assets/vendor/"
    })
    .js("resources/assets/js/app.js", "js/app.js")
    .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
    .sass("resources/assets/sass/app.scss", "css/app.css")
    .extract()
    .autoload({
        "jquery": ['$', 'jQuery'],
        "node-waves": ["Waves"],
        "autosize": ["autosize"],
        "moment": ["Moment"]
    });
