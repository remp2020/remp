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
      resourceRoot: "/vendor/remp/campaign/assets/lib/",
      postCss: [
        require('autoprefixer'),
      ],
    })
    .js("vendor/remp/campaign-module/resources/assets/js/banner.js", "js/banner.js")
    .js("vendor/remp/campaign-module/resources/assets/js/remplib.js", "js/remplib.js")
    .js("vendor/remp/campaign-module/resources/assets/js/bannerSelector.js", "js/bannerSelector.js")
    .js("vendor/remp/campaign-module/resources/assets/js/campaignDebug.js", "js/campaignDebug.js")
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
      publicPath: "public/vendor/campaign/",
      resourceRoot: "../",
      postCss: [
        require('autoprefixer'),
      ],
    })
    .js("vendor/remp/campaign-module/resources/assets/js/app.js", "js/app.js")
    .js("vendor/remp/campaign-module/resources/assets/js/banner.js", "js/banner.js")
    .sass("vendor/remp/campaign-module/resources/assets/sass/vendor.scss", "css/vendor.css")
    .sass("vendor/remp/campaign-module/resources/assets/sass/app.scss", "css/app.css")
    .less("node_modules/bootstrap-sweetalert/lib/sweet-alert-combine.less", "css/app.css")
    .vue()
    .extract();
}
