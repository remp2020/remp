<template>
    <div :id="`svg-container-${this.chartContainerId}`">
    </div>
</template>

<script>
    import * as d3 from 'd3'

    let props = {
        chartData: {
            type: Array,
            required: true,
        },
        chartContainerId: {
            type: String,
            required: true,
        }
    };

    export default {
        props: props,
        name: 'sparklineChart',
        watch: {
            chartData(val, oldVal) {
                if (JSON.stringify(val) !== JSON.stringify(oldVal)) {
                    this.drawChart(val);
                }
            }
        },
        mounted() {
            this.drawChart(this.chartData);
        },
        methods: {
            drawChart(data) {
                let parsedData = [];
                const svgContainer = `#svg-container-${this.chartContainerId}`;
                if ($(svgContainer).find('svg').length) {
                    $(svgContainer).find('svg')[0].remove();
                }

                const mainContainer = $(`#${this.chartContainerId}`);
                const width = mainContainer.width();
                const height = mainContainer.height();
                const x = d3.scaleTime().range([0, width]);
                const y = d3.scaleLinear().range([height, 0]);
                const parseDate = d3.timeParse("%s");
                const line = d3.line()
                    .curve(d3.curveMonotoneX)
                    .x(function (d) {return x(d.date);})
                    .y(function (d) {return y(d.count);});

                data.forEach(function (d, i) {
                    parsedData[i] = {
                        date: parseDate(d.t), // t - timestamp
                        count: d.c // c - count
                    };
                });
                x.domain(d3.extent(parsedData, function(d) { return d.date; }));
                y.domain(d3.extent(parsedData, function(d) { return d.count; }));

                d3.select(svgContainer)
                    .append('svg')
                    .attr('width', width)
                    .attr('height', height)
                    .append('path')
                    .datum(parsedData)
                    .attr('d', line)
                    .attr('stroke', '#626262')
                    .attr('fill', 'none');
            }
        }
    }
</script>
