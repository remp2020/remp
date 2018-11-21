global.Chart = require('chart.js');

global.Chart.defaults.global.tooltips.enabled = false;
global.Chart.defaults.global.tooltips.custom = function (tooltipModel) {
    // Tooltip Element
    var tooltipEl = $('#chartjs-tooltip');

    // Create element on first render
    if (!tooltipEl.length) {
        tooltipEl = $(document.createElement('div'));
        tooltipEl.attr('id', 'chartjs-tooltip');
        tooltipEl.html("<table></table>");
        $('body').append(tooltipEl)
    }

    // Hide if no tooltip
    if (tooltipModel.opacity === 0) {
        tooltipEl.css('opacity', 0);
        return;
    }

    // Set caret Position
    tooltipEl.removeClass('above below no-transform');
    if (tooltipModel.yAlign) {
        tooltipEl.addClass(tooltipModel.yAlign);
    } else {
        tooltipEl.addClass('no-transform');
    }

    function getBody(bodyItem) {
        return bodyItem.lines;
    }

    // Set Text
    if (tooltipModel.body) {
        var titleLines = tooltipModel.title || [];
        var bodyLines = tooltipModel.body.map(getBody);

        var innerHtml = '<thead>';

        titleLines.forEach(function (title) {
            innerHtml += '<tr><th>' + title + '</th></tr>';
        });
        innerHtml += '</thead><tbody>';

        bodyLines.forEach(function (body, i) {
            var colors = tooltipModel.labelColors[i];
            var style = 'background:' + colors.backgroundColor;
            style += '; border-color:' + colors.borderColor;
            style += '; border-width: 2px';
            var span = '<span style="' + style + '"></span>';
            innerHtml += '<tr><td>' + span + body + '</td></tr>';
        });
        innerHtml += '</tbody>';

        var tableRoot = tooltipEl.find('table');
        tableRoot.html(innerHtml);
        tableRoot.css({
            backgroundColor: 'rgba(0,0,0,0.8)',
            color: '#fff'
        })
    }

    // `this` will be the overall tooltip
    var position = this._chart.canvas.getBoundingClientRect();

    tooltipEl.css({
        position: 'absolute',
        top: position.top + tooltipModel.caretY + window.scrollY + 10 + 'px',
        left: position.left + tooltipModel.caretX + window.scrollX + 10 + 'px',
        backgroundColor: tooltipModel.backgroundColor,
        borderRadius: '3px',

        opacity: 1,
        padding: tooltipModel.yPadding + 'px ' + tooltipModel.xPadding + 'px',

        fontSize: tooltipModel.bodyFontSize + 'px',
        fontFamily: tooltipModel._bodyFontFamily,
        fontStyle: tooltipModel._bodyFontStyle
    });
}

