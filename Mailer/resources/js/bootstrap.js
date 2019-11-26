var global = require('global');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

require('datatables.net-buttons/js/dataTables.buttons.min');
require('datatables.net-buttons/js/buttons.colVis.min');
require('datatables.net-buttons/js/buttons.flash.min');
require('datatables.net-buttons/js/buttons.html5.min');
require('datatables.net-buttons/js/buttons.print.min');

global.$ = global.jQuery = require('jquery');

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');

require('eonasdan-bootstrap-datetimepicker');
require('easy-pie-chart/dist/jquery.easypiechart.js');
require('jquery-placeholder');

global.autosize = require('autosize');

global.CodeMirror = require('codemirror');
require('codemirror/mode/htmlmixed/htmlmixed.js');

global.Vue = require('vue');

global.moment = require('moment');

global.clipboard = require("./clipboard.js");

global.salvattore = require("salvattore");

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue").default;

global.Chart = require("chart.js");

global.Waves = require("node-waves");

global.Nette = require('nette-forms');
require('nette.ajax.js');
Nette.initOnLoad();
