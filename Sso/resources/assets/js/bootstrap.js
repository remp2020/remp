var global = require('global');

global.$ = global.jQuery = require('jquery');
global.moment = require('moment');
global.autosize = require('autosize');
global.Waves = require("node-waves");

global.$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

require('jquery-placeholder');
