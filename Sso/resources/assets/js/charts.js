$(document).ready(function () {
    /*----------------------------------------------------------
        Sparkline
    -----------------------------------------------------------*/
    function sparklineBar(id, value, height, barWidth, barColor, barSpacing) {
        $('.'+id).sparkline(value, {
            type: 'bar',
            height: height,
            barWidth: barWidth,
            barColor: barColor,
            barSpacing: barSpacing
        })
    }
    
    function sparklineLine(id, value, width, height, lineColor, fillColor, lineWidth, maxSpotColor, minSpotColor, spotColor, spotRadius, hSpotColor, hLineColor) {
        $('.'+id).sparkline(value, {
            type: 'line',
            width: width,
            height: height,
            lineColor: lineColor,
            fillColor: fillColor,
            lineWidth: lineWidth,
            maxSpotColor: maxSpotColor,
            minSpotColor: minSpotColor,
            spotColor: spotColor,
            spotRadius: spotRadius,
            highlightSpotColor: hSpotColor,
            highlightLineColor: hLineColor
        });
    }
    
       
    if ($('.sparkline-1')[0]) {
        sparklineLine('sparkline-1', [9,5,6,3,9,7,5,4,6,5,6,4,9], '100%', 50, 'rgba(255,255,255,0.6)', 'rgba(0,0,0,0)', 1.5, '#fff', '#fff', '#fff', 5, '#fff', '#fff');
    }
    if ($('.sparkline-2')[0]) {
        sparklineLine('sparkline-2', [2,4,6,5,6,4,5,3,7,3,6,5,9,6], '100%', 50, 'rgba(255,255,255,0.6)', 'rgba(0,0,0,0)', 1.5, '#fff', '#fff', '#fff', 5, '#fff', '#fff');
    }
    if ($('.sparkline-3')[0]) {
        sparklineLine('sparkline-3', [9,4,6,5,6,4,5,7,9,3,6,5,9], '100%', 50, 'rgba(255,255,255,0.6)', 'rgba(0,0,0,0)', 1.5, '#fff', '#fff', '#fff', 5, '#fff', '#fff');
    }
    
    
    if ($('.sparkline-bar-1')[0]) {
        sparklineBar('sparkline-bar-1', [6,9,5,6,3,7,5,4,6,5,6,4,2,5,8,2,6,9], 40, 3, '#FDECB7', 2);
    }
    if ($('.sparkline-bar-2')[0]) {
        sparklineBar('sparkline-bar-2', [5,7,2,5,2,8,6,7,6,5,3,1,9,3,5,8,2,4], 40, 3, '#FDECB7', 2);
    }
    if ($('.sparkline-bar-3')[0]) {
        sparklineBar('sparkline-bar-3', [3,9,1,3,5,6,7,6,8,2,5,2,7,5,6,7,6,8], 40, 3, '#FDECB7', 2);
    }
    
    if ($('.sparkline-bar-4')[0]) {
        sparklineBar('sparkline-bar-4', [6,9,5,6,3,7,5,4,6,5,6,4,2,5,8,2,6,9], 50, 4, 'rgba(255,255,255,0.7)', 2);
    }
    if ($('.sparkline-bar-5')[0]) {
        sparklineBar('sparkline-bar-5', [5,7,2,5,2,8,6,7,6,5,3,1,9,3,5,8,2,4], 50, 4, 'rgba(255,255,255,0.7)', 2);
    }
    if ($('.sparkline-bar-6')[0]) {
        sparklineBar('sparkline-bar-6', [3,9,1,3,5,6,7,6,8,2,5,2,7,5,6,7,6,8], 50, 4, 'rgba(255,255,255,0.7)', 2);
    }
    
    /*----------------------------------------------------------
        Easy Pie Charts
    -----------------------------------------------------------*/
    function easyPieChart(id, barColor, trackColor, scaleColor, lineWidth, size) {
        $('.'+id).easyPieChart({
            easing: 'easeOutBounce',
            barColor: barColor,
            trackColor: trackColor,
            scaleColor: scaleColor,
            lineCap: 'square',
            lineWidth: lineWidth,
            size: size,
            animate: 3000,
            onStep: function(from, to, percent) {
                $(this.el).find('.percent').text(Math.round(percent));
            }
        });
    }
    
    easyPieChart('easy-pie-1', 'rgba(255,255,255,0.8)', 'rgba(0,0,0,0.08)', 'rgba(0,0,0,0)', 3, 150);
    easyPieChart('easy-pie-2', '#fff', 'rgba(0,0,0,0.08)', 'rgba(0,0,0,0)', 2, 75);
    easyPieChart('easy-pie-3', '#fff', 'rgba(0,0,0,0.08)', 'rgba(0,0,0,0)', 2, 75);
    easyPieChart('easy-pie-4', '#fff', 'rgba(0,0,0,0.08)', 'rgba(0,0,0,0)', 2, 75);
});