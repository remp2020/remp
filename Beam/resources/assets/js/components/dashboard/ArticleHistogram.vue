<template>
    <div class="article-chart">

        <!--<svg width="960" height="500">-->
        <!--<g style="transform: translate(0, 10px)">-->
        <!--&lt;!&ndash;<path :d="line" />&ndash;&gt;-->
        <!--</g>-->
        <!--</svg>-->

        <svg ref="svg" width="960" height="300"></svg>

    </div>
</template>

<style>
</style>

<style scoped>

</style>

<script>
    import axios from 'axios'
    import * as d3 from 'd3'

    const props = {
        url: {
            type: String,
            required: true
        }
    }

    export default {
        name: 'article-chart',
        props: props,
        data() {
            return {
                data: null,
                loading: false
            };
        },
        watch: {
            data(val) {
                this.calc(val.results, val.tags)
            }
        },
        mounted() {
            this.loadData()
        },
        methods: {
            calc(results, tags) {
                console.log(results)
                console.log(tags)

                let svg = d3.select(this.$refs.svg),
                    margin = {top: 20, right: 20, bottom: 30, left: 50},
                    width = svg.attr("width") - margin.left - margin.right,
                    height = svg.attr("height") - margin.top - margin.bottom

                // Convert string values to date, numbers
                let parsedData = results.map(function (d) {
                    let dataObject = {
                        date: d3.isoParse(d.Date)
                    };
                    tags.forEach(function (s) {
                        dataObject[s] = +d[s];
                    })
                    return dataObject;
                });


                let stack = d3.stack()
                    .keys(tags)
                    .offset(d3.stackOffsetNone)

                let layers = stack(parsedData);

                function getDate(d) {
                    return d.date;
                }

                let x = d3.scaleTime()
                    .domain([parsedData[0].date, parsedData[parsedData.length - 1].date])
                    .range([0, width]);

                let y = d3.scaleLinear()
                    .domain([0, d3.max(layers, stackMax)])
                    .range([height, 0]);

                let xAxis = d3.axisBottom(x),
                    yAxis = d3.axisLeft(y);

                let gX = svg.append("g")
                    .attr("transform", "translate(0," + height + ")")
                    .attr("class", "axis axis--x")
                    .call(xAxis)
                    .select(".domain")
                    .remove();

                let gY = svg.append("g")
                    .attr("class", "axis axis--y")
                    .call(yAxis)

                let colors = tags.map(function (d, i) {
                    return d3.interpolateWarm(i / tags.length);
                });

                let colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                let area = d3.area()
                    .x(function (d, i) { return x(d.data.date) })
                    .y0(function (d) { return y(d[0]); })
                    .y1(function (d) { return y(d[1]); })

                let layerGroups = svg.selectAll(".layer")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer");

                layerGroups.append("path")
                    .attr("d", area)
                    .attr("fill", function (d, i) {
                        return colors[i];
                    });

                function stackMax(layer) {
                    return d3.max(layer, function (d) { return d[1]; });
                }

            },
            loadData() {
                console.log('loading data')
                this.loading = true
                axios
                    .get(this.url, {
                        params: {
                            'tz': Intl.DateTimeFormat().resolvedOptions().timeZone,
                            'interval': 'today',
                        }
                    })
                    .then(response => {
                        this.loading = false
                        this.data = response.data
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        }
    }
</script>