<template>
    <div id="article-chart">
        <svg ref="svg" width="950" height="300"></svg>
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

    let svg, x,y, xAxis, yAxis = null

    function stackMax(layer) {
        return d3.max(layer, function (d) { return d[1]; });
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
                this.fillData(val.results, val.tags)
            }
        },
        mounted() {
            this.createGraph()
            this.loadData()
            setInterval(this.loadData, 5000)
        },
        methods: {
            createGraph(){
                svg = d3.select(this.$refs.svg)
                let margin = {top: 20, right: 20, bottom: 30, left: 50},
                    width = svg.attr("width") - margin.left - margin.right,
                    height = svg.attr("height") - margin.top - margin.bottom

                x = d3.scaleTime().range([0, width])
                y = d3.scaleLinear().range([height, 0])

                xAxis = d3.axisBottom(x)
                yAxis = d3.axisLeft(y)

                let gX = svg.append("g")
                    .attr("transform", "translate(0," + height + ")")
                    .attr("class", "axis axis--x")
                    .call(xAxis)
            },
            fillData(results, tags) {
                console.log('filling data')
                // Prepare data
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

                // Scale the range of the data
                x.domain([parsedData[0].date, parsedData[parsedData.length - 1].date])
                y.domain([0, d3.max(layers, stackMax)])

                // Tags
                let colors = tags.map(function (d, i) {
                    return d3.interpolateWarm(i / tags.length);
                });

                let colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                svg.selectAll(".layer").remove();

                // Update data
                let layerGroups = svg.selectAll(".layer")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer");

                // Draw data
                let area = d3.area()
                    .x(function (d, i) { return x(d.data.date) })
                    .y0(function (d) { return y(d[0]); })
                    .y1(function (d) { return y(d[1]); })

                layerGroups.append("path")
                    .attr("d", area)
                    .attr("fill", function (d, i) {
                        return colors[i];
                    });

                // Update axis
                svg.select('.axis--x').call(xAxis)
            },
            loadData() {
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