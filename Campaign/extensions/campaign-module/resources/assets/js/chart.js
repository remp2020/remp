global.Chart = require('chart.js/auto');
moment = require('moment');

/**
 * Extracted chartjs-adapter-moment library
 * https://github.com/chartjs/chartjs-adapter-moment
 */

const FORMATS = {
    datetime: 'MMM D, YYYY, h:mm:ss a',
    millisecond: 'h:mm:ss.SSS a',
    second: 'h:mm:ss a',
    minute: 'h:mm a',
    hour: 'hA',
    day: 'MMM D',
    week: 'll',
    month: 'MMM YYYY',
    quarter: '[Q]Q - YYYY',
    year: 'YYYY'
};


global.Chart._adapters._date.override(typeof moment === 'function' ? {
    _id: 'moment', // DEBUG ONLY

    formats: function() {
        return FORMATS;
    },

    parse: function(value, format) {
        if (typeof value === 'string' && typeof format === 'string') {
            value = moment(value, format);
        } else if (!(value instanceof moment)) {
            value = moment(value);
        }
        return value.isValid() ? value.valueOf() : null;
    },

    format: function(time, format) {
        return moment(time).format(format);
    },

    add: function(time, amount, unit) {
        return moment(time).add(amount, unit).valueOf();
    },

    diff: function(max, min, unit) {
        return moment(max).diff(moment(min), unit);
    },

    startOf: function(time, unit, weekday) {
        time = moment(time);
        if (unit === 'isoWeek') {
            weekday = Math.trunc(Math.min(Math.max(0, weekday), 6));
            return time.isoWeekday(weekday).startOf('day').valueOf();
        }
        return time.startOf(unit).valueOf();
    },

    endOf: function(time, unit) {
        return moment(time).endOf(unit).valueOf();
    }
} : {});

global.Chart.mailerTooltip = function(context) {
    // Tooltip Element
    let tooltipEl = document.getElementById('chartjs-tooltip');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'chartjs-tooltip';
        tooltipEl.innerHTML = '<table></table>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
    }

    // Set caret Position
    tooltipEl.classList.remove('above', 'below', 'no-transform');
    if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }

    function getBody(bodyItem) {
        return bodyItem.lines;
    }

    // Set Text
    if (tooltipModel.body) {
        const titleLines = tooltipModel.title || [];
        const bodyLines = tooltipModel.body.map(getBody);

        let innerHtml = '<thead>';

        titleLines.forEach(function(title) {
            innerHtml += '<tr><th>' + title + '</th></tr>';
        });
        innerHtml += '</thead><tbody>';

        bodyLines.forEach(function(body, i) {
            const colors = tooltipModel.labelColors[i];
            let style = 'background:' + colors.backgroundColor;
            style += '; border-color:' + colors.borderColor;
            style += '; border-width: 2px';
            const span = '<span style="' + style + '">' + body + '</span>';
            innerHtml += '<tr><td>' + span + '</td></tr>';
        });
        innerHtml += '</tbody>';

        let tableRoot = tooltipEl.querySelector('table');
        tableRoot.innerHTML = innerHtml;
        tableRoot.style.backgroundColor = 'rgba(0,0,0,0.9)';
        tableRoot.style.color = '#fff';
        tableRoot.style.margin = '10px';
        tableRoot.style.padding = '10px';
    }

    const position = context.chart.canvas.getBoundingClientRect();

    // Display, position, and set styles for font
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = position.left + window.scrollX + tooltipModel.caretX + 'px';
    tooltipEl.style.top = position.top + window.scrollY + tooltipModel.caretY + 'px';
    tooltipEl.style.padding = tooltipModel.padding + 'px';
    tooltipEl.style.pointerEvents = 'none';
    tooltipEl.style.borderRadius = '15px';
    tooltipEl.style.backgroundColor = 'rgba(0,0,0,0.9)';
    tooltipEl.style.fontSize = tooltipModel.bodyFontSize + 'px';
}