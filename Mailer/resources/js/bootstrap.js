global.$ = global.jQuery = require('jquery');

global.Nette = require('nette-forms');

global.CodeMirror = require('codemirror');

global.Vue = require('vue');

global.moment = require('moment');

global.clipboard = require("./clipboard.js");

global.salvattore = require("salvattore");

global.ListStats = require('./components/ListStats.vue');

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue");

Nette.initOnLoad();
