const global = require('global');

import Vue from 'vue';
global.Vue = Vue;

global.$ = global.jQuery = require('jquery');
global.moment = require('moment');
global.clipboard = require("./clipboard.js");
global.salvattore = require("salvattore");
global.SmartRangeSelector = require("@remp/js-commons/js/components/SmartRangeSelector.vue").default;
global.Chart = require("chart.js");
global.Waves = require("node-waves");
global.Nette = require('nette-forms');
global.autosize = require('autosize');

global.CodeMirror = require('codemirror');
require('codemirror/addon/selection/active-line');
require('codemirror/addon/fold/xml-fold');
require('codemirror/addon/edit/matchbrackets');
require('codemirror/addon/edit/closebrackets');
require('codemirror/addon/edit/matchtags');
require('codemirror/addon/edit/closetag');
require('codemirror/addon/lint/lint');
require('codemirror/addon/dialog/dialog');
require('codemirror/addon/search/search');
require('codemirror/addon/search/searchcursor');
require('codemirror/addon/search/jump-to-line');
require('codemirror/mode/xml/xml');
require('codemirror/mode/javascript/javascript');
require('codemirror/mode/css/css');
require('codemirror/mode/htmlmixed/htmlmixed.js');

require('nette.ajax.js');
Nette.initOnLoad();

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

require('datatables.net-buttons/js/dataTables.buttons.min');
require('datatables.net-buttons/js/buttons.colVis.min');
require('datatables.net-buttons/js/buttons.flash.min');
require('datatables.net-buttons/js/buttons.html5.min');
require('datatables.net-buttons/js/buttons.print.min');

require('eonasdan-bootstrap-datetimepicker');
require('easy-pie-chart/dist/jquery.easypiechart.js');
require('jquery-placeholder');
