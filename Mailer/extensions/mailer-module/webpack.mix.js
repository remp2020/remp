const mix = require("laravel-mix");

let mailerModulePath = process.env.MAILER_MODULE_PATH;

mix
    .webpackConfig({
        resolve: {
            symlinks: false,
        },
        watchOptions: {
            ignored: /node_modules([\\]+|\/)+(?!remp)/
        }
    })
    .options({
        publicPath: "www/assets/vendor",
        resourceRoot: "/assets/vendor/",
        postCss: [
            require('autoprefixer'),
        ],
    })
    .js(mailerModulePath + "/assets/js/app.js", "js/app.js")
    .vue()
    .sass(mailerModulePath + "/assets/sass/vendor.scss", "css/vendor.css")
    .sass(mailerModulePath + "/assets/sass/app.scss", "css/app.css")
    .copyDirectory(mailerModulePath + "/assets/img", "www/assets/vendor")
    .extract()
    .version();